<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>التقرير التنفيذي</title>
    <style>
     
        body {
            font-family: 'DejaVu Sans', sans-serif;
        }

        /* العناوين الرئيسية */
        .header-main {
            background-color: #6f4e37;
            color: #ffffff;
            font-weight: bold;
            text-align: center;
            font-size: 14px;
            vertical-align: middle;
        }

        .header-sub {
            background-color: #f9f7f5;
            color: #6f4e37;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
        }

        /* رؤوس الجداول الفرعية */
        .section-header-kpi {
            background-color: #333333;
            color: #ffffff;
            font-weight: bold;
            text-align: center;
            border: 1px solid #000000;
        }

        .section-header-danger {
            background-color: #dc3545;
            color: #ffffff;
            font-weight: bold;
            text-align: center;
            border: 1px solid #000000;
        }

        /* خلايا البيانات */
        .th-cell {
            background-color: #e0e0e0;
            border: 1px solid #000000;
            font-weight: bold;
            text-align: center;
        }

        .td-cell {
            border: 1px solid #000000;
            text-align: center;
            vertical-align: middle;
        }

        /* ألوان النصوص */
        .text-success {
            color: #198754;
            font-weight: bold;
        }

        .text-danger {
            color: #dc3545;
            font-weight: bold;
        }

        .text-primary {
            color: #0d6efd;
            font-weight: bold;
        }
    </style>
</head>

<body>

    {{-- الجدول الأول: الترويسة والمؤشرات --}}
    <table>
        <thead>
            <tr>
                <td colspan="6" class="header-main" height="30">
                    المملكة العربية السعودية - وزارة الشؤون البلدية والقروية والإسكان
                </td>
            </tr>
            <tr>
                <td colspan="6" class="header-sub" height="25">
                    الإدارة العامة للأمن السيبراني - التقرير التنفيذي الشامل
                </td>
            </tr>
            <tr>
                <td colspan="6" style="text-align: right; color: #666666;">
                    تاريخ التقرير: {{ $date }}
                </td>
            </tr>
            <tr>
                <td colspan="6"></td>
            </tr> {{-- سطر فارغ --}}
        </thead>

        <tbody>
            {{-- قسم المؤشرات (KPIs) --}}
            <tr>
                <td colspan="6" class="section-header-kpi" height="25">
                    أولاً: مؤشرات الأداء العامة
                </td>
            </tr>
            <tr>
                <th class="th-cell">نسبة الامتثال</th>
                <th class="th-cell">نسبة المخاطر</th>
                <th class="th-cell">الإنجاز</th>
                <th class="th-cell">متوسط زمن الحل</th> {{-- تمت إضافة العنوان --}}
                <th class="th-cell">المهام المغلقة</th>
                <th class="th-cell">المهام المؤرشفة</th>
            </tr>
            <tr>
                <td class="td-cell text-success">{{ $complianceRate }}%</td>
                <td class="td-cell text-danger">{{ $overdueRate }}%</td>
                <td class="td-cell text-primary">{{ $completionRate }}%</td>
                <td class="td-cell">{{ round($avgResolutionTime ?? 0) }} يوم</td>
                <td class="td-cell">{{ $closedCount ?? 0 }}</td>
                <td class="td-cell">{{ $archivedCount ?? 0 }}</td>
            </tr>
        </tbody>
    </table>

    <br>

    {{-- الجدول الثاني: المهام المتأخرة --}}
    <table>
        <thead>
            <tr>
                <td colspan="6" class="section-header-danger" height="25">
                    ثانياً: المهام المتأخرة (Requires Attention)
                </td>
            </tr>
            <tr>
                {{-- تم توزيع colspan ليتناسب مع المجموع 6 --}}
                <th colspan="2" class="th-cell" style="background-color: #f8d7da; color: #842029;">عنوان المهمة</th>
                <th class="th-cell" style="background-color: #f8d7da; color: #842029;">المسؤول</th>
                <th class="th-cell" style="background-color: #f8d7da; color: #842029;">تاريخ الاستحقاق</th>
                <th class="th-cell" style="background-color: #f8d7da; color: #842029;">الأولوية</th>
                <th class="th-cell" style="background-color: #f8d7da; color: #842029;">التأخير (يوم)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($overdueList as $task)
            <tr>
                <td colspan="2" class="td-cell" style="text-align: right;">{{ $task->title }}</td>
                <td class="td-cell">{{ $task->assignee->name ?? '-' }}</td>
                <td class="td-cell">{{ $task->due_date }}</td>
                <td class="td-cell">{{ $task->priority }}</td>
                <td class="td-cell text-danger">
                    {{ round(\Carbon\Carbon::parse($task->due_date)->diffInDays(now())) }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="td-cell text-success" height="30">
                    لا توجد مهام متأخرة. العمل يسير بانتظام.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

</body>

</html>