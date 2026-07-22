<?php

namespace App\Services\Tasks;

use App\Models\Task;
use Illuminate\Support\Facades\DB;

class TaskDashboardService
{
    /**
     * يجيب إحصائيات المهام مع الفلاتر (الربع السنوي، الأولوية، الموظف)
     */
    public function getTaskStatistics(array $filters = [])
    {
        $query = Task::query();
        
        // العلاقات الضرورية عشان ما نسوي استعلامات زايدة 
        $query->with(['assignee:id,name,department', 'team:id,name', 'creator:id,name']);

        // فلتر الربع السنوي 
        // الفكرة هنا نحول رقم الربع (1, 2, 3, 4) إلى أشهر البداية والنهاية
        if (isset($filters['quarter'])) {
            [$year, $q] = explode('-', $filters['quarter']);
            $startMonth = ($q - 1) * 3 + 1; // مثلا الربع الثاني: (2-1)*3 + 1 = شهر 4

            $query->whereYear('created_at', $year)
                ->whereRaw('MONTH(created_at) BETWEEN ? AND ?', [$startMonth, $startMonth + 2]);
        }

        // فلتر الأولوية 
        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        // فلتر الموظف (للموظفين العاديين عشان يشوفون مهامهم بس)
        if (isset($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        return $query->get();
    }

    /**
     * يستخدم في الرسم البياني أو الإحصائيات السريعة
     */
    public function tasksByDomain()
    {
        return Task::select('sub_category', DB::raw('count(*) as total'))
            ->groupBy('sub_category')
            ->pluck('total', 'sub_category');
    }
}