<?php
//الأساسي
namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\Exportable;

class AuditorReportExport implements WithMultipleSheets
{
    use Exportable;

    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function sheets(): array
    {
        $sheets = [];

        // الصفحة 1: الملخص التنفيذي (Dashboard Summary)
        if (class_exists(AuditorSummarySheet::class)) {
            $sheets[] = new AuditorSummarySheet($this->data);
        }

        // الصفحة 2: أداء الموظفين 
        if (isset($this->data['employeesStats'])) {
            $sheets[] = new AuditorEmployeesSheet($this->data['employeesStats']);
        }

        // الصفحة 3: سجل المهام التفصيلي 
        if (isset($this->data['allTasks'])) {
            $sheets[] = new AuditorDetailsSheet($this->data['allTasks']);
        }

        return $sheets;
    }
}