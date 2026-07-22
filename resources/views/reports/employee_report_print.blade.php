<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقرير أداء - {{ $user->name ?? 'الموظف' }}</title>

</head>

<body>
    @vite('resources/css/employee_report.css')
    @php
    $tasksCol = $tasks instanceof \Illuminate\Support\Collection ? $tasks : collect($tasks);


    $findDepartmentName = function($model) {
    if (!$model) return null;


    if (!empty($model->department) && is_object($model->department)) return $model->department->name ?? $model->department->title ?? null;
    if (!empty($model->dept) && is_object($model->dept)) return $model->dept->name ?? $model->dept->title ?? null;
    if (!empty($model->section) && is_object($model->section)) return $model->section->name ?? null;

    // نحاول الوصول لـ department, department_name, dept_name
    if (!empty($model->department) && is_string($model->department)) return $model->department;
    if (!empty($model->department_name)) return $model->department_name;
    if (!empty($model->dept_name)) return $model->dept_name;
    if (!empty($model->section_name)) return $model->section_name;

    return null;
    };

    $headerDept = $findDepartmentName($user) ?? 'غير محدد';

    if(!isset($stats) || !is_array($stats)){
    $total = $tasksCol->count();
    $completed = $tasksCol->whereIn('status', ['completed','closed'])->count();
    $overdue = $tasksCol->whereIn('status', ['overdue','returned'])->count();
    $rate = $total ? round(($completed / $total) * 100) : 0;
    $stats = ['total' => $total, 'completed' => $completed, 'overdue' => $overdue, 'rate' => $rate];
    }

    if(!isset($prioritySummary) || !is_array($prioritySummary)){
    $prioritySummary = [
    'critical' => $tasksCol->whereIn('priority', ['critical','حرج', 4])->count(),
    'high' => $tasksCol->whereIn('priority', ['high','urgent','عالية','عاجلة', 3])->count(),
    'medium' => $tasksCol->whereIn('priority', ['medium','متوسطة', 2])->count(),
    'low' => $tasksCol->whereIn('priority', ['low','منخفضة', 1])->count(),
    ];
    } else {
    $prioritySummary['critical'] = $prioritySummary['critical'] ?? 0;
    $prioritySummary['high'] = ($prioritySummary['high'] ?? 0) + ($prioritySummary['urgent'] ?? 0);
    $prioritySummary['medium'] = $prioritySummary['medium'] ?? 0;
    $prioritySummary['low'] = $prioritySummary['low'] ?? 0;
    }
    @endphp

    <div class="print-btn-container">
        <button onclick="window.print()" class="print-btn">طباعة التقرير</button>
    </div>

    <div class="page">

        <table style="width: 100%; border: none;">

            {{-- (1) الترويسة --}}
            <thead>
                <tr>
                    <td style="border: none;">
                        <table class="header-table">
                            <tr>
                                <td width="35%" style="border: none;">
                                    <div class="header-title">المملكة العربية السعودية</div>
                                    <div class="header-title">وزارة الشؤون البلدية والقروية والإسكان</div>
                                    <div class="header-title">الإدارة العامة للأمن السيبراني</div>
                                    <div style="margin-top: 5px; color: #777;">تقرير أداء الموظف</div>
                                </td>
                                <td width="30%" class="logo-container" style="border: none;">
                                    <img src="{{ asset('images/Aseer-logo-dark.png') }}" alt="Logo">
                                </td>
                                <td width="35%" style="text-align: left; border: none;">
                                    <div class="info-box">
                                        <div style="color:#6f4e37; font-weight:bold; margin-bottom: 5px; font-size:12px;">{{ $user->name ?? 'غير معروف' }}</div>
                                        <div style="margin-bottom: 4px;"><strong>القسم:</strong> {{ $headerDept }}</div>
                                        <div style="border-top: 1px dashed #ccc; padding-top: 4px;">
                                            <strong>تاريخ التقرير:</strong> {{ date('Y-m-d') }}
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </table>
                        <div style="height: 10px;"></div>
                    </td>
                </tr>
            </thead>

            <tfoot>
                <tr>
                    <td style="border: none;">
                        <div class="footer-space"></div>
                    </td>
                </tr>
            </tfoot>

            <tbody>
                <tr>
                    <td style="border: none;">


                        {{-- 1. الأولويات --}}
                        <div class="section-title">1. ملخص الأولويات</div>

                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>حرج</th>
                                    <th>عالية</th>
                                    <th>متوسطة</th>
                                    <th>منخفضة</th>
                                    <th>المجموع</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                $total =
                                ($prioritySummary['critical'] ?? 0) +
                                ($prioritySummary['high'] ?? 0) +
                                ($prioritySummary['medium'] ?? 0) +
                                ($prioritySummary['low'] ?? 0);
                                @endphp
                                <tr>
                                    <td><strong style="color:#392015;">{{ $prioritySummary['critical'] ?? 0 }}</strong></td>
                                    <td><strong style="color:#392015;">{{ $prioritySummary['high'] ?? 0 }}</strong></td>
                                    <td><strong style="color:#392015;">{{ $prioritySummary['medium'] ?? 0 }}</strong></td>
                                    <td><strong style="color:#392015;">{{ $prioritySummary['low'] ?? 0 }}</strong></td>
                                    <td><strong style="color:#392015;">{{ $total }}</strong></td>
                                </tr>
                            </tbody>
                        </table>


                        {{-- 2. التفاصيل --}}
                        <div class="section-title">2. تفاصيل المهام والمشاريع</div>
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th width="4%">#</th>
                                    <th width="25%">عنوان المهمة</th>
                                    <th width="12%">القسم</th>
                                    <th width="12%">المسؤول</th>
                                    <th width="12%">الإنجاز</th>
                                    <th width="10%">الاستحقاق</th>
                                    <th width="10%">المتبقي</th>
                                    <th width="8%">الحالة</th>
                                    <th width="7%">الأولوية</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tasksCol as $index => $task)
                                @php
                                $percent = $task->completion_percentage ?? 0;
                                if(in_array($task->status, ['completed','closed'])) $percent = 100;

                                $now = \Carbon\Carbon::now();
                                $dueDate = !empty($task->due_date) ? \Carbon\Carbon::parse($task->due_date) : null;
                                $diff = $dueDate ? $now->diffInDays($dueDate, false) : null;


                                $taskDept = $findDepartmentName($task);
                                if(!$taskDept && !empty($task->assignee)) {
                                $taskDept = $findDepartmentName($task->assignee);
                                }
                                $taskDept = $taskDept ?? '-';

                                $assigneeName = $task->assignee->name ?? $task->assignee_name ?? '-';

                                $prio = trim((string)($task->priority ?? 'low'));
                                @endphp
                                <tr>
                                    <td>{{ $index + 1 }}</td>

                                    <td class="text-start">
                                        <div style="font-weight:bold; color:#333;">{{ \Illuminate\Support\Str::limit($task->title ?? '-', 35) }}</div>
                                        <div style="color:#777; font-size:9px;">{{ $task->code ?? 'TASK-'.$task->id }}</div>
                                    </td>

                                    {{-- عمود القسم --}}
                                    <td>{{ \Illuminate\Support\Str::limit($taskDept, 15) }}</td>

                                    {{-- عمود المسؤول --}}
                                    <td>{{ \Illuminate\Support\Str::limit($assigneeName, 40) }}</td>

                                    <td>
                                        <div style="font-size:9px; margin-bottom:2px;">{{ $percent }}%</div>
                                        <div class="progress-container">
                                            <div class="progress-bar" style="width: {{ $percent }}%; background-color: {{ $percent < 50 ? '#392015' : ($percent < 100 ? '#392015' : '#392015') }};"></div>
                                        </div>
                                    </td>

                                    <td style="direction:ltr;">{{ $task->due_date ?? '-' }}</td>

                                    <td>
                                        @if(is_null($diff)) <span style="color:#ccc;">-</span>
                                        @elseif($diff < 0) <span style="color:#dc3545; font-weight:bold;">+{{ abs((int)$diff) }} يوم</span>
                                            @elseif($diff == 0) <span style="color:#fd7e14;">اليوم</span>
                                            @else <span style="color:#392015;">{{ (int)$diff }} يوم</span> @endif
                                    </td>

                                    <td>
                                        @if(in_array($task->status, ['completed','closed'])) <span class="status-dot dot-green"></span> منجز
                                        @elseif(in_array($task->status, ['overdue','returned'])) <span class="status-dot dot-red"></span> متأخر
                                        @else <span class="status-dot dot-blue"></span> جاري @endif
                                    </td>

                                    <td>
                                        @if(in_array($prio, ['critical','حرج']) || $task->priority == 4) <span class="badge bg-danger">حرج</span>
                                        @elseif(in_array($prio, ['high','urgent','عالية','عاجلة']) || $task->priority == 3) <span class="badge bg-high">عالية</span>
                                        @elseif(in_array($prio, ['medium','متوسطة']) || $task->priority == 2) <span class="badge bg-med">متوسطة</span>
                                        @else <span class="badge bg-low">منخفضة</span> @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" style="padding: 20px; color:#777;">لا توجد مهام مسجلة.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>

                    </td>
                </tr>
            </tbody>
        </table>

    </div>
</body>

</html>