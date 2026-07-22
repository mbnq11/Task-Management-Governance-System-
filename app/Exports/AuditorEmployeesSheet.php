<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Border;

class AuditorEmployeesSheet implements FromView, WithStyles, WithTitle, WithEvents
{
    protected $employees;

    public function __construct($employees)
    {
        $this->employees = $employees;
    }

    public function view(): View
    {
        return view('exports.auditor_employees_sheet', ['employees' => $this->employees]);
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->setRightToLeft(true);
    }

    public function title(): string
    {
        return 'أداء الموظفين';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $rowCount = $sheet->getHighestRow();

                $headerStyle = [
                    'font' => [
                        'bold' => true,
                        'color' => ['argb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => '6F4E37'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ];
                
                // تنسيق العنوان الرئيسي (الصف 1)
                $sheet->getStyle('A1:G1')->applyFromArray($headerStyle);
                $sheet->getRowDimension(1)->setRowHeight(35);

                // تنسيق أسماء الأعمدة (الصف 2)
                $sheet->getStyle('A2:G2')->getFont()->setBold(true)->getColor()->setARGB('000000');
                $sheet->getStyle('A2:G2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('E0E0E0');
                $sheet->getRowDimension(2)->setRowHeight(30);

                // 2. ضبط عرض الأعمدة
                $sheet->getColumnDimension('A')->setWidth(30); // الموظف
                $sheet->getColumnDimension('B')->setWidth(25); // القسم
                $sheet->getColumnDimension('C')->setWidth(15); // الإجمالي
                $sheet->getColumnDimension('D')->setWidth(15); // منجز
                $sheet->getColumnDimension('E')->setWidth(15); // جاري
                $sheet->getColumnDimension('F')->setWidth(15); // معلق
                $sheet->getColumnDimension('G')->setWidth(20); // النسبة

                // 3. تلوين الصفوف (من 3 إلى النهاية)
                for ($row = 3; $row <= $rowCount; $row++) {
                    
                    // Zebra Striping (تلوين الخلفية للصفوف الزوجية)
                    if ($row % 2 == 0) {
                        $sheet->getStyle("A{$row}:G{$row}")
                              ->getFill()
                              ->setFillType(Fill::FILL_SOLID)
                              ->getStartColor()->setARGB('F9F7F5');
                    }

                    // قراءة نسبة الإنجاز (العمود G)
                    $rateCell = $sheet->getCell("G{$row}")->getValue();
                    $rate = (float) str_replace(['%', ' '], '', $rateCell); // تنظيف القيمة

                    // قراءة المعلق (العمود F)
                    $overdue = (int) $sheet->getCell("F{$row}")->getValue();

                    // تحديد لون النسبة
                    $rateColor = 'FFFFFF'; 
                    if ($rate >= 90) {
                        $rateColor = 'D1E7DD'; // ممتاز (أخضر)
                    } elseif ($rate < 50) {
                        $rateColor = 'F8D7DA'; // ضعيف (أحمر)
                    } elseif ($rate < 80) {
                        $rateColor = 'FFF3CD'; // متوسط (برتقالي)
                    }

                    // تطبيق لون النسبة
                    if($rateColor !== 'FFFFFF'){
                        $sheet->getStyle("G{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($rateColor);
                    }
                    $sheet->getStyle("G{$row}")->getFont()->setBold(true);

                    // تلوين المعلق بالأحمر إذا كان > 0
                    if ($overdue > 0) {
                        $sheet->getStyle("F{$row}")->getFont()->setColor(new Color('DC3545'));
                        $sheet->getStyle("F{$row}")->getFont()->setBold(true);
                    }

                    // تلوين المنجز بالأخضر
                    $sheet->getStyle("D{$row}")->getFont()->setColor(new Color('198754'));
                    $sheet->getStyle("D{$row}")->getFont()->setBold(true);

                    // تلوين الجاري بالأزرق
                    $sheet->getStyle("E{$row}")->getFont()->setColor(new Color('0D6EFD'));
                }

                // 4. تنسيقات عامة وتجميد
                $sheet->freezePane('A3'); // تثبيت أول صفين
                $sheet->setAutoFilter("A2:G{$rowCount}"); // تفعيل الفلتر على الصف الثاني

                // محاذاة وتوسيط
                $sheet->getStyle("A1:G{$rowCount}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle("C3:G{$rowCount}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("B3:B{$rowCount}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // حدود خفيفة للجدول
                $sheet->getStyle("A1:G{$rowCount}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new Color('CCCCCC'));
            },
        ];
    }
}