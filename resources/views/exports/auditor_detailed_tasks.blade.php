<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>تقرير المهام</title>
    
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; }
        table { width: 100%; border-collapse: collapse; direction: rtl; }
        
        th, td { border: 1px solid #000000; padding: 8px; text-align: center; font-size: 12px; }
        
        thead tr { background-color: #6f4e37; color: #ffffff; height: 40px; }
        thead th { font-weight: bold; font-size: 14px; }

        tbody tr:nth-child(even) { background-color: #f9f7f5; }

        /* === ألوان الحالات (Visual Statuses) === */
        .status-success   { color: #198754; font-weight: bold; } /* أخضر */
        .status-primary   { color: #0d6efd; font-weight: bold; } /* أزرق */
        .status-info      { color: #0dcaf0; font-weight: bold; } /* سماوي */
        .status-warning   { color: #d68c08; font-weight: bold; } /* برتقالي */
        .status-danger    { color: #dc3545; font-weight: bold; } /* أحمر */
        .status-secondary { color: #6c757d; font-weight: bold; } /* رمادي */

        .date-overdue { color: #dc3545; font-weight: bold; text-decoration: underline; }
    </style>
</head>
<body>

    {{-- تعريف مصفوفات الترجمة --}}
    @php
        // 1. ترجمة الحالات (حسب طلبك)
        $statusLabels = [
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

        // 2. ترجمة الأولويات (إضافة مهمة عشان الجدول يكون عربي 100%)
        $priorityLabels = [
            'low'      => 'منخفضة',
            'medium'   => 'متوسطة',
            'high'     => 'عالية',
            'critical' => 'حرجة جداً',
        ];

        // 3. ألوان الحالات
        $statusColors = [
            'pending'           => 'status-secondary',
            'in_progress'       => 'status-primary',
            'submitted'         => 'status-info',
            'reviewed'          => 'status-info',
            'returned'          => 'status-danger',
            'waiting_requester' => 'status-warning',
            'completed'         => 'status-success',
            'closed'            => 'status-success',
            'archived'          => 'status-secondary',
            'endorsed'          => 'status-primary',
        ];
    @endphp

    <table>
        <thead>
            <tr>
                <th style="width: 40px;">#</th>
                <th>عنوان المهمة</th>
                <th>المسؤول</th>
                <th style="width: 130px;">الحالة</th>
                <th style="width: 90px;">الأولوية</th>
                <th style="width: 90px;">تاريخ الإنشاء</th>
                <th style="width: 90px;">تاريخ الاستحقاق</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tasks as $task)
                @php
                    // جلب النصوص العربية
                    $statusText   = $statusLabels[$task->status] ?? $task->status;
                    $priorityText = $priorityLabels[$task->priority] ?? $task->priority;
                    
                    // جلب اللون
                    $statusClass  = $statusColors[$task->status] ?? '';

                    // التحقق من التأخير
                    $isOverdue = false;
                    if($task->due_date < now() && !in_array($task->status, ['completed', 'closed', 'archived'])) {
                        $isOverdue = true;
                    }
                @endphp

                <tr>
                    <td>{{ $task->id }}</td>
                    <td style="text-align: right;">{{ $task->title }}</td>
                    <td>{{ $task->assignee->name ?? '-' }}</td>
                    
                    {{-- الحالة معربة وملونة --}}
                    <td class="{{ $statusClass }}">
                        {{ $statusText }}
                    </td>

                    {{-- الأولوية معربة --}}
                    <td>{{ $priorityText }}</td>

                    <td>{{ $task->created_at->format('Y-m-d') }}</td>
                    
                    <td class="{{ $isOverdue ? 'date-overdue' : '' }}">
                        {{ $task->due_date }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>