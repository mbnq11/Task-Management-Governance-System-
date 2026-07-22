<?php
// هذا الملف خاص فقط بالداشبورد وعرض الإحصائيات
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Task;
use App\Models\User;
use App\Models\TaskComment;
use App\Services\Tasks\TaskDashboardService;
use App\Support\Tasks\TaskAuthorization;
use App\Support\Tasks\TaskHelpers;

class TaskDashboardController extends Controller
{
    use TaskAuthorization, TaskHelpers;

    public function __construct(
        private TaskDashboardService $dashboard
    ) {}

    // هذا الفنشكن اللي يجيب لك كل بلاوي الداشبورد والإحصائيات
    public function index(Request $request)
    {
        $user  = Auth::user();
        $today = now()->format('Y-m-d');

        // هنا نضبط الفلاتر عشان لو بغيت تبحث بربع سنة ولا بأولوية معينة
        $filters = [];
        if ($request->filled('quarter'))  $filters['quarter']  = $request->quarter;
        if ($request->filled('priority')) $filters['priority'] = $request->priority;
        if ($user->role === 'employee')   $filters['assigned_to'] = $user->id;

        $tasksQuery = Task::query();

        // فلتر الأولوية
        if ($request->filled('priority')) {
            $tasksQuery->where('priority', $request->priority);
        }

        // لو هو موظف نجيب له بس اللي عليه
        if ($user->role === 'employee') {
            $tasksQuery->where('assigned_to', $user->id);
        }

        // حسبة الربع السنوي
        $qStart = null;
        $qEnd = null;
        if ($request->filled('quarter')) {
            [$year, $q] = array_pad(explode('-', $request->quarter), 2, null);
            $year = (int) $year;
            $q    = (int) $q;

            if ($year > 0 && $q >= 1 && $q <= 4) {
                $startMonth = (($q - 1) * 3) + 1;
                $qStart = Carbon::create($year, $startMonth, 1)->startOfDay();
                $qEnd   = (clone $qStart)->addMonths(3)->subSecond()->endOfDay();
                $tasksQuery->whereBetween('created_at', [$qStart, $qEnd]);
            }
        }

        // نجيب إحصائيات الخدمة
        $allTasksStats = $this->dashboard->getTaskStatistics($filters);

        // هنا نحسب الـ KPIs والعدادات (مكتملة، متأخرة، الخ)
        $doneStatuses = ['completed', 'closed', 'archived'];
        $openStatuses = ['pending', 'in_progress', 'submitted', 'returned', 'reviewed', 'endorsed'];

        $totalTasks     = (clone $tasksQuery)->count();
        $completedTasks = (clone $tasksQuery)->where('status', 'completed')->count();
        $closedCount    = (clone $tasksQuery)->where('status', 'closed')->count();
        $archivedCount  = (clone $tasksQuery)->where('status', 'archived')->count();

        $openTasksCount = (clone $tasksQuery)->whereIn('status', $openStatuses)->count();

        $overdueTasks = (clone $tasksQuery)
            ->whereNotIn('status', $doneStatuses)
            ->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->count();

        $doneCount = $completedTasks + $closedCount + $archivedCount;

        $complianceRate = $totalTasks > 0 ? ($doneCount / $totalTasks) * 100 : 0;
        $completionRate = $totalTasks > 0 ? ($completedTasks / $totalTasks) * 100 : 0;
        $overdueRate    = $totalTasks > 0 ? ($overdueTasks / $totalTasks) * 100 : 0;

        $avgResolutionTime = (clone $tasksQuery)
            ->whereIn('status', $doneStatuses)
            ->selectRaw('AVG(DATEDIFF(updated_at, created_at)) as avg_days')
            ->value('avg_days') ?? 0;

        // القوائم اللي تظهر تحت في الداشبورد
        $overdueList = (clone $tasksQuery)
            ->whereNotIn('status', $doneStatuses)
            ->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->orderBy('due_date', 'asc')
            ->with(['assignee'])
            ->get();

        $closedList = (clone $tasksQuery)
            ->where('status', 'closed')
            ->orderByDesc('updated_at')
            ->with(['assignee'])
            ->get();

        $archivedList = (clone $tasksQuery)
            ->where('status', 'archived')
            ->orderByDesc('updated_at')
            ->with(['assignee'])
            ->get();

        $complianceList = (clone $tasksQuery)
            ->where('status', 'completed')
            ->orderByDesc('updated_at')
            ->with(['assignee'])
            ->get();

        $openList = (clone $tasksQuery)
            ->whereIn('status', ['pending', 'in_progress', 'submitted', 'endorsed'])
            ->orderBy('due_date', 'asc')
            ->with(['assignee'])
            ->get();

        // المهام الواردة والمرسلة
        $receivedTasks = Task::where(function ($query) use ($user) {
            $query->where('assigned_to', $user->id)
                ->orWhereHas('team', function ($q) use ($user) {
                    $q->where('users.id', $user->id);
                });
        })
            ->orderByRaw("FIELD(status,
                'pending','in_progress','submitted','reviewed','endorsed','waiting_requester','returned',
                'completed','closed','archived'
            )")
            ->orderByDesc('updated_at')
            ->with(['creator', 'assignee'])
            ->get();

        $sentTasks = collect();
        if (!in_array($user->role, ['team_leader', 'employee'], true)) {
            $sentTasks = Task::where('created_by', $user->id)
                ->orderBy('updated_at', 'desc')
                ->with(['creator', 'assignee'])
                ->get();
        }

        // منطق حساب الأداء للمدراء والمدققين
        $employeePerformance = [];
        $complianceRanking   = [];

        if (in_array($user->role, ['auditor', 'ciso', 'manager'], true)) {

            $doneStatuses       = ['completed', 'closed', 'archived'];
            $inProgressStatuses = ['in_progress', 'submitted', 'reviewed', 'endorsed'];
            $notDoneStatuses    = ['pending', 'returned', 'waiting_requester'];

            $employees = User::whereIn('role', ['employee', 'team_leader'])
                ->when($user->role === 'manager', fn($q) => $q->where('department', $user->department))
                ->get(['id', 'name']);

            $empIds = $employees->pluck('id')->all();

            $taskLinks = DB::query()
                ->fromSub(function ($sub) use ($empIds) {
                    $sub->from('tasks')
                        ->selectRaw('assigned_to as user_id, id as task_id')
                        ->whereIn('assigned_to', $empIds)
                        ->unionAll(
                            DB::table('task_team')
                                ->selectRaw('user_id, task_id')
                                ->whereIn('user_id', $empIds)
                        );
                }, 'links')
                ->selectRaw('DISTINCT user_id, task_id');

            $statsQuery = DB::query()
                ->fromSub($taskLinks, 'ut')
                ->join('tasks as t', 't.id', '=', 'ut.task_id')
                ->when($qStart && $qEnd, fn($q) => $q->whereBetween('t.created_at', [$qStart, $qEnd]))
                ->when($request->filled('priority'), fn($q) => $q->where('t.priority', $request->priority))
                ->groupBy('ut.user_id')
                ->selectRaw('
                    ut.user_id,
                    COUNT(*) as total,
                    SUM(CASE WHEN t.status IN ("completed","closed","archived") THEN 1 ELSE 0 END) as done,
                    SUM(CASE WHEN t.due_date IS NOT NULL AND t.due_date < NOW() AND t.status NOT IN ("completed","closed","archived") THEN 1 ELSE 0 END) as overdue
                ')
                ->get()
                ->keyBy('user_id');

            $tasksBase = DB::query()
                ->fromSub($taskLinks, 'ut')
                ->join('tasks as t', 't.id', '=', 'ut.task_id')
                ->when($qStart && $qEnd, fn($q) => $q->whereBetween('t.created_at', [$qStart, $qEnd]))
                ->when($request->filled('priority'), fn($q) => $q->where('t.priority', $request->priority))
                ->select([
                    'ut.user_id',
                    't.id',
                    't.title',
                    't.status',
                    't.updated_at',
                    't.created_at',
                    't.due_date'
                ]);

            $doneLists = (clone $tasksBase)
                ->whereIn('t.status', $doneStatuses)
                ->orderByDesc('t.updated_at')
                ->get()
                ->groupBy('user_id')
                ->map(fn($g) => $g->take(5));

            $inProgressLists = (clone $tasksBase)
                ->whereIn('t.status', $inProgressStatuses)
                ->orderByRaw('CASE WHEN t.due_date IS NULL THEN 1 ELSE 0 END, t.due_date ASC')
                ->get()
                ->groupBy('user_id')
                ->map(fn($g) => $g->take(5));

            $notDoneLists = (clone $tasksBase)
                ->whereIn('t.status', $notDoneStatuses)
                ->orderByDesc('t.created_at')
                ->get()
                ->groupBy('user_id')
                ->map(fn($g) => $g->take(5));

            foreach ($employees as $emp) {
                $s = $statsQuery[$emp->id] ?? (object)['total' => 0, 'done' => 0, 'overdue' => 0];

                $total   = (int) $s->total;
                $done    = (int) $s->done;
                $overdue = (int) $s->overdue;

                $score = $total > 0 ? round(($done / $total) * 100) : 0;

                $employeePerformance[$emp->name] = [
                    'id'             => $emp->id,
                    'name'           => $emp->name,
                    'total'          => $total,
                    'done'           => $done,
                    'overdue'        => $overdue,
                    'completion_percent' => $score,
                    'compliance_score'   => $score,
                    'lists' => [
                        'done'        => $doneLists[$emp->id] ?? collect(),
                        'in_progress' => $inProgressLists[$emp->id] ?? collect(),
                        'not_done'    => $notDoneLists[$emp->id] ?? collect(),
                    ],
                ];

                $complianceRanking[$emp->name] = ['score' => $score];
            }

            uasort($complianceRanking, fn($a, $b) => $b['score'] <=> $a['score']);
        }

        // الرسم البياني (الشارت)
        $chartLabels = [];
        $chartCreatedData = [];
        $chartCompletedData = [];

        for ($i = 5; $i >= 0; $i--) {
            $m = now()->subMonths($i);
            $chartLabels[] = $m->format('M Y');

            $chartCreatedData[] = (clone $tasksQuery)
                ->whereMonth('created_at', $m->month)
                ->whereYear('created_at', $m->year)
                ->count();

            $chartCompletedData[] = (clone $tasksQuery)
                ->whereIn('status', ['completed', 'closed'])
                ->whereMonth('updated_at', $m->month)
                ->whereYear('updated_at', $m->year)
                ->count();
        }

        // باقي البيانات والتفاصيل الصغيرة
        $tasksByDomain = Task::select('sub_category', DB::raw('count(*) as total'))
            ->groupBy('sub_category')
            ->pluck('total', 'sub_category');

        $auditLogsQuery = TaskComment::query()
            ->where('comment', 'LIKE', '[نظام]%')
            ->whereIn('id', function ($q) {
                $q->from('task_comments')
                    ->selectRaw('MAX(id)')
                    ->where('comment', 'LIKE', '[نظام]%')
                    ->groupBy('task_id');
            })
            ->with(['task.assignee'])
            ->orderByDesc('id');

        /*  فلترة البحث في سجل التدقيق    */
        if ($request->filled('audit_search')) {
            $term = trim($request->audit_search);

            $auditLogsQuery->where(function ($qq) use ($term) {
                // بحث بعنوان المهمة
                $qq->whereHas('task', function ($t) use ($term) {
                    $t->where('title', 'like', "%{$term}%");
                });

                // أو بحث برقم المهمة إذا المستخدم كتب رقم
                if (ctype_digit($term)) {
                    $qq->orWhere('task_id', (int) $term);
                }
            });
        }


        $auditLogs = $auditLogsQuery->get();


        $subordinates = collect();
        if ($user->role == 'ciso') {
            $subordinates = User::where('role', 'manager')->withCount('tasks')->get();
        } elseif ($user->role == 'manager') {
            $subordinates = User::where('department', $user->department)
                ->whereIn('role', ['team_leader', 'employee'])
                ->withCount('tasks')
                ->get();
        } elseif ($user->role == 'team_leader') {
            $subordinates = User::where('department', $user->department)
                ->where('role', 'employee')
                ->withCount('tasks')
                ->get();
        }

        $stats = [
            'total_tasks' => $totalTasks,
            'completed' => $completedTasks,
            'pending' => $openTasksCount,
            'performance_rate' => round($completionRate),
        ];

        $delayedTasks = $receivedTasks->where('due_date', '<', $today);

        $crossDepartmentTasks = collect();
        $returnedList = collect();
        $returnedCount = 0;
        $departmentFlow = collect();

        if ($user->role === 'ciso') {
            $departmentFlow = Task::query()
                ->with(['creator:id,name,role,department', 'assignee:id,name,role,department'])
                ->whereHas('creator', fn($q) => $q->where('role', 'manager'))   // من مدير
                ->whereHas('assignee', fn($q) => $q->where('role', 'manager'))  // إلى مدير
                ->whereColumn('tasks.created_by', '!=', 'tasks.assigned_to')
                ->get()
                ->filter(
                    fn($t) =>
                    $t->creator && $t->assignee &&
                        $t->creator->department && $t->assignee->department &&
                        $t->creator->department !== $t->assignee->department
                )
                ->sortByDesc('updated_at')
                ->take(25)
                ->values()
                ->map(function ($t) {
                    $t->from_dept = $t->creator->department;
                    $t->to_dept   = $t->assignee->department;

                    $labels = [
                        'pending'           => 'جديدة',
                        'in_progress'       => 'جاري العمل',
                        'submitted'         => 'مرفوعة',
                        'reviewed'          => 'تمت المراجعة',
                        'returned'          => 'معادة',
                        'waiting_requester' => 'بانتظار الجهة الطالبة',
                        'completed'         => 'مكتملة',
                        'closed'            => 'مغلقة',
                        'archived'          => 'مؤرشفة',
                        'endorsed'          => 'مرفوعة للمدير العام',
                    ];

                    $t->status_label = $labels[$t->status] ?? $t->status;
                    return $t;
                });


            $returnedList = (clone Task::query())
                ->with(['assignee:id,name', 'creator:id,name,department,role'])
                ->where('status', 'returned')
                ->whereHas('creator', function ($q) {
                    $q->where('role', '!=', 'ciso');
                })
                ->get()
                ->filter(function ($t) {
                    return $t->creator && $t->assignee
                        && $t->creator->department !== $t->assignee->department;
                })
                ->sortByDesc('updated_at')
                ->take(20)
                ->values();

            $returnedCount = $returnedList->count();
        }

        $years = Task::query()
            ->selectRaw('YEAR(created_at) as y')
            ->distinct()
            ->orderByDesc('y')
            ->pluck('y')
            ->toArray();

        $cy = (int) now()->year;
        if (!in_array($cy, $years, true)) array_unshift($years, $cy);
        $years = array_slice(array_unique($years), 0, 3);

        $availableQuarters = [];
        foreach ($years as $y) {
            $availableQuarters["{$y}-1"] = "الربع الأول {$y}";
            $availableQuarters["{$y}-2"] = "الربع الثاني {$y}";
            $availableQuarters["{$y}-3"] = "الربع الثالث {$y}";
            $availableQuarters["{$y}-4"] = "الربع الرابع {$y}";
        }

        $viewPath = 'dashboards.' . $user->role;
        if (!view()->exists($viewPath)) $viewPath = 'tasks.index';

        return view($viewPath, compact(
            'receivedTasks',
            'sentTasks',
            'stats',
            'subordinates',
            'totalTasks',
            'openTasksCount',
            'completedTasks',
            'closedCount',
            'archivedCount',
            'overdueTasks',
            'complianceRate',
            'completionRate',
            'overdueRate',
            'avgResolutionTime',
            'overdueList',
            'closedList',
            'archivedList',
            'complianceList',
            'openList',
            'chartLabels',
            'chartCreatedData',
            'chartCompletedData',
            'tasksByDomain',
            'auditLogs',
            'availableQuarters',
            'employeePerformance',
            'complianceRanking',
            'delayedTasks',
            'crossDepartmentTasks',
            'returnedList',
            'returnedCount',
            'departmentFlow'
        ));
    }
}
