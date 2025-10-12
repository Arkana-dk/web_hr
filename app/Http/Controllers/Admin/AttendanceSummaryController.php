<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Response;
use App\Models\Employee;
use App\Models\OvertimeRequest;
use Carbon\Carbon;

class AttendanceSummaryController extends Controller
{
    public function index(Request $request)
    {
        // 1) Period from query or default
        $month = (int) $request->input('month', now()->format('m'));
        $year  = (int) $request->input('year',  now()->format('Y'));
        $search = trim((string)$request->input('search', ''));

        // 2) Build summary (Collection of arrays)
        $raw = $this->buildRawSummary($month, $year, $search);

        // 3) Paginate the collection
        $perPage = 9;
        $page    = (int) $request->input('page', 1);
        $slice   = $raw->slice(($page - 1) * $perPage, $perPage)->values();
        $summary = new LengthAwarePaginator(
            $slice,
            $raw->count(),
            $perPage,
            $page,
            [
                'path'  => $request->url(),
                'query' => $request->query(),
            ]
        );

        // 4) Pass to view
        return view('admin.pages.attendance-summary.index', [
            'summary' => $summary,
            'month'   => $month,
            'year'    => $year,
            'search'  => $search,
        ]);
    }

    public function detail(Request $request, $employeeId)
{
    $month = (int) $request->input('month', now()->month);
    $year  = (int) $request->input('year', now()->year);

    $employee = \App\Models\Employee::with([
        'attendances' => fn($q) =>
            $q->whereMonth('date', $month)->whereYear('date', $year),
        'leaveRequests' => fn($q) =>
            $q->whereMonth('start_date', $month)->whereYear('start_date', $year),
    ])->findOrFail($employeeId);

    // Hitung summary personal
    $summary = [
        'present' => $employee->attendances->where('status','present')->count(),
        'late'    => $employee->attendances->where('status','late')->count(),
        'absent'  => $employee->attendances->where('status','absent')->count(),
        'cuti'    => $employee->leaveRequests->where('status','approved')->count(),
    ];

    return view('admin.pages.attendance-summary.detail', [
        'employee' => $employee,
        'attendances' => $employee->attendances,
        'summary' => $summary,
        'month' => $month,
        'year'  => $year,
    ]);
}


    /**
     * EXPORT: csv (native, no package), xlsx/pdf (opsional)
     * Route: admin.attendance-summary.export
     */
    public function export(Request $request, string $format)
    {
        $month  = (int) $request->input('month', now()->format('m'));
        $year   = (int) $request->input('year',  now()->format('Y'));
        $search = trim((string)$request->input('search', ''));

        $data = $this->buildRawSummary($month, $year, $search)->values(); // Collection

        // Header kolom
        $headers = [
            'Employee ID',
            'Nama Pegawai',
            'Departemen',
            'Jabatan',
            'Hadir',
            'Terlambat',
            'Izin/Cuti',
            'Tidak Hadir',
            'Lembur (jam)',
            'Periode',
        ];

        // Map ke rows
        $rows = $data->map(function ($item) use ($month, $year) {
            return [
                $item['employee_id'] ?? '',
                $item['employee_name'] ?? '',
                $item['department'] ?? '',
                $item['position'] ?? '',
                (int)($item['present'] ?? 0),
                (int)($item['late'] ?? 0),
                (int)($item['cuti'] ?? 0),
                (int)($item['absent'] ?? 0),
                (int)($item['overtime_hours'] ?? 0),
                sprintf('%02d-%d', $month, $year),
            ];
        });

        $filenameBase = 'attendance_summary_' . $year . '_' . str_pad($month, 2, '0', STR_PAD_LEFT);

        switch ($format) {
            case 'csv':
                return $this->exportCsv($headers, $rows, $filenameBase . '.csv');

            case 'xlsx':
                // Opsional: butuh maatwebsite/excel
                if (class_exists(\Maatwebsite\Excel\Facades\Excel::class)) {
                    return $this->exportXlsx($headers, $rows, $filenameBase . '.xlsx');
                }
                return Response::make('XLSX export requires maatwebsite/excel package.', 501);

            case 'pdf':
                // Opsional: butuh barryvdh/laravel-dompdf
                if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                    return $this->exportPdf($headers, $rows, $month, $year, $filenameBase . '.pdf');
                }
                return Response::make('PDF export requires barryvdh/laravel-dompdf package.', 501);

            default:
                abort(404);
        }
    }

    /**
     * Builder ringkasan mentah (tanpa pagination), mempertahankan filter & periode.
     */
    protected function buildRawSummary(int $month, int $year, ?string $search = null)
    {
        $employees = Employee::with([
            'department:id,name',
            'position:id,name',
            'attendances' => fn($q) =>
                $q->whereMonth('date', $month)->whereYear('date', $year),
            'leaveRequests' => fn($q) =>
                $q->whereMonth('start_date', $month)->whereYear('start_date', $year),
        ])->get();

        $raw = $employees->map(function ($emp) use ($month, $year) {
            $overtimeHours = OvertimeRequest::where('employee_id', $emp->id)
                ->where('status','approved')
                ->whereMonth('date', $month)
                ->whereYear('date',  $year)
                ->get()
                ->sum(fn($ot) =>
                    Carbon::parse($ot->end_time)->diffInHours(Carbon::parse($ot->start_time))
                );

            return [
                'employee_id'    => $emp->id,
                'employee_name'  => $emp->name,
                'department'     => optional($emp->department)->name,
                'position'       => optional($emp->position)->name,
                'present'        => $emp->attendances->where('status','present')->count(),
                'late'           => $emp->attendances->where('status','late')->count(),
                'absent'         => $emp->attendances->where('status','absent')->count(),
                'cuti'           => $emp->leaveRequests->where('status','approved')->count(),
                'overtime_hours' => $overtimeHours,
            ];
        });

        if ($search = trim((string)$search)) {
            $raw = $raw->filter(fn($item) =>
                str_contains(strtolower($item['employee_name']), strtolower($search))
            )->values();
        }

        return $raw;
    }

    /**
     * Export CSV native (tanpa paket tambahan)
     */
    protected function exportCsv(array $headers, $rows, string $filename)
    {
        $callback = function () use ($headers, $rows) {
            $file = fopen('php://output', 'w');
            // UTF-8 BOM agar Excel Windows aman
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($file, $headers);
            foreach ($rows as $r) {
                fputcsv($file, $r);
            }
            fclose($file);
        };

        return Response::stream($callback, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control'       => 'no-store, no-cache, must-revalidate',
        ]);
    }

    /**
     * Export XLSX (opsional) – aktif jika pakai maatwebsite/excel
     * composer require maatwebsite/excel
     */
    protected function exportXlsx(array $headers, $rows, string $filename)
    {
        $exportArray = collect([$headers])->merge($rows)->toArray();
        $export = new class($exportArray) implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithTitle {
            private array $data;
            public function __construct(array $data){ $this->data=$data; }
            public function array(): array { return $this->data; }
            public function title(): string { return 'Attendance Summary'; }
        };

        return \Maatwebsite\Excel\Facades\Excel::download($export, $filename);
    }

    /**
     * Export PDF (opsional) – aktif jika pakai barryvdh/laravel-dompdf
     * composer require barryvdh/laravel-dompdf
     */
    protected function exportPdf(array $headers, $rows, int $month, int $year, string $filename)
    {
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.pages.attendance-summary.export-pdf', [
            'headers' => $headers,
            'rows'    => $rows,
            'month'   => $month,
            'year'    => $year,
        ])->setPaper('a4', 'portrait');

        return $pdf->download($filename);
    }
}
