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
use Carbon\Carbon;

class AuditorDetailsSheet implements FromView, WithStyles, WithTitle, WithEvents
{
    protected $tasks;

    public function __construct($tasks)
    {
        $this->tasks = $tasks;
    }

    public function view(): View
    {
        return view('exports.auditor_detailed_tasks', ['tasks' => $this->tasks]);
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->setRightToLeft(true); // اتجاه الورقة لليمين
    }

    public function title(): string
    {
        return 'سجل المهام التفصيلي';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $rowCount = $sheet->getHighestRow();
                $lastColumn = 'G'; // آخر عمود في الجدول

                // 1. تنسيق الهيدر (الصف الأول)
                $headerStyle = [
                    'font' => [
                        'bold' => true,
                        'color' => ['argb' => 'FFFFFF'],
                        'size' => 12,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => '6F4E37'], // لون بني عودي
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ];
                $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray($headerStyle);
                $sheet->getRowDimension(1)->setRowHeight(30); // ارتفاع الهيدر

                // 2. تثبيتف الأول والفلترة
                $sheet->freezePane('A2');
                $sheet->setAutoFilter("A1:{$lastColumn}1");

                // 3. ضبط عرض الأعمدة
                $sheet->getColumnDimension('A')->setWidth(10); // # يعني 1او 2 او 3 الخخخ
                $sheet->getColumnDimension('B')->setWidth(50); // العنوان
                $sheet->getColumnDimension('C')->setWidth(25); // المسؤول
                $sheet->getColumnDimension('D')->setWidth(15); // الحالة
                $sheet->getColumnDimension('E')->setWidth(15); // الأولوية
                $sheet->getColumnDimension('F')->setWidth(18); // تاريخ الإنشاء
                $sheet->getColumnDimension('G')->setWidth(18); // تاريخ الاستحقاق

                // 4. تنسيق المحتوى (الصفوف من 2 إلى النهاية)
                $contentStyle = [
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'horizontal' => Alignment::HORIZONTAL_CENTER, // توسيط الكل مبدئياً
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => 'CCCCCC'],
                        ],
                    ],
                ];
                $sheet->getStyle("A2:{$lastColumn}{$rowCount}")->applyFromArray($contentStyle);
                
                // محاذاة العنوان لليمين لأنه نص طويل
                $sheet->getStyle("B2:B{$rowCount}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("B2:B{$rowCount}")->getAlignment()->setWrapText(true);

                // 5. التلوين الشرطي 
                for ($row = 2; $row <= $rowCount; $row++) {
                    
                    // أ. تلوين الخلفية للصفوف الزوجية
                    if ($row % 2 == 0) {
                        $sheet->getStyle("A{$row}:{$lastColumn}{$row}")
                              ->getFill()
                              ->setFillType(Fill::FILL_SOLID)
                              ->getStartColor()->setARGB('F9F7F5'); // رمادي فاتح 
                    }

                    // ب. قراءة القيم
                    $statusCell = $sheet->getCell("D{$row}");
                    $statusVal  = trim($statusCell->getValue());
                    
                    $dateCell   = $sheet->getCell("G{$row}");
                    $dateVal    = $dateCell->getValue(); // يفضل أن يكون التنسيق Y-m-d في البليد

                    // ج. تلوين نص الحالة
                    if (in_array($statusVal, ['completed', 'closed', 'مكتملة', 'منجز'])) {
                        // أخضر
                        $sheet->getStyle("D{$row}")->getFont()->setColor(new Color('198754'));
                        $sheet->getStyle("D{$row}")->getFont()->setBold(true);
                    } elseif (in_array($statusVal, ['in_progress', 'جارية'])) {
                        // أزرق
                        $sheet->getStyle("D{$row}")->getFont()->setColor(new Color('0D6EFD'));
                    } elseif (in_array($statusVal, ['returned', 'معادة'])) {
                        // أحمر
                        $sheet->getStyle("D{$row}")->getFont()->setColor(new Color('DC3545'));
                    }

                    // د. تلوين تاريخ الاستحقاق إذا كان متأخراً
                    // شرط: المهمة غير منتهية + التاريخ قديم
                    $isFinished = in_array($statusVal, ['completed', 'closed', 'archived', 'مكتملة', 'مغلقة', 'مؤرشفة']);
                    
                    if (!$isFinished && $dateVal) {
                        try {
                            if (Carbon::parse($dateVal)->lt(Carbon::today())) {
                                // تلوين الخلية بالأحمر الفاتح والنص بالأحمر الغامق
                                $sheet->getStyle("G{$row}")->getFont()->setColor(new Color('DC3545'));
                                $sheet->getStyle("G{$row}")->getFont()->setBold(true);
                            }
                        } catch (\Exception $e) {
                        }
                    }
                }
            },
        ];
    }
}