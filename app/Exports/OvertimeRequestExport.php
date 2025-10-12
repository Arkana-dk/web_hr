<?php

namespace App\Exports;

use App\Models\OvertimeRequest;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class OvertimeRequestExport implements FromCollection, WithHeadings, WithMapping
{
    protected $startDate;
    protected $endDate;

    public function __construct($startDate = null, $endDate = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }
    
    public function collection()
    {
        $query = OvertimeRequest::with('employee');

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('date', [$this->startDate, $this->endDate]);
        }

        return $query->get();
    }


    public function headings(): array
    {
        return [
            'NIP',
            'Nama Pegawai',
            'Opsi Makan',
            'Jenis Lembur',
            'Tujuan Jemputan',
            'Tanggal',
            'Jam Mulai',
            'Jam Selesai',
            'Alasan',
            'Status',
        ];
    }

    public function map($request): array
    {
        return [
            $request->employee->employee_number ?? '-',
            $request->employee->name ?? '-',
            $request->meal_option,
            ucfirst($request->day_type),
            $request->transport_route,
            $request->date,
            $request->start_time,
            $request->end_time,
            $request->reason,
            ucfirst($request->status),
        ];
    }
}
