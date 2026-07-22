<?php
//التقرير التنفيذي
namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class AuditorSummarySheet implements FromView, WithStyles, WithTitle, WithEvents
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function view(): View
    {
        return view('exports.auditor_summary_report', $this->data);
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->setRightToLeft(true);
    }

    public function title(): string
    {
        return 'الملخص التنفيذي';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // حساب آخر صف فيه بيانات لتطبيق التنسيق عليه فقط
                $lastRow = $sheet->getHighestRow();

                // 1. تحديد عرض الأعمدة 
                $sheet->getColumnDimension('A')->setWidth(20);
                $sheet->getColumnDimension('B')->setWidth(20);
                $sheet->getColumnDimension('C')->setWidth(20);
                $sheet->getColumnDimension('D')->setWidth(20);
                $sheet->getColumnDimension('E')->setWidth(20);
                $sheet->getColumnDimension('F')->setWidth(20);

                // 2. ضبط ارتفاع صفوف الترويسة
                $sheet->getRowDimension(1)->setRowHeight(30); // العنوان الرئيسي
                $sheet->getRowDimension(2)->setRowHeight(25); // العنوان الفرعي

                // 3. توسيط جميع الخلايا (أفقياً وعمودياً)
                $sheet->getStyle("A1:F{$lastRow}")->getAlignment()->applyFromArray([
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ]);

                //الجهه
                $sheet->getStyle('A3:F3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            },
        ];
    }
}