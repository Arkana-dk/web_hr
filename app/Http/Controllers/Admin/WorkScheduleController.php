<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
// use App\Http\Requests\WorkScheduleImportRequest; // gunakan bila Anda punya FormRequest
use App\Models\Employee;
use App\Models\Shift;
use App\Models\WorkSchedule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

use PhpOffice\PhpSpreadsheet\IOFactory;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;




//==================== CATATAN =========================

    // BELUM SELESAI! JANGAN DI OPREK


    /// =============================================
class WorkScheduleController extends Controller
{
    /**
     * List + filter jadwal (existing)
     */
    public function index(Request $request)
{
    $employees = Employee::with([
        'workSchedules.shift',
        'department',
        'section',
        'position'
    ])->get();

    $allSchedules = collect();
    foreach ($employees as $emp) {
        foreach ($emp->workSchedules as $ws) {
            $ws->employee_name   = $emp->name;
            $ws->employee_number = $emp->employee_number;
            $ws->shift_name      = $ws->shift->name ?? '-';
            $ws->department      = $emp->department->name ?? '-';
            $ws->section         = $emp->section->name ?? '-';
            $ws->position        = $emp->position->name ?? '-';
            $ws->work_date       = \Carbon\Carbon::parse($ws->work_date)->toDateString();
            $allSchedules->push($ws);
        }
    }

    // ==== FILTER BARU: by name (contains, case-insensitive)
    if ($request->filled('name')) {
        $needle = mb_strtolower(trim($request->name));
        $allSchedules = $allSchedules->filter(function ($ws) use ($needle) {
            $name = mb_strtolower($ws->employee_name ?? '');
            $num  = mb_strtolower((string)($ws->employee_number ?? ''));
            return str_contains($name, $needle) || str_contains($num, $needle);
        });
    }


    // ==== Filter lainnya tetap
    if ($request->filled('department')) {
        $allSchedules = $allSchedules->filter(fn($ws) => $ws->department === $request->department);
    }
    if ($request->filled('section')) {
        $allSchedules = $allSchedules->filter(fn($ws) => $ws->section === $request->section);
    }
    if ($request->filled('position')) {
        $allSchedules = $allSchedules->filter(fn($ws) => $ws->position === $request->position);
    }

    // Opsi dropdown (unik & terurut) — tanpa employeeOptions
    $departmentOptions = $allSchedules->pluck('department')->filter()->unique()->sort()->values();
    $sectionOptions    = $allSchedules->pluck('section')->filter()->unique()->sort()->values();
    $positionOptions   = $allSchedules->pluck('position')->filter()->unique()->sort()->values();

    // Pagination
    $page   = (int) $request->get('page', 1);
    $perPage = 20;
    $paginatedSchedules = new LengthAwarePaginator(
        $allSchedules->forPage($page, $perPage)->values(),
        $allSchedules->count(),
        $perPage,
        $page,
        ['path' => $request->url(), 'query' => $request->query()]
    );

    // Events untuk kalender & panel
    $events = WorkSchedule::with('employee', 'shift')
        ->get()
        ->map(function ($item) {
            return [
                'title' => ($item->employee->name ?? 'Unknown') . ' - ' . ($item->shift->name ?? '-'),
                'start' => \Carbon\Carbon::parse($item->work_date)->toDateString(),
            ];
        });

    return view('admin.pages.work-schedules.index', [
        'employees'        => $employees,
        'schedules'        => $paginatedSchedules,
        'events'           => $events,
        'departmentOptions'=> $departmentOptions,
        'sectionOptions'   => $sectionOptions,
        'positionOptions'  => $positionOptions,
        'selected' => [
            'name'      => $request->name,
            'department'=> $request->department,
            'section'   => $request->section,
            'position'  => $request->position,
        ],
    ]);
}


    /**
     * GET: Halaman import (form upload)
     */
    public function showImport()
    {
        return view('admin.pages.work-schedules.import');
    }

    /**
     * POST: Upload & parse Excel multi-sheet.
     * Sheet[0] = mapping shift; Sheet[1..N] = bulan (nama sheet: JANUARY-2025 / JANUARI-2025 / dst)
     */
    public function import(Request $request)
{
    $request->validate([
        'file' => 'required|mimes:xlsx,xls',
    ]);

    $uploadedFile = $request->file('file');
    $path = $uploadedFile->getPathname();

    // Ambil nama semua sheet & koleksi sheet
    $spreadsheet = IOFactory::load($path);
    $sheetNames  = $spreadsheet->getSheetNames();
    $sheets      = Excel::toCollection(null, $uploadedFile);

    // Mapping Nama Bulan (definisikan SEKALI saja)
    $monthMap = [
        'JAN' => 1, 'JANUARY' => 1, 'JANUARI' => 1,
        'FEB' => 2, 'FEBRUARY' => 2, 'FEBRUARI' => 2,
        'MAR' => 3, 'MARCH' => 3, 'MARET' => 3,
        'APR' => 4, 'APRIL' => 4,
        'MAY' => 5, 'MEI' => 5,
        'JUN' => 6, 'JUNE' => 6, 'JUNI' => 6,
        'JUL' => 7, 'JULY' => 7, 'JULI' => 7,
        'AUG' => 8, 'AUGUST' => 8, 'AGUSTUS' => 8,
        'SEP' => 9, 'SEPT' => 9, 'SEPTEMBER' => 9,
        'OCT' => 10, 'OCTOBER' => 10, 'OKTOBER' => 10,
        'NOV' => 11, 'NOVEMBER' => 11,
        'DEC' => 12, 'DECEMBER' => 12, 'DESEMBER' => 12,
    ];

    // --- STEP 1: Deteksi sheet mapping (kode|nama) -> optional
    $mappingSheetIndex = null;
    foreach ($sheets as $i => $sheet) {
        // Heuristik: 5 baris pertama mayoritas punya kol A & B terisi, kol C kosong
        $score = 0; $checked = 0;
        foreach ($sheet->take(5) as $row) {
            $c0 = trim((string)($row[0] ?? ''));
            $c1 = trim((string)($row[1] ?? ''));
            $c2 = trim((string)($row[2] ?? ''));
            if ($c0 !== '' || $c1 !== '' || $c2 !== '') {
                $checked++;
                if ($c0 !== '' && $c1 !== '' && $c2 === '') $score++;
            }
        }
        if ($checked > 0 && $score >= 3) {
            $mappingSheetIndex = $i;
            break;
        }
    }

    $excelCodeToName = [];
    if ($mappingSheetIndex !== null) {
        $shiftSheet = $sheets[$mappingSheetIndex];
        foreach ($shiftSheet as $row) {
            $code = trim((string)($row[0] ?? ''));
            $name = trim((string)($row[1] ?? ''));
            if ($code && $name) {
                $excelCodeToName[strtoupper($code)] = $this->normalizeShiftToken($name);
            }
        }
    }

    // Map nama shift di DB -> id (selalu dibuat sebagai fallback)
    $dbShiftNameToId = [];
    Shift::query()->get()->each(function ($s) use (&$dbShiftNameToId) {
        $dbShiftNameToId[$this->normalizeShiftToken($s->name)] = $s->id;
    });

    // Build lookup code->id (dari mapping sheet) dan name->id (dari DB)
    $shiftIdLookup = [];
    foreach ($excelCodeToName as $code => $normName) {
        if (isset($dbShiftNameToId[$normName])) {
            $shiftIdLookup[$code] = $dbShiftNameToId[$normName];
        }
    }
    foreach ($dbShiftNameToId as $normName => $id) {
        $shiftIdLookup[$normName] = $id;
    }

    // --- STEP 2: Parse jadwal tiap sheet bulan
    $allRows = [];
    $invalidEmployees = [];
    $tokensUnknown = [];

    // Month sheets = semua sheet kecuali mapping (kalau ketemu)
    $monthSheets = $sheets;
    if ($mappingSheetIndex !== null) {
        $monthSheets = $sheets->except([$mappingSheetIndex])->values();
    }

    foreach ($monthSheets as $idx => $sheet) {
        // Ambil nama sheet: sesuaikan index terhadap sheetNames asli
        // Jika ada mappingSheetIndex dan < idx, offset bertambah 1; paling aman: cari nama langsung dari instance
        // Tapi karena kita cuma butuh pola BULAN-TAHUN, pakai pendekatan ini:
        $originalIdx = $idx;
        if ($mappingSheetIndex !== null && $idx >= $mappingSheetIndex) {
            $originalIdx = $idx + 1;
        }
        $sheetName = strtoupper(trim($sheetNames[$originalIdx] ?? ''));

        if (!preg_match('/([A-Z]+)[\s\- ]?([0-9]{4})/', $sheetName, $matches)) {
            // Lewati sheet yang namanya tidak sesuai pola, misal "JADWAL", "CATATAN", dll
            continue;
        }

        $monthStr = strtoupper($matches[1]);
        $year     = (int)$matches[2];
        $month    = $monthMap[$monthStr] ?? null;
        if (!$month) {
            continue; // Bulan tak dikenal -> lewati
        }

        // 1) Deteksi header & indeks kolom penting
        $headerRowIdx = null;
        $colEmpNumber = null;
        $colName      = null;
        $firstDateCol = null;
        $datesRowIdx  = null;

        foreach ($sheet->take(10) as $rIdx => $row) {
            // Normalisasi sel ke uppercase string
            $cells = [];
            foreach ($row as $i => $v) {
                $cells[$i] = strtoupper(trim((string)($v ?? '')));
            }

            // Header EMPLOYEE_NUMBER / NIK / NAMA
            if (in_array('EMPLOYEE_NUMBER', $cells, true) || in_array('NIK', $cells, true)) {
                $headerRowIdx = $rIdx;
                $colEmpNumber = array_search('EMPLOYEE_NUMBER', $cells, true);
                if ($colEmpNumber === false) $colEmpNumber = array_search('NIK', $cells, true);
                $colName      = array_search('NAMA', $cells, true);
            }

            // Baris label tanggal (YYYY-MM-DD)
            $dateCols = [];
            foreach ($cells as $i => $val) {
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $val)) {
                    $dateCols[] = $i;
                }
            }
            if (count($dateCols) >= 5) { // heuristik minimal 5 tanggal
                $datesRowIdx  = $rIdx;
                $firstDateCol = min($dateCols);
            }
        }

        // Fallback kalau header tidak ketemu
        if ($colEmpNumber === null) $colEmpNumber = 0; // A
        if ($colName      === null) $colName      = 1; // B
        if ($firstDateCol === null) $firstDateCol = 2; // C

        // Data mulai setelah header/dates row (ambil yang paling bawah)
        $startDataRow = 0;
        if ($headerRowIdx !== null || $datesRowIdx !== null) {
            $startDataRow = max((int)$headerRowIdx, (int)$datesRowIdx) + 1;
        }

        // 2) Iterasi baris data
        $rowIndex = -1;
        foreach ($sheet as $row) {
            $rowIndex++;
            if ($rowIndex < $startDataRow) continue;

            $employeeNumber = trim((string)($row[$colEmpNumber] ?? ''));
            $employeeName   = trim((string)($row[$colName]      ?? ''));

            // Skip baris header / kosong
            if (
                $employeeNumber === '' ||
                preg_match('/^(nik|no\.?\s*karyawan|employee\s*number)$/i', $employeeNumber)
            ) {
                continue;
            }

            $employee = Employee::where('employee_number', $employeeNumber)->first();
            if (!$employee) {
                $invalidEmployees[] = $employeeNumber;
                continue;
            }

            // 3) Ambil token per tanggal
            for ($day = 1; $day <= 31; $day++) {
                $colIdx = $firstDateCol + ($day - 1);
                $token  = trim((string)($row[$colIdx] ?? ''));
                if ($token === '' || $token === '-') continue;
                if (!checkdate($month, $day, $year)) continue;

                $asCode = strtoupper($token);                 // contoh: "[7#1] NS" atau "LIBUR NASIONAL"
                $asName = $this->normalizeShiftToken($token); // jadi "NS" atau "LIBUR NASIONAL"

                $shiftId = $shiftIdLookup[$asCode] ?? ($shiftIdLookup[$asName] ?? null);
                if (!$shiftId) {
                    $tokensUnknown[] = $token;
                    continue;
                }

                $shiftNameForDisplay = $excelCodeToName[$asCode] ?? $asName;
                $date = Carbon::createFromDate($year, $month, $day)->toDateString();

                $allRows[] = [
                    'employee_id'     => $employee->id,
                    'employee_number' => $employee->employee_number,
                    'employee_name'   => $employee->name ?: $employeeName,
                    'work_date'       => $date,
                    'shift_code'      => $asCode,
                    'shift_name'      => $shiftNameForDisplay,
                    'shift_id'        => $shiftId,
                ];
            }
        }
    }

    session()->put('work_schedule_preview', [
        'validRows'        => $allRows,
        'invalidEmployees' => array_values(array_unique($invalidEmployees)),
        'tokensUnknown'    => array_values(array_unique($tokensUnknown)),
    ]);

    return redirect()->route('admin.work-schedules.confirm');
}


    private function normalizeShiftToken(string $v): string
    {
        $v = trim($v);
        // buang prefix [..] di awal, mis. "[8#1] S 1" -> "S 1"
        $v = preg_replace('/^\[[^\]]+\]\s*/', '', $v);
        // samakan "S1" menjadi "S 1"
        $v = preg_replace('/^([A-Z]+)\s?(\d+)$/i', '$1 $2', $v);
        // multiple spaces -> single
        $v = preg_replace('/\s+/', ' ', $v);
        return strtoupper($v);
    }


    /**
     * GET: Halaman konfirmasi (preview dengan pagination)
     */
    public function confirmPage(Request $request)
    {
        $result = session('work_schedule_preview');

        if (!$result || empty($result['validRows'])) {
            return redirect()->route('admin.work-schedules.index')
                ->with('error', 'Tidak ada data jadwal ditemukan.');
        }

        $rows   = collect($result['validRows']);
        $page   = (int) $request->input('page', 1);
        $perPage = 50;

        $validSchedules = new LengthAwarePaginator(
            $rows->forPage($page, $perPage),
            $rows->count(),
            $perPage,
            $page,
            ['path' => url()->current()]
        );

        $invalidEmployees = $result['invalidEmployees'] ?? [];

        return view('admin.pages.work-schedules.confirmation', compact('validSchedules', 'invalidEmployees'));
    }

    /**
     * POST: Simpan ke DB (upsert chunked)
     */
    public function confirmStore()
    {
        $result = session('work_schedule_preview');

        if (!$result || empty($result['validRows'])) {
            return redirect()->route('admin.work-schedules.index')
                ->with('error', 'Tidak ada data untuk disimpan.');
        }

        $rows = collect($result['validRows']);

        DB::transaction(function () use ($rows) {
            $rows->chunk(1000)->each(function ($chunk) {
                WorkSchedule::upsert(
                    $chunk->map(fn($r) => [
                        'employee_id' => $r['employee_id'],
                        'work_date'   => $r['work_date'],
                        'shift_id'    => $r['shift_id'],
                        
                    ])->toArray(),
                    ['employee_id', 'work_date'],
                    ['shift_id']
                );
            });
        });

        session()->forget('work_schedule_preview');

        return redirect()->route('admin.work-schedules.index')
            ->with('success', 'Jadwal berhasil disimpan.');
    }

    /**
     * Utility: Paginasi Collection (kalau mau dipakai di tempat lain)
     */
    protected function paginateCollection($items, int $perPage = 50): LengthAwarePaginator
    {
        $items  = collect($items);
        $page   = (int) request()->input('page', 1);
        $offset = ($page - 1) * $perPage;

        return new LengthAwarePaginator(
            $items->slice($offset, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }
}
