<?php

namespace App\Exports;

use App\Models\PayRun;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PayRunExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithCustomStartCell, WithEvents
{
    /** @var array<string, mixed> */
    protected array $filters;

    protected string $monthLabel;

    /**
     * @param array{status?:string|null, group?:string|int|null, q?:string|null, month_label?:string|null} $filters
     */
    public function __construct(array $filters = [])
    {
        $this->filters = $filters;

        // Label bulan (default: bulan sekarang, bahasa Indonesia, huruf besar)
        $this->monthLabel = Str::upper(
            (string)($filters['month_label']
                ?? Carbon::now()->locale('id')->translatedFormat('F'))
        );
    }

    /** @return Collection<int,\App\Models\PayRun> */
    public function collection(): Collection
    {
        $q = PayRun::query()->with('payGroup');

        if (!empty($this->filters['status'])) {
            $q->where('status', $this->filters['status']);
        }
        if (!empty($this->filters['group'])) {
            $q->where('pay_group_id', $this->filters['group']);
        }
        if (!empty($this->filters['q'])) {
            $term = '%' . str_replace(['%','_'], ['\\%','\\_'], $this->filters['q']) . '%';
            $q->where(function ($sub) use ($term) {
                $sub->whereHas('payGroup', function ($pg) use ($term) {
                    $pg->where('name', 'like', $term)
                       ->orWhere('code', 'like', $term);
                })
                ->orWhere('notes', 'like', $term); // hapus jika kolom notes tidak ada
            });
        }

        return $q->orderByDesc('created_at')->get();
    }

    public function headings(): array
    {
        return [
            '#',
            'Pay Group',
            'Code',
            'Period Start',
            'Period End',
            'Status',
            'Created At',
            'Updated At',
        ];
    }

    /** @param \App\Models\PayRun $run */
    public function map($run): array
    {
        static $i = 0; $i++;

        return [
            $i,
            optional($run->payGroup)->name,
            optional($run->payGroup)->code,
            $run->start_date ? Carbon::parse($run->start_date)->format('Y-m-d') : null,
            $run->end_date ? Carbon::parse($run->end_date)->format('Y-m-d') : null,
            strtoupper((string) $run->status),
            $run->created_at ? $run->created_at->format('Y-m-d H:i') : null,
            $run->updated_at ? $run->updated_at->format('Y-m-d H:i') : null,
        ];
    }

    /** Headings mulai di baris 3 (A3) agar baris 1 dipakai judul & bulan */
    public function startCell(): string
    {
        return 'A3';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();

                // Rentang dinamis berdasarkan jumlah kolom heading
                $colCount     = count($this->headings());
                $lastCol      = Coordinate::stringFromColumnIndex($colCount);
                $headRowIndex = 3; // baris heading

                // ===== Row 1: Title + Month =====
                // Title
                $sheet->setCellValue('A1', 'PAYROLL REPORT');
                // Merge title melebar beberapa kolom supaya enak dilihat
                $sheet->mergeCells("A1:C1");
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

                // Label "MONTH :" + nilai bulan (merge ke kanan sampai kolom terakhir)
                $labelColIdx     = (int)max(2, ceil($colCount / 2));     // sekitar tengah
                $labelCol        = Coordinate::stringFromColumnIndex($labelColIdx);
                $valStartCol     = Coordinate::stringFromColumnIndex($labelColIdx + 1);
                $valRange        = "{$valStartCol}1:{$lastCol}1";

                $sheet->setCellValue("{$labelCol}1", 'MONTH :');
                $sheet->setCellValue("{$valStartCol}1", $this->monthLabel);
                if ($labelColIdx + 1 <= $colCount) {
                    $sheet->mergeCells($valRange);
                }

                // Style untuk MONTH box
                $sheet->getStyle("{$labelCol}1")->getFont()->setBold(true);
                $sheet->getStyle($valRange)->getFont()->setBold(true);
                $sheet->getStyle($valRange)->getFill()->setFillType(Fill::FILL_SOLID)
                      ->getStartColor()->setARGB('FFFFF2CC'); // kuning lembut
                $sheet->getStyle($valRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Opsional: tinggi baris 1
                $sheet->getRowDimension(1)->setRowHeight(22);

                // ===== Row 3: Heading table (A3:...3) =====
                $headerRange = "A{$headRowIndex}:{$lastCol}{$headRowIndex}";
                $sheet->getStyle($headerRange)->getFont()->setBold(true);
                $sheet->getStyle($headerRange)->getAlignment()
                      ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                      ->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)
                      ->getStartColor()->setARGB('FFE2EFDA'); // hijau lembut
                $sheet->getStyle($headerRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getRowDimension($headRowIndex)->setRowHeight(20);

                // Border tipis untuk seluruh data (opsional rapi)
                $highestRow = $sheet->getHighestRow();
                if ($highestRow > $headRowIndex) {
                    $dataRange = "A{$headRowIndex}:{$lastCol}{$highestRow}";
                    $sheet->getStyle($dataRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                }
            },
        ];
    }
}
