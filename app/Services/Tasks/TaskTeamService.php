<?php

namespace App\Services\Tasks;

use App\Models\Task;

class TaskTeamService
{
    /**
     * يضيف موظف لفريق العمل
     */
    public function addTeamMember(Task $task, int $userId): void
    {
        $task->team()->syncWithoutDetaching([$userId]);
    }

    /**
     * يشيل الموظف من الفريق ( إزالة)
     */
    public function removeTeamMember(Task $task, int $userId): void
    {
        $task->team()->detach($userId);
    }
}