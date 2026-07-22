<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التقرير العام للأداء</title>

    <style>

    </style>
</head>

<body>
    @vite('resources/css/general_report.css')
    {{-- زر الطباعة --}}
    <div class="print-btn-container">
        <button onclick="window.print()" class="print-btn">طباعة التقرير</button>
    </div>

    <div class="page">

        {{-- الجدول الرئيسي لاحتواء الصفحة --}}
        <table style="width: 100%; border: none; border-collapse: separate; border-spacing: 0;">

            {{-- 1. الترويسة --}}
            <thead>
                <tr>
                    <td style="border: none; padding: 0;">
                        <table class="header-table">
                            <tr>
                                <td width="35%" style="border: none;">
                                    <div class="header-title">المملكة العربية السعودية</div>
                                    <div class="header-title">وزارة الشؤون البلدية والقروية والإسكان</div>
                                    <div class="header-title">الإدارة العامة للأمن السيبراني</div>
                                    <div style="margin-top: 5px; color: #777;">تقرير متابعة الأداء والمهام</div>
                                </td>
                                <td width="30%" class="logo-container" style="border: none;">
                                    <img src="{{ asset('images/Aseer-logo-dark.png') }}" alt="Logo">

                                </td>
                                <td width="35%" style="text-align: left; border: none;">
                                    <div class="info-box">
                                        <div style="margin-bottom:4px;"><strong>تاريخ الطباعة:</strong> {{ date('Y-m-d') }}</div>
                                        <div style="margin-bottom:4px;"><strong>المستخدم:</strong> {{ auth()->user()->name ?? 'System' }}</div>
                                        <div><strong>حالة التقرير:</strong> نهائي</div>
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
                    <td style="border: none; padding: 0;">


                        {{-- القسم الثاني: تفاصيل المهام --}}
                        <div class="section-title"> تفاصيل المهام والمشاريع</div>
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th width="4%">#</th>
                                    <th width="28%">عنوان المهمة</th>
                                    <th width="14%">المسؤول</th>
                                    <th width="12%">الإنجاز</th>
                                    <th width="10%">تاريخ الاستحقاق</th>
                                    <th width="10%">المدة المتبقية</th>
                                    <th width="9%">الحالة</th>
                                    <th width="9%">الأولوية</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($allTasks as $index => $task)
                                @php
                                $percent = $task->completion_percentage ?? 0;
                                if(in_array($task->status, ['completed', 'closed'])) $percent = 100;

                                $dueDate = !empty($task->due_date) ? \Carbon\Carbon::parse($task->due_date) : null;
                                $now = \Carbon\Carbon::now();
                                $diff = $dueDate ? $now->diffInDays($dueDate, false) : null;

                                $prio = trim((string)($task->priority ?? 'low'));
                                @endphp
                                <tr>
                                    <td>{{ $index + 1 }}</td>

                                    <td class="text-start">
                                        <div style="font-weight:bold; color:#333;">{{ \Illuminate\Support\Str::limit($task->title, 45) }}</div>
                                        <div style="color:#777; font-size:9px;">{{ $task->code ?? 'TASK-'.$task->id }}</div>
                                    </td>

                                    <td>
                                        <div style="font-weight: bold; color: #333;">
                                            {{ $task->assignee->name ?? 'غير محدد' }}
                                        </div>
                                        <div style="font-size: 9px; color: #777; margin-top: 2px;">
                                            {{ $task->assignee->department->name ?? $task->assignee->department ?? '' }}
                                        </div>
                                    </td>
                                    {{-- ================================================= --}}

                                    <td>
                                        <div style="font-size:9px; margin-bottom:2px;">{{ $percent }}%</div>
                                        <div class="progress-container">
                                            <div class="progress-bar" style="width: {{ $percent }}%; 
                                                background-color: {{ $percent < 50 ? '#392015;' : ($percent < 100 ? '#392015;' : '#392015;') }};">
                                            </div>
                                        </div>
                                    </td>

                                    <td style="direction:ltr;">{{ $task->due_date ?? '-' }}</td>

                                    <td>
                                        @if(is_null($diff))
                                        <span style="color:#ccc;">-</span>
                                        @elseif($diff < 0)
                                            <span style="color:#392015; font-weight:bold;">+{{ abs((int)$diff) }} يوم</span>
                                            <div style="font-size:8px; color:#392015;">(متأخر)</div>
                                            @elseif($diff == 0)
                                            <span style="color:#fd7e14;">اليوم</span>
                                            @else
                                            <span style="color:#392015;">{{ (int)$diff }} يوم</span>
                                            @endif
                                    </td>

                                    <td>
                                        @if(in_array($task->status, ['completed','closed']))
                                        <span class="status-dot dot-green"></span> منجز
                                        @elseif(in_array($task->status, ['overdue']))
                                        <span class="status-dot dot-red"></span> متأخر
                                        @else
                                        <span class="status-dot dot-blue"></span> جاري
                                        @endif
                                    </td>

                                    <td>
                                        @if(in_array($prio, ['critical','حرج']) || $task->priority == 4)
                                        <span class="badge bg-danger">حرج</span>
                                        @elseif(in_array($prio, ['high','urgent','عالية','عاجلة']) || $task->priority == 3)
                                        <span class="badge bg-high">عالية</span>
                                        @elseif(in_array($prio, ['medium','متوسطة']) || $task->priority == 2)
                                        <span class="badge bg-med">متوسطة</span>
                                        @else
                                        <span class="badge bg-low">منخفضة</span>
                                        @endif
                                    </td>

                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" style="padding: 20px; color:#777;">لا توجد مهام مسجلة في هذا التقرير.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>

                    </td>
                </tr>
            </tbody>
        </table>

        {{-- الفوتر الثابت --}}
        <div class="footer-fixed">
            نظام الإدارة العامة للأمن السيبراني | تم استخراج التقرير بتاريخ {{ date('Y-m-d H:i') }}
        </div>

    </div>
</body>

</html>