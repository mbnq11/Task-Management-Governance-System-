<?php
// هذا الملف خاص بإدارة الفريق، إضافة أو حذف أعضاء.
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Task;
use App\Models\User;
use App\Services\Tasks\TaskTeamService;
use App\Services\Tasks\TaskActivityService;

class TaskTeamController extends Controller
{
    public function __construct(
        private TaskTeamService $teamService,
        private TaskActivityService $activity
    ) {}

    // إضافة عضو للفريق
    public function addTeamMember(Request $request, $id)
    {
        $task = Task::with(['assignee', 'creator', 'team'])->findOrFail($id);
        $authUser = auth()->user();

        if (!in_array($authUser->role, ['manager', 'team_leader', 'ciso'], true)) {
            return back()->with('error', 'غير مصرح لك.');
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $newMember = User::findOrFail((int) $request->user_id);

        if (($task->task_type ?? 'workflow') === 'executive') {
            return back()->with('error', 'لا يمكن تعديل فريق العمل في المهام التنفيذية.');
        }

        if ($authUser->role === 'ciso') {

            $executorDept = $task->assignee?->department;

            // لا يسمح بإضافة managers أو auditor أو غيره
            if (!in_array($newMember->role, ['employee', 'team_leader'], true)) {
                return back()->with('error', 'المدير العام يمكنه إضافة الموظفين أو قادة الفرق فقط.');
            }

            // الإدارات المسموحة: التنفيذ + التابعة 
            $deptTree = config('departments.children', []);
            $allowedDepts = array_merge([$executorDept], $deptTree[$executorDept] ?? []);

            if (!in_array($newMember->department, $allowedDepts, true)) {
                return back()->with('error', 'لا يمكن إضافة عضو من خارج إدارة التنفيذ أو الإدارات التابعة لها.');
            }
        }

        // المدير ما يضيف إلا من قسمه
        if ($authUser->role === 'manager') {

            $deptTree = config('departments.children', []);
            $allowedDepts = array_merge([$authUser->department], $deptTree[$authUser->department] ?? []);

            if (!in_array($newMember->department, $allowedDepts, true)) {
                return back()->with('error', 'لا يمكن إضافة عضو من خارج إدارتك أو الإدارات التابعة لها.');
            }

            if (!in_array($newMember->role, ['employee', 'team_leader'], true)) {
                return back()->with('error', 'يمكن إضافة الموظفين أو قادة الفرق فقط.');
            }
        }

        // قائد الفريق ما يضيف إلا من قسمه
        if ($authUser->role === 'team_leader') {
            if ($newMember->department !== $authUser->department || $newMember->role !== 'employee') {
                return back()->with('error', 'لا يمكن إضافة إلا موظف من نفس قسمك.');
            }
        }

        // منع التكرار
        if ($task->team->contains($newMember->id)) {
            return back()->with('error', 'هذا العضو موجود بالفعل ضمن الفريق.');
        }

        $this->teamService->addTeamMember($task, $newMember->id);

        $this->activity->addSystemComment($task, "أضاف عضواً للفريق: " . $newMember->name);
        $this->activity->sendNotificationEmail($task, "تم إضافتك لفريق العمل", $newMember);

        return back()->with('success', 'تمت الإضافة.');
    }

    // حذف عضو من الفريق
    public function removeTeamMember($task_id, $user_id)
    {
        $task = Task::findOrFail($task_id);

        if (!in_array(auth()->user()->role, ['manager', 'team_leader', 'ciso'], true)) {
            return back()->with('error', 'غير مصرح لك.');
        }

        $this->teamService->removeTeamMember($task, (int) $user_id);

        $this->activity->addSystemComment($task, "أزال عضواً من الفريق.");

        return back()->with('success', 'تمت الإزالة.');
    }
}
