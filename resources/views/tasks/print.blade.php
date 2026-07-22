<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقرير مهمة #{{ $task->code ?? $task->id }}</title>

    @vite('resources/css/report.css')
</head>

<body>

    <div class="print-btn-container">
        <button onclick="window.print()" class="print-btn">طباعة التقرير</button>
    </div>

    <div class="page">
        <table style="width: 100%; border: none;">

            <thead>
                <tr>
                    <td style="border: none;">
                        <table class="header-table">
                            <tr>
                                <td width="35%">
                                    <div class="header-title">المملكة العربية السعودية</div>
                                    <div class="header-title">وزارة الشؤون البلدية والقروية والإسكان</div>
                                    <div class="header-title">الإدارة العامة للأمن السيبراني</div>
                                    <div style="margin-top: 5px; color: #777;">تقرير تفاصيل المهمة</div>
                                </td>
                                <td width="30%" class="logo-container">
                                    <img src="{{ asset('images/Aseer-logo-dark.png') }}" alt="Logo">
                                </td>
                                <td width="35%" style="text-align: left;">
                                    <div class="info-box">
                                        <div style="margin-bottom: 5px;"><strong>رقم المهمة:</strong> #{{ $task->code ?? $task->id }}</div>
                                        <div style="margin-bottom: 5px;"><strong>تاريخ الإنشاء:</strong> {{ $task->created_at->format('Y-m-d') }}</div>
                                        <div><strong>الحالة:</strong>
                                            @if(in_array($task->status, ['completed', 'closed'])) <span style="color:#392015; font-weight:bold;">مكتملة</span>
                                            @elseif($task->status == 'overdue') <span style="color:#392015; font-weight:bold;">متأخرة</span>
                                            @else <span style="color:#392015; font-weight:bold;">{{ $task->status }}</span> @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </table>
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

                        {{-- 1. البيانات الأساسية --}}
                        <div class="section-title">1. بيانات المهمة الأساسية</div>
                        <table class="custom-table">
                            <tr>
                                <th width="15%">عنوان المهمة</th>
                                <td colspan="3" style="text-align: right; padding-right: 10px; font-weight: bold;">
                                    {{ $task->title }}
                                </td>
                            </tr>
                            <tr>
                                <th width="15%">التصنيف</th>
                                <td width="35%">{{ $task->sub_category ?? $task->category->name ?? 'عام' }}</td>
                                <th width="15%">تاريخ الاستحقاق</th>
                                <td width="35%" style="direction: ltr;">{{ $task->due_date ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>الأولوية</th>
                                <td>
                                    @php $prio = $task->priority; @endphp
                                    @if(in_array($prio, ['high', 'critical', 'urgent', 'عالية'])) <span class="badge bg-high">عالية</span>
                                    @elseif(in_array($prio, ['medium', 'متوسطة'])) <span class="badge bg-med">متوسطة</span>
                                    @else <span class="badge bg-low">منخفضة</span> @endif
                                </td>
                                <th>التعقيد</th>
                                <td>{{ $task->complexity ?? 'عادي' }}</td>
                            </tr>
                        </table>

                        {{-- 2. الأطراف والمسؤوليات --}}
                        <div class="section-title">2. الأطراف والمسؤوليات</div>
                        <table class="custom-table">
                            <tr>
                                <th width="15%">الجهة الطالبة</th>
                                <td width="35%">
                                    <strong>{{ $task->creator->name ?? 'النظام' }}</strong>
                                    <div style="font-size: 9px; color: #666;">
                                        {{ $task->creator->department->name ?? $task->creator->department ?? '' }}
                                    </div>
                                </td>
                                <th width="15%">المسؤول المباشر</th>
                                <td width="35%">
                                    <strong>{{ $task->assignee->name ?? 'غير محدد' }}</strong>
                                    <div style="font-size: 9px; color: #666;">
                                        {{ $task->assignee->department->name ?? $task->assignee->department ?? '' }}
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th>فريق المساندة</th>
                                <td colspan="3" style="text-align: right; padding: 8px;">
                                    @if(isset($task->team) && count($task->team) > 0)
                                    @foreach($task->team as $member)
                                    <span class="team-badge">{{ $member->name }}</span>
                                    @endforeach
                                    @else
                                    <span style="color: #999;">لا يوجد أعضاء إضافيين</span>
                                    @endif
                                </td>
                            </tr>
                        </table>

                        {{-- 3. الوصف --}}
                        <div class="section-title">3. تفاصيل ووصف المهمة</div>
                        <div class="content-area">
                            {!! nl2br(e($task->description ?? '')) !!}
                        </div>

                        {{-- التوقيعات --}}
                        <table style="width: 100%; margin-top: 50px; page-break-inside: avoid;">
                            <tr>
                                <td width="50%" style="text-align: center; border: none;">
                                    <div style="font-weight: bold; margin-bottom: 40px; text-decoration: underline;">مدير الإدارة</div>
                                    <div>{{ $task->creator->name ?? '____________________' }}</div>
                                </td>
                                <td width="50%" style="text-align: center; border: none;">
                                    <div style="font-weight: bold; margin-bottom: 40px; text-decoration: underline;">الموظف المسؤول</div>
                                    <div>{{ $task->assignee->name ?? '____________________' }}</div>
                                </td>
                            </tr>
                        </table>

                    </td>
                </tr>
            </tbody>
        </table>

        <div class="footer-fixed">
            نظام الإدارة العامة للأمن السيبراني | تم استخراج التقرير بتاريخ {{ date('Y-m-d H:i') }}
        </div>
    </div>

</body>

</html>