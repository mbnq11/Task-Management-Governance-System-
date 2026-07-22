<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>

<body>
    <table>
        <thead>
            <tr>
                <td colspan="7" style="background-color: #6f4e37; color: #ffffff; font-weight: bold; text-align: center; height: 35px; font-size: 14px; border: 1px solid #000000;">
                    تفاصيل أداء فريق العمل 
                </td>
            </tr>
            <tr style="background-color: #e0e0e0; color: #000000; height: 30px; text-align: center;">
                <th style="border: 1px solid #000000; font-weight: bold; width: 30px;">الموظف</th>
                <th style="border: 1px solid #000000; font-weight: bold; width: 25px;">القسم</th>
                <th style="border: 1px solid #000000; font-weight: bold; width: 15px;">إجمالي المهام</th>
                <th style="border: 1px solid #000000; font-weight: bold; width: 15px; color: #198754;">منجز</th>
                <th style="border: 1px solid #000000; font-weight: bold; width: 15px; color: #0d6efd;">جاري</th>
                <th style="border: 1px solid #000000; font-weight: bold; width: 15px; color: #dc3545;">متأخرة</th>
                <th style="border: 1px solid #000000; font-weight: bold; width: 20px;">نسبة الإنجاز</th>
            </tr>
        </thead>
        <tbody>
            @foreach($employees as $emp)
            @php
            $bg = $loop->iteration % 2 == 0 ? '#f9f7f5' : '#ffffff';

            $rateColor = '#000000';
            $rateBg = $bg;

            if($emp['rate'] >= 90) {
            $rateColor = '#198754'; // أخضر للممتاز
            $rateBg = '#d1e7dd';
            } elseif($emp['rate'] < 50) {
                $rateColor='#dc3545' ; // أحمر للضعيف
                $rateBg='#f8d7da' ;
                } elseif($emp['rate'] < 80) {
                $rateColor='#fd7e14' ; // برتقالي للمتوسط
                $rateBg='#fff3cd' ;
                }
                @endphp

                <tr style="background-color: {{ $bg }}; text-align: center;">
                <td style="border: 1px solid #cccccc; text-align: right; font-weight: bold;">
                    {{ $emp['name'] }}
                </td>

                <td style="border: 1px solid #cccccc; color: #555555;">
                    {{ $emp['department'] }}
                </td>

                <td style="border: 1px solid #cccccc; font-weight: bold;">
                    {{ $emp['total'] }}
                </td>

                <td style="border: 1px solid #cccccc; color: #198754; font-weight: bold; background-color: #d1e7dd;">
                    {{ $emp['completed'] }}
                </td>

                <td style="border: 1px solid #cccccc; color: #0d6efd;">
                    {{ $emp['in_progress'] }}
                </td>

                <td style="border: 1px solid #cccccc; color: #dc3545; font-weight: bold; background-color: {{ $emp['overdue'] > 0 ? '#f8d7da' : $bg }};">
                    {{ $emp['overdue'] }}
                </td>

                <td style="border: 1px solid #cccccc; color: {{ $rateColor }}; font-weight: bold; background-color: {{ $rateBg }};">
                    {{ $emp['rate'] }}%
                </td>
                </tr>
                @endforeach
        </tbody>
    </table>
</body>

</html>