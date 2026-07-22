<?php
// هذا الملف للتعليقات والمرفقات وتحديد المخرجات النهائية.
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Task;
use App\Services\Tasks\TaskActivityService;

class TaskCommentController extends Controller
{
    public function __construct(
        private TaskActivityService $activity
    ) {}

    // إضافة تعليق ومرفق
    public function storeComment(Request $request, $task_id)
    {
        $request->validate([
            'comment' => 'required|string',
            'attachment' => 'nullable|file',
        ]);

        $task = Task::with(['team'])->findOrFail($task_id);
        $user = auth()->user();

        // لو المعاملة تنفيذية (خاصة)
        if (($task->task_type ?? 'workflow') === 'executive' && !in_array($user->role, ['ciso', 'manager'], true)) {
            return back()->with('error', 'غير مصرح لك.');
        }

        $path = null;
        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('attachments/work', 'public');
        }

        $task->comments()->create([
            'user_id' => $user->id,
            'comment' => $request->comment,
            'attachment' => $path,
        ]);

        return back()->with('success', 'تم التعليق.');
    }

    // تحديد المرفقات النهائية (للمعاملات بين الإدارات)
    public function setFinalAttachments(Request $request, Task $task)
    {
        $user = auth()->user();

        $task->loadMissing(['creator', 'assignee', 'comments']);

        if (($task->task_type ?? 'workflow') === 'executive') abort(403);

        $isCrossDepartment = (
            $task->creator && $task->assignee &&
            $task->creator->department !== $task->assignee->department &&
            ($task->creator->role !== 'ciso')
        );

        $taskAssigneeDept = $task->assignee?->department;

        $isExecutorManager = (
            $user->role === 'manager'
            && $taskAssigneeDept
            && $user->department === $taskAssigneeDept
        );

        if (!$isCrossDepartment || !$isExecutorManager) abort(403);

        if (!in_array($task->status, ['reviewed', 'submitted'], true)) {
            return back()->with('error', 'لا يمكن تحديد المخرجات النهائية في هذه الحالة.');
        }

        $commentIds = $request->input('final_comment_ids', []);
        if (!is_array($commentIds)) $commentIds = [];

        $validIds = $task->comments()
            ->whereNotNull('attachment')
            ->whereIn('id', $commentIds)
            ->pluck('id')
            ->toArray();

        DB::transaction(function () use ($task, $user, $validIds) {
            DB::table('task_final_attachments')
                ->where('task_id', $task->id)
                ->delete();

            if (count($validIds)) {
                $rows = array_map(fn($cid) => [
                    'task_id' => $task->id,
                    'comment_id' => $cid,
                    'selected_by' => $user->id,
                    'created_at' => now(),
                ], $validIds);

                DB::table('task_final_attachments')->insert($rows);
            }
        });

        return back()->with('success', 'تم تحديد المخرجات النهائية بنجاح.');
    }
    public function viewAttachment(Task $task, $commentId)
{
    $user = auth()->user();

    // تحميل العلاقات اللي نحتاجها للتحقق
    $task->loadMissing(['team', 'comments']);

    if (($task->task_type ?? 'workflow') === 'executive' && !in_array($user->role, ['ciso', 'manager'], true)) {
        abort(403);
    }

    // نتأكد إن التعليق تابع لنفس المهمة
    $comment = $task->comments()->where('id', $commentId)->firstOrFail();

    // نتأكد فيه مرفق
    if (!$comment->attachment) abort(404);

    // نتأكد الملف موجود فعليًا
    if (!Storage::disk('public')->exists($comment->attachment)) abort(404);

    // عرض داخل المتصفح (PDF/صور…)
    return response()->file(Storage::disk('public')->path($comment->attachment));

}

}