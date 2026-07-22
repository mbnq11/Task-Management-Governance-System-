<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Task;
use App\Models\User;
use App\Exports\AuditorReportExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * دالة مركزية لجلب البيانات ومعالجتها
     * تستخدم للتقرير العام ولتقرير الموظف
     */
    private function getReportData(Request $request, $employeeId = null)
    {
        // 1. بناء الاستعلام الأساسي
        $query = Task::with(['assignee', 'creator', 'team']);

        // فلترة حسب الموظف (لتقرير الأداء الفردي)
        if ($employeeId) {
            $query->where(function ($q) use ($employeeId) {
                $q->where('assigned_to', $employeeId)
                  ->orWhereHas('team', function ($subQ) use ($employeeId) {
                      $subQ->where('users.id', $employeeId);
                  });
            });
        }

        // فلترة حسب الربع السنوي (Quarter)
        if ($request->filled('quarter')) {
            [$year, $q] = array_pad(explode('-', $request->quarter), 2, null);
            if ($year && $q) {
                $startMonth = (($q - 1) * 3) + 1;
                $startDate = Carbon::create($year, $startMonth, 1)->startOfDay();
                $endDate = $startDate->copy()->addMonths(3)->subSecond()->endOfDay();
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }
        }

        // فلترة حسب الأولوية
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        // تنفيذ الاستعلام
        $tasks = $query->orderByDesc('created_at')->get();

        // 2. إحصائيات الموظفين (للمصفوفة)
        $employeesStats = [];

        foreach ($tasks as $task) {
            // نجمع كل من عمل على المهمة
            $users = collect([$task->assignee]);
            if ($task->team) {
                $users = $users->merge($task->team);
            }
            $users = $users->filter()->unique('id'); 

            $isCompleted = in_array($task->status, ['completed', 'closed', 'archived']);
            $isInProgress = in_array($task->status, ['in_progress', 'pending', 'submitted', 'reviewed']);
            $isOverdue = ($task->due_date < now() && !$isCompleted);

            foreach ($users as $user) {
                if (!isset($employeesStats[$user->id])) {
                    $employeesStats[$user->id] = [
                        'name'        => $user->name,
                        'department'  => $user->department ?? '-',
                        'total'       => 0,
                        'completed'   => 0,
                        'in_progress' => 0,
                        'overdue'     => 0,
                        'rate'        => 0,
                        'compliance_score' => 0 
                    ];
                }

                $employeesStats[$user->id]['total']++;
                if ($isCompleted) $employeesStats[$user->id]['completed']++;
                if ($isInProgress) $employeesStats[$user->id]['in_progress']++;
                if ($isOverdue) $employeesStats[$user->id]['overdue']++;
            }
        }

        // حساب النسب المئوية
        foreach ($employeesStats as &$stat) {
            $stat['rate'] = $stat['total'] > 0 ? round(($stat['completed'] / $stat['total']) * 100) : 0;
            $stat['compliance_score'] = $stat['rate']; // توحيد المسمى
        }
        
        // ترتيب الموظفين حسب الأفضل أداءً
        usort($employeesStats, fn($a, $b) => $b['rate'] <=> $a['rate']);

        // 3. الإحصائيات العامة (KPIs)
        $total = $tasks->count();
        $completedCount = $tasks->where('status', 'completed')->count();
        $closedCount    = $tasks->where('status', 'closed')->count();
        $archivedCount  = $tasks->where('status', 'archived')->count();
        
        // المهام المنتهية فعلياً
        $doneCount = $completedCount + $closedCount + $archivedCount;

        // المهام المتأخرة وغير المنتهية
        $overdueList = $tasks->where('due_date', '<', now())
                             ->whereNotIn('status', ['completed', 'closed', 'archived']);
        $overdueCount = $overdueList->count();

        // حساب النسب العامة
        $complianceRate = $total > 0 ? round(($doneCount / $total) * 100) : 0;
        $completionRate = $total > 0 ? round(($completedCount / $total) * 100) : 0; // نسبة المكتمل فقط
        $overdueRate    = $total > 0 ? round(($overdueCount / $total) * 100) : 0;

        // متوسط زمن الإغلاق
        $avgResolutionTime = 0;
        $finishedTasks = $tasks->whereIn('status', ['completed', 'closed', 'archived']);
        if ($finishedTasks->count() > 0) {
            $totalDays = 0;
            foreach ($finishedTasks as $task) {
                $totalDays += $task->created_at->diffInDays($task->updated_at);
            }
            $avgResolutionTime = round($totalDays / $finishedTasks->count());
        }

        // إرجاع البيانات في مصفوفة موحدة
        return [
            'allTasks'          => $tasks,
            'employeesStats'    => $employeesStats,
            'employeePerformance' => $employeesStats, 
            'total'             => $total,
            'totalTasks'        => $total, 
            'completed'         => $completedCount,
            'completedTasks'    => $completedCount, 
            'closedCount'       => $closedCount,
            'archivedCount'     => $archivedCount,
            'in_progress'       => $tasks->whereIn('status', ['in_progress', 'pending', 'submitted', 'reviewed'])->count(),
            'delayed'           => $overdueCount,
            'overdueList'       => $overdueList,
            'complianceRate'    => $complianceRate,
            'completionRate'    => $completionRate,
            'overdueRate'       => $overdueRate,
            'avgResolutionTime' => $avgResolutionTime,
            'date'              => now()->format('Y-m-d'),
        ];
    }

    /**
     * تصدير التقرير الشامل إلى ملف Excel
     */
    public function exportExcel(Request $request)
    {
        $data = $this->getReportData($request);
        return Excel::download(new AuditorReportExport($data), 'Report_'.date('Y-m-d').'.xlsx');
    }

    /**
     * طباعة التقرير العام (PDF/Print View)
     */
    public function printGeneralReport(Request $request)
    {
        $data = $this->getReportData($request);
        return view('reports.general_report_print', $data);
    }

    /**
     * طباعة تقرير أداء موظف محدد (PDF/Print View)
     */
    public function printEmployeeReport($id, Request $request)
    {
        $user = User::findOrFail($id);
        $data = $this->getReportData($request, $id);

        return view('reports.employee_report_print', [
            'user'  => $user,
            'tasks' => $data['allTasks'],
            'stats' => [
                'total'     => $data['total'],
                'completed' => $data['completed'],
                'overdue'   => $data['delayed'],
                'rate'      => $data['complianceRate']
            ],
            'filters' => [
                'quarter'  => $request->quarter,
                'priority' => $request->priority,
            ]
        ]);
    }
}