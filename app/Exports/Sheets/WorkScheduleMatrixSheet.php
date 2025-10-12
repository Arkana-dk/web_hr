<?php

namespace App\Exports\Sheets;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\NamedRange;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WorkScheduleMatrixSheet implements WithTitle, WithEvents, WithCustomStartCell
{
    public function __construct(
        public string $month,     // "YYYY-MM"
        public array  $meta,      // ['department'=>..., 'section'=>..., 'position'=>...]
        public $employees,        // Collection<Employee>
        public $shifts,           // Collection<Shift> (opsional untuk referensi)
        
    ) {}

    public function startCell(): string
    {
        return 'A1';
    }

    public function title(): string
    {
        // Sheet name seperti target: "SEP-2025"
        $dt = Carbon::parse($this->month . '-01');
        return strtoupper($dt->format('M')) . '-' . $dt->format('Y');
    }

     public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $e) {
                $sheet = $e->sheet->getDelegate();
                $dt    = Carbon::parse($this->month . '-01');
                $daysInMonth = (int) $dt->daysInMonth;

                // Header atas
                $sheet->setCellValue('A1', 'Jadwal Bulan');
                $sheet->setCellValue('B1', $dt->format('Y-m'));

                $sheet->setCellValue('A2', 'Department');
                $sheet->setCellValue('B2', (string)($this->meta['department'] ?? ''));
                $sheet->setCellValue('C2', 'Section');
                $sheet->setCellValue('D2', (string)($this->meta['section'] ?? '-'));
                $sheet->setCellValue('E2', 'Position');
                $sheet->setCellValue('F2', (string)($this->meta['position'] ?? '-'));

                // Header tabel (row 3 & 4)
                $sheet->setCellValue('A3', 'USER_ID');
                $sheet->setCellValue('B3', 'EMPLOYEE_NUMBER');
                $sheet->setCellValue('C3', 'NAMA');

                $firstDateCol = 5; // E
                for ($d = 1; $d <= $daysInMonth; $d++) {
                    $colIdx = $firstDateCol + ($d - 1);
                    $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
                    $cur = $dt->copy()->day($d);

                    // Baris 3: nama hari
                    $dayNames = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'];
                    $dayName = $dayNames[(int)$cur->dayOfWeekIso % 7];
                    $sheet->setCellValue($col.'3', $dayName);

                    // Baris 4: tanggal YYYY-MM-DD
                    $sheet->setCellValue($col.'4', $cur->format('Y-m-d'));
                }

                // Data mulai row 5
                $r = 5;
                foreach ($this->employees as $emp) {
                    // USER_ID: pakai id karyawan di DB (atau kosongkan jika tidak mau)
                    $sheet->setCellValueExplicit('A'.$r, (string)($emp->id ?? ''), DataType::TYPE_STRING);
                    $sheet->setCellValueExplicit('B'.$r, (string)($emp->employee_number ?? ''), DataType::TYPE_STRING);
                    $sheet->setCellValue('C'.$r, (string)($emp->name ?? ''));

                    // Kosongkan sel tanggal (user isi)
                    for ($d = 1; $d <= $daysInMonth; $d++) {
                        $colIdx = $firstDateCol + ($d - 1);
                        $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
                        $sheet->setCellValueExplicit($col.$r, '', DataType::TYPE_STRING);
                    }
                    $r++;
                }
                $lastRow = max($r - 1, 5);

                // Styling
                $sheet->getColumnDimension('A')->setWidth(10); // USER_ID
                $sheet->getColumnDimension('B')->setWidth(20); // EMPLOYEE_NUMBER
                $sheet->getColumnDimension('C')->setWidth(24); // NAMA
                for ($d = 1; $d <= $daysInMonth; $d++) {
                    $colIdx = $firstDateCol + ($d - 1);
                    $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
                    $sheet->getColumnDimension($col)->setWidth(12);
                }

                $headerRange = 'A3:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($firstDateCol + $daysInMonth - 1) . '4';
                $sheet->getStyle($headerRange)->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                        'wrapText'   => true,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E9F3FF'],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'BFD7FF'],
                        ],
                    ],
                ]);
                $sheet->getRowDimension(3)->setRowHeight(22);
                $sheet->getRowDimension(4)->setRowHeight(22);

                $dataRange = 'A5:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($firstDateCol + $daysInMonth - 1) . $lastRow;
                $sheet->getStyle($dataRange)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_HAIR,
                            'color' => ['rgb' => 'D8E6FF'],
                        ],
                    ],
                ]);

                // Freeze pane: E5
                $sheet->freezePane('E5');

                // Named range VALID_CODE_LIST dari sheet DATALISTS (kolom B)
                $p = $sheet->getParent();
                $datalistSheet = $p->getSheetByName('DATALISTS');
                if ($datalistSheet) {
                    $p->removeNamedRange('VALID_CODE_LIST');
                    $named = new NamedRange('VALID_CODE_LIST', $datalistSheet, '$B$1:$B$1000');
                    $p->addNamedRange($named);
                }

                // Data validation dropdown untuk tanggal
                for ($row = 5; $row <= $lastRow; $row++) {
                    for ($d = 1; $d <= $daysInMonth; $d++) {
                        $colIdx = $firstDateCol + ($d - 1);
                        $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
                        $cell = $col.$row;

                        $dv = $sheet->getCell($cell)->getDataValidation();
                        $dv->setType(DataValidation::TYPE_LIST);
                        $dv->setErrorStyle(DataValidation::STYLE_STOP);
                        $dv->setAllowBlank(true);
                        $dv->setShowDropDown(true);
                        $dv->setShowErrorMessage(true);
                        $dv->setErrorTitle('Invalid');
                        $dv->setError('Pilih dari daftar.');
                        $dv->setFormula1('=VALID_CODE_LIST');

                        $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('@');
                        $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    }
                }
            },
        ];
    }
}
