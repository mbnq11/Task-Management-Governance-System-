<?php

namespace App\Services\Tasks;

use App\Models\TaskComment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class TaskActivityService
{
    public function addSystemComment($task, $msg): void
    {
        TaskComment::create([
            'task_id' => $task->id,
            'user_id' => Auth::id(),
            'comment' => '[نظام] ' . $msg
        ]);
    }

    public function sendNotificationEmail($task, $subject, $toUser): void
    {
        if (!$toUser || empty($toUser->email)) return;

        try {
            Mail::raw("تحديث المهمة #{$task->id}: $subject", function ($message) use ($toUser, $subject) {
                $message->to($toUser->email)->subject("نظام المهام: $subject");
            });
        } catch (\Exception $e) {
            \Log::error("Email Error: " . $e->getMessage());
        }
    }
}
