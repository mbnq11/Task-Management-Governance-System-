<?php

namespace App\Support\Tasks;

trait TaskHelpers
{
    private function getStatusName($status) {
        return match($status) {
            'submitted' => 'بانتظار اعتماد القائد',
            'reviewed'  => 'بانتظار اعتماد المدير',
            'endorsed'  => 'بانتظار اعتماد المدير العام',
            'returned'  => 'معادة للموظف للتعديل',
            'completed' => 'معتمدة / منجزة',
            'closed'    => 'مغلقة',
            'archived'  => 'مؤرشفة',
            'in_progress' => 'قيد التنفيذ',
            default => 'في الانتظار'
        };
    }

    private function isCrossDepartment($task) {
        if (!$task->creator || !$task->assignee) return false;
        return $task->creator->department !== $task->assignee->department;
    }
}
