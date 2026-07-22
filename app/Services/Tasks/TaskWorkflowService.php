<?php
// قلب سجل العمل 
namespace App\Services\Tasks;

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TaskWorkflowService
{
    /**
     * تحقق هل المهمة رايحة من إدارة لإدارة ثانية؟
     * ( الـ CISO خارج الحسبة لأنه هو "الكل" ومو تابع لإدارة محددة في هذا السياق)
     */
    private function isCrossDepartment(Task $task): bool
    {
        $task->loadMissing(['creator', 'assignee']);

        if (!$task->creator || !$task->assignee) return false;

        // لو المنشئ هو الـ CISO، هذي معاملة وما نعتبرها "بين إدارات"
        if (($task->creator->role ?? null) === 'ciso') return false;

        // إذا الإدارات تختلف، يعني معاملة خارجية
        return $task->creator->department !== $task->assignee->department;
    }

    /**
     *  نحدد وش بيصير للحالة وش الرسالة ومين يوصله تنبيه بناء على الأكشن
     */
    public function decideStatusChange(Task $task, $user, string $action): array
    {
        $task->loadMissing(['creator', 'assignee']);

        $newStatus  = $task->status;
        $msg        = '';
        $notifyUser = null;

        $isCrossDept = $this->isCrossDepartment($task);

        // 1) المدير العام (CISO)
        if ($user->role === 'ciso') {

            // اعتماد المهام الداخلية اللي ارتفعت له من المدراء (Endorsed)
            if (!$isCrossDept && $task->status === 'endorsed') {
                if ($action === 'completed') {
                    $newStatus = 'completed';
                    $task->completion_percentage = 100;
                    $msg = 'تم الاعتماد النهائي من المدير العام.';
                } elseif ($action === 'returned') {
                    $newStatus = 'returned';
                    $task->completion_percentage = 0;
                    $msg = 'توجيهات من المدير العام: إعادة للتعديل.';
                    $notifyUser = $task->assignee;
                }
            }

            // الإغلاق القسري (في أي وقت يقدر يسكر الموضوع)
            if ($action === 'closed' && $task->status !== 'closed') {
                $newStatus = 'closed';
                $msg = 'إغلاق المعاملة.';
            }

            // الأرشفة
            if ($action === 'archived' && $task->status !== 'archived') {
                $newStatus = 'archived';
                $msg = 'أرشفة المعاملة.';
            }

            return [
                'blocked'    => false,
                'newStatus'  => $newStatus,
                'msg'        => $msg,
                'notifyUser' => $notifyUser,
            ];
        }

        // 2) مدير الإدارة (Manager)
        if ($user->role === 'manager') {

            $taskAssigneeDept = $task->assignee?->department;
            // هل أنا مدير الإدارة اللي بتنفذ الشغل؟
            $isExecutorManager  = ($taskAssigneeDept && $user->department === $taskAssigneeDept);
            // هل أنا مدير الإدارة اللي طلبت الشغل؟
            $isRequesterManager = ($task->created_by === $user->id);

            // لازم المدير هذا يكون يا "مدير الجهة المنفذة" يا "مدير الجهة الطالبة" عشان يقدر يسوي أكشن
            if (!$isExecutorManager && !$isRequesterManager) {
                return ['blocked' => true, 'error' => 'غير مصرح لك.'];
            }

            // A) سيناريو المهام بين الإدارات 
            if ($isCrossDept) {

                // (1) دور مدير الإدارة المنفذة: يعتمد الشغل ويرسله للي طلبه 
                if ($isExecutorManager && in_array($task->status, ['reviewed', 'submitted'], true)) {

                    if ($action === 'send_to_requester') {
                        // نشيك هل حدد المرفقات النهائية؟ ما نخليه يرسل والملفات ناقصة
                        $hasSelection = DB::table('task_final_attachments')->where('task_id', $task->id)->exists();

                        if (!$hasSelection) {
                            return ['blocked' => true, 'error' => 'حدد المخرجات النهائية قبل الإرسال للجهة الطالبة.'];
                        }

                        $newStatus = 'waiting_requester';
                        $msg = 'تم اعتماد الإدارة المنفذة وبانتظار اعتماد الإدارة الطالبة.';
                        $notifyUser = $task->creator; // تنبيه يروح لمدير الجهة الطالبة
                    }

                    if ($action === 'returned') {
                        $newStatus = 'returned';
                        $msg = 'ملاحظات من مدير الإدارة المنفذة: إعادة للتعديل.';
                        $notifyUser = $task->assignee; // نرجعها للموظف يعدل
                    }
                }

                // (2) دور مدير الإدارة الطالبة: يشيك عالشغل ويعتمد (Completed) أو يرجعه
                if ($isRequesterManager && $task->status === 'waiting_requester') {

                    if ($action === 'approve') {
                        // مره آخرى  نتأكد ان فيه ملفات (احتياط)
                        $hasSelection = DB::table('task_final_attachments')->where('task_id', $task->id)->exists();

                        if (!$hasSelection) {
                            return ['blocked' => true, 'error' => 'لا يمكن الاعتماد قبل تحديد المخرجات النهائية.'];
                        }

                        $newStatus = 'completed';
                        $task->completion_percentage = 100;
                        $msg = 'تم اعتماد الجهة الطالبة وإغلاق المعاملة.';

                        // نبلغ مدير الجهة المنفذة ان شغلهم تم اعتماده
                        $notifyUser = User::where('department', $taskAssigneeDept)->where('role', 'manager')->first() ?? $task->assignee;
                    }

                    if ($action === 'returned') {
                        $newStatus = 'returned';
                        $task->completion_percentage = 0;
                        $msg = 'الجهة الطالبة أعادت المعاملة للإدارة المنفذة لعدم مطابقة المطلوب.';
                        // نرجعها لمدير الجهة المنفذة يشوف وش المشكلة
                        $notifyUser = User::where('department', $taskAssigneeDept)->where('role', 'manager')->first() ?? $task->assignee;
                    }
                }
            }


            // B) سيناريو داخل الإدارة الوحدة (داخلي)
            else {
                // المدير يراجع شغل موظفيه ويرفعه للـ CISO
                if (in_array($task->status, ['reviewed', 'submitted'], true)) {

                    if ($action === 'approve') {
                        $newStatus = 'endorsed';
                        $msg = 'تم اعتماد مدير الإدارة ورفعها للمدير العام (CISO) للاعتماد النهائي.';
                        $notifyUser = User::where('role', 'ciso')->first();
                    }

                    if ($action === 'returned') {
                        $newStatus = 'returned';
                        $msg = 'ملاحظات من مدير الإدارة: إعادة للتعديل.';
                        $notifyUser = $task->assignee;
                    }
                }
            }

            return [
                'blocked'    => false,
                'newStatus'  => $newStatus,
                'msg'        => $msg,
                'notifyUser' => $notifyUser,
            ];
        }

        // 3) قائد الفريق (Team Leader)
        if ($user->role === 'team_leader') {

            // القائد حده فريقه وقسمه، ما يتدخل برا
            if ($task->assignee && $user->department !== $task->assignee->department) {
                return ['blocked' => true, 'error' => 'غير مصرح.'];
            }

            if ($task->status === 'submitted') {
                if ($action === 'approve') {
                    $newStatus = 'reviewed';
                    $msg = 'تم اعتماد القائد ورفعها لمدير الإدارة.';
                    $notifyUser = User::where('department', $user->department)->where('role', 'manager')->first();
                }

                if ($action === 'returned') {
                    $newStatus = 'returned';
                    $msg = 'ملاحظات من القائد: إعادة للموظف.';
                    $notifyUser = $task->assignee;
                }
            }

            return [
                'blocked'    => false,
                'newStatus'  => $newStatus,
                'msg'        => $msg,
                'notifyUser' => $notifyUser,
            ];
        }

        // 4) الموظف (Employee)
        if ($user->role === 'employee') {

            // الموظف شغلته يخلص ويرفع (Submit)
            if ($action === 'submitted') {
                $newStatus = 'submitted';
                $msg = 'رفع المهمة للاعتماد.';

                // التنبيه يروح للقائد أول، لو ما فيه قائد يروح للمدير
                $notifyUser = User::where('department', $user->department)->where('role', 'team_leader')->first()
                    ?? User::where('department', $user->department)->where('role', 'manager')->first();
            }

            return [
                'blocked'    => false,
                'newStatus'  => $newStatus,
                'msg'        => $msg,
                'notifyUser' => $notifyUser,
            ];
        }

        return ['blocked' => true, 'error' => 'إجراء غير مسموح.'];
    }
}
