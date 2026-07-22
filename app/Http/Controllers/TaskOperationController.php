<?php
//هذا الملف فيه العمليات الأساسية: إنشاء مهمة، عرضها، وحفظها، وتحديث حالتها.
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Task;
use App\Models\User;
use App\Services\Tasks\TaskActivityService;
use App\Services\Tasks\TaskWorkflowService;
use App\Support\Tasks\TaskAuthorization;
use App\Support\Tasks\TaskHelpers;

class TaskOperationController extends Controller
{
    use TaskAuthorization, TaskHelpers;

    public function __construct(
        private TaskActivityService $activity,
        private TaskWorkflowService $workflow
    ) {}

    // صفحة إنشاء مهمة جديدة
    public function create()
    {
        $this->checkCreationPermissions();

        $user = Auth::user();
        $subordinates  = collect();
        $otherManagers = collect();
        $cisoUser      = null;

        if ($user->role === 'ciso') {
            // CISO  يرسل لمدراء الإدارات
            $subordinates = User::where('role', 'manager')->get();
        } elseif ($user->role === 'manager') {
            // المدير يرسل لموظفينه
            $subordinates = User::where('department', $user->department)
                ->whereIn('role', ['team_leader', 'employee'])
                ->get();
            // ويرسل للمدراء الثانيين
            $otherManagers = User::where('role', 'manager')
                ->where('id', '!=', $user->id)
                ->get();
            // ويرسل للمدير العام
            $cisoUser = User::where('role', 'ciso')->first();
        }

        return view('tasks.create', compact('subordinates', 'otherManagers', 'cisoUser'));
    }

    // حفظ المهمة في قاعدة البيانات
    public function store(Request $request)
    {
        $this->checkCreationPermissions();

        $request->validate([
            'title' => 'required|string|max:255',
            'assigned_to' => 'required|exists:users,id',
            'due_date' => 'required|date|after_or_equal:today',
            'priority' => 'required',
            'complexity' => 'required',
            'sub_category' => 'required',
        ], [
            'due_date.after_or_equal' => 'تاريخ الاستحقاق لا يمكن أن يكون في الماضي.',
        ]);

        $creator = Auth::user();
        $assignee = User::findOrFail((int)$request->assigned_to);

        $taskType = 'workflow';
        $isExecutive = (
            ($creator->role === 'ciso' && $assignee->role === 'manager')
            || ($creator->role === 'manager' && $assignee->role === 'ciso')
        );
        if ($isExecutive) $taskType = 'executive';

        $task = Task::create(array_merge($request->all(), [
            'created_by' => Auth::id(),
            'status' => 'pending',
            'completion_percentage' => 0,
            'task_type' => $taskType,
        ]));

        $task->team()->attach($request->assigned_to);

        $this->activity->addSystemComment($task, "تم إنشاء المهمة وإسنادها إلى: " . $assignee->name);
        $this->activity->sendNotificationEmail($task, "تم تكليفك بمهمة جديدة", $assignee);

        return redirect()->route('dashboard')->with('success', 'تم الإسناد بنجاح.');
    }

    // عرض تفاصيل المهمة والتعليقات
    public function show($id)
    {
        $task = Task::with(['creator', 'assignee', 'team', 'comments.user'])->findOrFail($id);
        $user = Auth::user();

        $isExecutive = (($task->task_type ?? 'workflow') === 'executive');

        if ($isExecutive) {
            $allowed = (
                $user->role === 'ciso'
                || ($user->role === 'manager' && in_array($user->id, [$task->created_by, $task->assigned_to], true))
            );

            if (!$allowed) abort(403, 'ليس لديك صلاحية.');

            $filteredComments = $task->comments->filter(function ($c) {
                if (str_contains($c->comment, '[نظام]')) return true;
                return $c->user && in_array($c->user->role, ['ciso', 'manager'], true);
            });

            $isCrossDepartment = false;
            $availableEmployees = collect();
            $allAttachments = collect();
            $selectedIds = [];
            $finalAttachments = collect();

            return view('tasks.show', compact(
                'task',
                'availableEmployees',
                'isCrossDepartment',
                'allAttachments',
                'selectedIds',
                'finalAttachments',
                'isExecutive',
                'filteredComments'
            ));
        }

        // صلاحيات الاطلاع العادية
        $isDirectlyRelated = (
            $task->assigned_to == $user->id ||
            $task->created_by == $user->id ||
            $task->team->contains($user->id)
        );

        $isAuthorizedSupervisor = false;
        if (in_array($user->role, ['ciso', 'auditor'], true)) {
            $isAuthorizedSupervisor = true;
        } elseif (in_array($user->role, ['manager', 'team_leader'], true)) {
            if ($task->assignee && $task->assignee->department === $user->department) {
                $isAuthorizedSupervisor = true;
            }
            if ($task->created_by == $user->id) {
                $isAuthorizedSupervisor = true;
            }
        }

        if (!$isDirectlyRelated && !$isAuthorizedSupervisor) {
            abort(403, 'عذراً، ليس لديك صلاحية للاطلاع على هذا السجل.');
        }

        //  استثناء مهم: إذا المهمة من مدير إلى CISO لا تعتبر "بين إدارات"
        $isManagerToCiso = (
            ($task->creator?->role === 'manager') &&
            ($task->assignee?->role === 'ciso')
        );

        //  Cross Department الحقيقي فقط (بدون CISO كمنشئي/مستلم)
        $isCrossDepartment = (
            $task->creator && $task->assignee
            && ($task->creator->role !== 'ciso')
            && ($task->assignee->role !== 'ciso')
            && ($task->creator->department !== $task->assignee->department)
        );

        //  إذا مدير  CISO نخليه دائمًا false
        if ($isManagerToCiso) {
            $isCrossDepartment = false;
        }

        $isFinished = in_array($task->status, ['completed', 'closed', 'archived'], true);
        $isRequesterManager = ($user->role === 'manager' && $user->id == $task->created_by);

        // لو المدير هو الطالب والمعاملة عند إدارة ثانية 
        if ($isCrossDepartment && $isRequesterManager) {
            $isFinished = in_array($task->status, ['completed', 'closed', 'archived'], true);
            $canSeeOutputs = ($task->status === 'waiting_requester') || $isFinished;

            $message = 'هذه المهمة قيد العمل حالياً لدى الإدارة المختصة.';
            if ($task->status === 'waiting_requester') {
                $message = 'تم إنجاز المهمة من الإدارة المنفذة وبانتظار اعتماد الجهة الطالبة.';
            } elseif ($task->status === 'returned') {
                $message = 'تمت إعادة المهمة للإدارة المنفذة لوجود ملاحظات.';
            } elseif ($isFinished) {
                $message = 'تم اعتماد المهمة وإغلاقها. يمكنك تحميل المخرجات.';
            }

            $selectedIds = [];
            $finalAttachments = collect();

            if ($canSeeOutputs) {
                $selectedIds = DB::table('task_final_attachments')
                    ->where('task_id', $task->id)
                    ->pluck('comment_id')
                    ->toArray();

                $finalAttachments = $task->comments()
                    ->whereIn('id', $selectedIds)
                    ->whereNotNull('attachment')
                    ->orderByDesc('created_at')
                    ->get();
            }

            return view('tasks.restricted_status', [
                'task' => $task,
                'selectedIds' => $selectedIds,
                'finalAttachments' => $finalAttachments,
                'message' => $message,
                'isFinished' => $isFinished,
                'canSeeOutputs' => $canSeeOutputs,
            ]);
        }


        $availableEmployees = collect();

        if (!in_array($task->status, ['closed', 'archived'], true)) {

            $deptTree = config('departments.children', []);

            // 1) إذا كان المستخدم CISO (المدير العام)
            if ($user->role === 'ciso') {

                // إدارة التنفيذ = إدارة المسؤول الرئيسي
                $executorDept = $task->assignee?->department;

                if ($executorDept) {
                    $allowedDepts = array_merge([$executorDept], $deptTree[$executorDept] ?? []);

                    // فقط موظفين + قادة فرق من إدارة التنفيذ + الإدارات التابعة
                    $availableEmployees = User::whereIn('department', $allowedDepts)
                        ->whereIn('role', ['employee', 'team_leader'])
                        ->where('id', '!=', $user->id)
                        ->get();
                } else {
                    $availableEmployees = collect();
                }
            }

            // 2) إذا كان المستخدم مدير إدارة (Manager)
            elseif ($user->role === 'manager') {

                $managerDept = $user->department;
                $allowedDepts = array_merge([$managerDept], $deptTree[$managerDept] ?? []);

                // موظفين + قادة فرق من إدارته + الإدارات التابعة
                $availableEmployees = User::whereIn('department', $allowedDepts)
                    ->where('id', '!=', $user->id)
                    ->whereIn('role', ['employee', 'team_leader'])
                    ->get();
            }

            // 3) إذا كان قائد فريق (Team Leader)
            elseif ($user->role === 'team_leader') {

                // يرى موظفي قسمه فقط
                $availableEmployees = User::where('department', $user->department)
                    ->where('role', 'employee')
                    ->where('id', '!=', $user->id)
                    ->get();
            }
        }

        $allAttachments = collect();
        $selectedIds = [];
        $finalAttachments = collect();

        if ($user->role === 'manager' && $isCrossDepartment && $task->assignee) {
            $isExecutorManager = ($user->department === $task->assignee->department);
            if ($isExecutorManager && in_array($task->status, ['reviewed', 'submitted'], true)) {
                $allAttachments = $task->comments()
                    ->whereNotNull('attachment')
                    ->orderByDesc('created_at')
                    ->get();

                $selectedIds = DB::table('task_final_attachments')
                    ->where('task_id', $task->id)
                    ->pluck('comment_id')
                    ->toArray();

                $finalAttachments = $task->comments()
                    ->whereIn('id', $selectedIds)
                    ->whereNotNull('attachment')
                    ->orderByDesc('created_at')
                    ->get();
            }
        }

        $isExecutive = false;
        $filteredComments = $task->comments;

        return view('tasks.show', compact(
            'task',
            'availableEmployees',
            'isCrossDepartment',
            'allAttachments',
            'selectedIds',
            'finalAttachments',
            'isExecutive',
            'filteredComments'
        ));
    }

    public function updateStatus(Request $request, $id)
    {
        $this->checkAuditorRestriction();

        $task = Task::with(['team', 'creator', 'assignee'])->findOrFail($id);
        $user = Auth::user();
        $action = (string) $request->input('status', '');

        $oldStatus = $task->status;

        if ($oldStatus === 'waiting_requester' && $action === 'returned') {
            $request->validate([
                'return_note' => 'required|string|min:3|max:2000',
            ], [
                'return_note.required' => 'سبب الإرجاع مطلوب.',
            ]);
        }

        if ($oldStatus === 'waiting_requester' && $action === 'approve') {
            $hasSelection = DB::table('task_final_attachments')
                ->where('task_id', $task->id)
                ->exists();

            if (!$hasSelection) {
                return back()->with('error', 'لا يمكن الاعتماد قبل تحديد المخرجات النهائية من سجل المراسلات.');
            }
        }

        if ($request->has('completion_percentage')) {
            $percent = (int) $request->input('completion_percentage', 0);
            $percent = max(0, min(100, $percent));

            $isAuthorized = (
                $task->assigned_to == $user->id ||
                $task->team->contains($user->id) ||
                $user->role === 'ciso'
            );

            if (!$isAuthorized) return back()->with('error', 'غير مصرح.');

            $task->update(['completion_percentage' => $percent]);
            return back()->with('success', 'تم حفظ النسبة.');
        }

        $result = $this->workflow->decideStatusChange($task, $user, $action);

        if (!empty($result['blocked'])) {
            return back()->with('error', $result['error'] ?? 'غير مصرح.');
        }

        $newStatus = $result['newStatus'] ?? $task->status;
        $msg = $result['msg'] ?? '';
        $notifyUser = $result['notifyUser'] ?? null;

        if ($newStatus === $oldStatus) return back();

        $task->status = $newStatus;
        $task->save();

        if (!empty($msg)) {
            $this->activity->addSystemComment($task, $msg);
        }

        if ($oldStatus === 'waiting_requester' && $action === 'returned' && $request->filled('return_note')) {
            $this->activity->addSystemComment($task, 'ملاحظة الجهة الطالبة: ' . $request->input('return_note'));
        }

        if ($notifyUser) {
            $this->activity->sendNotificationEmail($task, $msg ?: 'تم تحديث حالة المهمة.', $notifyUser);
        }

        if ($request->input('redirect_to') === 'dashboard') {
            return redirect()->route('dashboard')->with('success', 'تم تحديث الحالة بنجاح.');
        }

        return back()->with('success', 'تم تحديث الحالة بنجاح.');
    }
}
