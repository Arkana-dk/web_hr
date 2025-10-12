<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Employee, Department, Section, Position, User, PayGroup};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Hash, Log};
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Pagination\LengthAwarePaginator;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\UploadedFile;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as XlsDate;

class EmployeeImportController extends Controller
{
    /** Session keys */
    private const S_IMPORT_ROWS        = 'import_rows';
    private const S_IMPORT_HARD_ERRORS = 'import_hard_errors';
    private const S_IMPORT_WARNINGS    = 'import_soft_warnings';

    public function __construct()
    {
        $this->middleware('role_or_permission:super-admin|hr.employee.view_basic')
             ->only(['form', 'preview', 'downloadTemplate']);

        $this->middleware('role_or_permission:super-admin|hr.employee.manage')
             ->only(['store']);
    }

    /** Step 1: Form upload */
    public function form()
    {
        Log::info('EMP_IMPORT_FORM');
        return view('admin.pages.employee-import.index');
    }

    /** Step 2: Upload & Preview (POST proses → redirect GET, GET tampilkan + paginate) */
    public function preview(Request $request)
    {
        if ($request->isMethod('post')) {
            $request->validate([
                'file' => 'required|file|mimes:xls,xlsx|max:20480', // 20MB
            ]);

            [$parsedRows, $hardErrors, $softWarnings] = $this->parseExcel($request->file('file'));

            session([
                self::S_IMPORT_ROWS        => $parsedRows,
                self::S_IMPORT_HARD_ERRORS => $hardErrors,
                self::S_IMPORT_WARNINGS    => $softWarnings,
            ]);

            // redirect agar pagination pakai GET
            return redirect()->route('admin.employee.import.preview', [
                'per_page' => $request->integer('per_page', 50),
            ]);
        }

        // GET: ambil dari session → paginate
        $allRows      = collect(session(self::S_IMPORT_ROWS, []));
        $hardErrors   = session(self::S_IMPORT_HARD_ERRORS, []);
        $softWarnings = session(self::S_IMPORT_WARNINGS, []);
        $perPage      = max(1, (int) $request->query('per_page', 50));
        $page         = max(1, (int) $request->query('page', 1));

        $rows = new LengthAwarePaginator(
            $allRows->forPage($page, $perPage)->values(),
            $allRows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('admin.pages.employee-import.preview', [
            'rows'         => $rows,
            'hardErrors'   => $hardErrors,
            'softWarnings' => $softWarnings,
            'perPage'      => $perPage,
        ]);
    }

    /** Step 3: Commit data valid (abaikan soft warnings) */
    public function store(Request $request)
    {
        $reqId = (string) Str::uuid();
        Log::info('EMP_IMPORT_STORE_START', [
            'req_id'  => $reqId,
            'db_conn' => config('database.default'),
            'db_name' => config('database.connections.'.config('database.default').'.database'),
        ]);

        $rows       = session(self::S_IMPORT_ROWS);
        $hardErrors = session(self::S_IMPORT_HARD_ERRORS, []);

        if (!$rows || count($rows) === 0) {
            Log::warning('EMP_IMPORT_STORE_NODATA', ['req_id' => $reqId]);
            return redirect()->route('admin.employee.import.form')
                ->with('error', 'Data tidak ditemukan. Silakan upload ulang.');
        }

        if (!empty($hardErrors)) {
            Log::warning('EMP_IMPORT_STORE_HAS_HARD_ERRORS', ['req_id' => $reqId, 'errors' => $hardErrors]);
            return redirect()->route('admin.employee.import.form')
                ->with('error', 'Ada data kritikal yang tidak valid (mis. Email kosong/tidak valid/duplikat). Perbaiki & upload ulang.');
        }

        $inserted = 0;
        $skipped  = 0;  // baris yang diskip (mis. invalid/duplikat)
        $failed   = []; // baris yang gagal (exception DB dll)

        foreach ($rows as $rowData) {
            DB::beginTransaction();
            try {
                // 0) Normalisasi email (hindari duplikat karena beda kapital/whitespace)
                $email = Str::lower(trim((string)($rowData['email'] ?? '')));
                if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    DB::rollBack();
                    Log::info('EMP_IMPORT_SKIP_HARD', ['req_id' => $reqId, 'reason' => 'EMAIL_INVALID', 'raw' => $rowData['email'] ?? null]);
                    $skipped++; 
                    continue;
                }
                // pakai pengecekan LOWER(email) agar konsisten di semua collation
                $emailUsed = Employee::whereRaw('LOWER(email) = ?', [$email])->exists()
                        || User::whereRaw('LOWER(email) = ?', [$email])->exists();

                if ($emailUsed) {
                    DB::rollBack();
                    Log::info('EMP_IMPORT_SKIP_HARD', ['req_id' => $reqId, 'reason' => 'EMAIL_DUP', 'email' => $email]);
                    $skipped++;
                    continue;
                }

                // pastikan email yang dipakai ke langkah berikut sudah dinormalisasi
                $rowData['email'] = $email;
                $data = $this->sanitizeRowForInsert($rowData);

                // 1) Password awal dari NIK (wajib ada)
                $rawPassword = $data['national_identity_number'] ?? null;
                if (empty($rawPassword)) {
                    DB::rollBack();
                    Log::info('EMP_IMPORT_SKIP_NO_NIK', [
                        'req_id' => $reqId,
                        'row'    => $rowData['row_num'] ?? null,
                        'email'  => $email,
                    ]);
                    $skipped++;
                    continue;
                }

                // 2) Cek duplikat NIK lagi (race-safety)
                if (Employee::where('national_identity_number', $data['national_identity_number'])->exists()) {
                    DB::rollBack();
                    Log::info('EMP_IMPORT_SKIP_DUP_NIK', [
                        'req_id' => $reqId,
                        'nik'    => $data['national_identity_number'],
                        'email'  => $email,
                    ]);
                    $skipped++;
                    continue;
                }

                // 3) Buat user
                $user = User::create([
                    'name'     => $data['name'] ?: '(Tanpa Nama)',
                    'email'    => $email,
                    'password' => Hash::make((string) $rawPassword), // pakai NIK
                    'role'     => 'user',
                ]);

                // 4) Buat employee (TANPA generate employee_number dari max(id))
                $emp = Employee::create([
                    'user_id'                  => $user->id,
                    'role'                     => 'employee',
                    'name'                     => $data['name'] ?: '(Tanpa Nama)',
                    'national_identity_number' => $data['national_identity_number'], // sudah dipastikan ada
                    'family_number_card'       => $data['family_number_card'] ?? null,
                    'employee_number'          => $data['employee_number'] ?? null, // biarkan null dulu, generate sesudah create kalau kosong
                    'email'                    => $email,
                    'gender'                   => $data['gender'] ?? 'Laki-laki',
                    'title'                    => $data['position_name'] ?? null,
                    'address'                  => $data['address'] ?? null,
                    'place_of_birth'           => $data['birth_place'] ?? null,
                    'date_of_birth'            => $data['birth_date'] ?? null,
                    'kk_number'                => $data['kk_number'] ?? null,
                    'religion'                 => $data['religion'] ?? null,
                    'phone'                    => $data['phone_number'] ?? null,
                    'marital_status'           => $data['marital_status'] ?? null,
                    'dependents_count'         => $data['dependents_count'] ?? 0,
                    'education'                => $data['last_education'] ?? null,

                    'department_id'            => $data['department_id'] ?? null,
                    'position_id'              => $data['position_id'] ?? null,
                    'group_id'                 => $data['group_id'] ?? null,
                    'section_id'               => $data['section_id'] ?? null,

                    'bank_name'                => $data['bank_name'] ?? 'Mandiri',
                    'bank_account_name'        => $data['bank_account_name'] ?? null,
                    'bank_account_number'      => $data['bank_account_number'] ?? null,

                    'tmt'                      => $data['tmt'] ?? null,
                    'contract_end_date'        => $data['contract_end_date'] ?? null,

                    'salary'                   => $data['expected_salary'] ?? 0,
                    'pay_group_id'             => $data['pay_group_id'] ?? null,
                ]);

                // 5) Jika employee_number kosong, generate aman pakai ID yang sudah pasti
                if (empty($emp->employee_number)) {
                    $emp->employee_number = 'EMP-' . str_pad((string)$emp->id, 5, '0', STR_PAD_LEFT);
                    $emp->save();
                }

                DB::commit();
                $inserted++;

                Log::info('EMP_IMPORT_ROW_DONE', [
                    'req_id'      => $reqId,
                    'employee_id' => $emp->id,
                    'email'       => $emp->email
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                DB::rollBack();
                // contoh: unique constraint violation karena race condition
                Log::error('EMP_IMPORT_ROW_FAIL_DB', [
                    'req_id' => $reqId,
                    'row'    => $rowData['row_num'] ?? null,
                    'email'  => $rowData['email'] ?? null,
                    'code'   => $e->getCode(),
                    'error'  => $e->getMessage()
                ]);
                $failed[] = ['row' => $rowData['row_num'] ?? null, 'email' => $rowData['email'] ?? null, 'err' => $e->getMessage()];
                continue; // JANGAN hentikan seluruh import
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error('EMP_IMPORT_ROW_FAIL', [
                    'req_id' => $reqId,
                    'row'    => $rowData['row_num'] ?? null,
                    'email'  => $rowData['email'] ?? null,
                    'error'  => $e->getMessage()
                ]);
                $failed[] = ['row' => $rowData['row_num'] ?? null, 'email' => $rowData['email'] ?? null, 'err' => $e->getMessage()];
                continue; // lanjut baris berikutnya
            }
        }

        // ringkas hasil di flash message (opsional)
        Log::info('EMP_IMPORT_STORE_DONE', ['req_id' => $reqId, 'inserted' => $inserted, 'skipped' => $skipped, 'failed' => count($failed)]);
        session()->forget([self::S_IMPORT_ROWS, self::S_IMPORT_HARD_ERRORS, self::S_IMPORT_WARNINGS]);

        return redirect()->route('admin.employee.import.form')
            ->with('success', '✅ Import selesai. Insert: '.$inserted.' | Skipped: '.$skipped.' | Failed: '.count($failed));

    }

    /** (Opsional) Download template excel */
    public function downloadTemplate()
    {
        $path = storage_path('app/templates/employee_import_template.xlsx');
        if (!file_exists($path)) {
            return back()->with('error', 'Template belum tersedia.');
        }
        return response()->download($path, 'employee_import_template.xlsx');
    }

    /* ==================== Helpers ==================== */

    /** Mapping kolom Excel → struktur internal */
    private function mapRow($data): array
    {
        $g = fn($k) => $data[$k] ?? null;

    return [
        // identitas dasar
        'name'                     => $g('name'),
        'email'                    => $g('email'),
        'phone_number'             => $g('phone_number') ?? $g('phone'),
        'address'                  => $g('address'),
        'gender'                   => $g('gender'), // akan dinormalisasi/validasi
        'religion'                 => $g('religion'),

        // lahir
        'birth_place'              => $g('birth_place') ?? $g('place_of_birth'),
        'birth_date'               => $g('birth_date') ?? $g('date_of_birth'),

        // status keluarga & pendidikan
        'marital_status'           => $g('marital_status') ?? $g('status'),
        'dependents_count'         => $g('dependents_count'),
        'last_education'           => $g('last_education') ?? $g('education'),

        // gaji
        'expected_salary'          => $g('expected_salary') ?? $g('salary'),

        // nomor-nomor
        'national_identity_number' => $g('national_identity_number'),
        'family_number_card'       => $g('family_number_card'),
        'kk_number'                => $g('kk_number'),
        'employee_number'          => $g('employee_number'),

        // tanggal kerja
        'tmt'                      => $g('tmt'),
        'contract_end_date'        => $g('contract_end_date'),

        // referensi by id (jika sudah ada di file)
        'department_id'            => $g('department_id'),
        'section_id'               => $g('section_id'),
        'position_id'              => $g('position_id'),

        // referensi by name/code (fallback)
        'department_name'          => $g('department_name'),
        'section_name'             => $g('section_name'),
        'position_name'            => $g('position_name'),

        // bank
        'bank_name'                => $g('bank_name'),
        'bank_account_name'        => $g('bank_account_name'),
        'bank_account_number'      => $g('bank_account_number'),

        // pay group
        'pay_group_code'           => $g('pay_group_code'),
        'pay_group_name'           => $g('pay_group_name'),
        'pay_group_id'             => $g('pay_group_id'),

        // org opsional
        'group_id'                 => $g('group_id'),
    ];
    }

    private function normalizeHeader(\Illuminate\Support\Collection $header): \Illuminate\Support\Collection
    {
        return $header->map(function ($h) {
            $s = is_string($h) ? $h : (string) $h;
            $s = trim($s);
            $s = str_replace(['-', ' '], '_', $s);
            $s = preg_replace('/__+/', '_', $s);
            return mb_strtolower($s);
        });
    }


    /** Parse file Excel → [parsedRows, hardErrors, softWarnings] */
    private function parseExcel(UploadedFile $file): array
    {
            $sheet = Excel::toCollection(null, $file)->first();
        if (!$sheet || $sheet->isEmpty()) {
            return [[], [], []];
        }

        // 1) Ambil & normalisasi header sekali di sini
        $rawHeader = $sheet->first();                    // Collection baris 1
        $header    = $this->normalizeHeader($rawHeader); // sudah lowercase + snake_case
        $rows      = $sheet->skip(1);                    // mulai baris data

        // 2) Prefetch referensi pakai header normal
        $idxDeptName = $header->search('department_name');
        $idxSectName = $header->search('section_name');
        $idxPosName  = $header->search('position_name');
        $idxPgCode   = $header->search('pay_group_code');
        $idxPgName   = $header->search('pay_group_name');

        $deptNames = $idxDeptName !== false ? $rows->pluck($idxDeptName)->filter()->unique()->values() : collect();
        $sectNames = $idxSectName !== false ? $rows->pluck($idxSectName)->filter()->unique()->values() : collect();
        $posNames  = $idxPosName  !== false ? $rows->pluck($idxPosName )->filter()->unique()->values() : collect();
        $pgCodes   = $idxPgCode   !== false ? $rows->pluck($idxPgCode  )->filter()->unique()->values() : collect();
        $pgNames   = $idxPgName   !== false ? $rows->pluck($idxPgName  )->filter()->unique()->values() : collect();

        $deptMap = $deptNames->isEmpty() ? collect() : Department::whereIn('name', $deptNames)->pluck('id','name');
        $sectMap = $sectNames->isEmpty() ? collect() : Section::whereIn('name', $sectNames)->pluck('id','name');
        $posMap  = $posNames ->isEmpty() ? collect() : Position::whereIn('name', $posNames)->pluck('id','name');
        $pgByCode= $pgCodes  ->isEmpty() ? collect() : PayGroup::whereIn('code', $pgCodes)->pluck('id','code');
        $pgByName= $pgNames  ->isEmpty() ? collect() : PayGroup::whereIn('name', $pgNames)->pluck('id','name');

        $parsedRows   = [];
        $hardErrors   = [];
        $softWarnings = [];

        foreach ($rows as $i => $row) {
            $rowNum = $i + 2; // header di baris 1
            // 3) Combine dengan header yang sudah dinormalisasi
            $data   = $header->combine($row);

            $mapped = $this->mapRow($data);
            $mapped['row_num'] = $rowNum;

            // 4) Hard validator - JANGAN lupa tambahkan hasilnya ke $hardErrors
            $v = Validator::make($mapped, [
                'email'            => 'required|email|unique:employees,email|unique:users,email',
                'gender'           => 'nullable|in:Laki-laki,Perempuan',
                'marital_status'   => 'nullable|in:Sudah Kawin,Belum Kawin',
                'dependents_count' => 'nullable|integer|min:0|max:3',
            ], [], [
                'email' => 'Email',
                'national_identity_number' => 'NIK',
            ]);
            // setelah validator email:
            if (!empty($mapped['national_identity_number']) &&
                Employee::where('national_identity_number', $mapped['national_identity_number'])->exists()) {
                $hardErrors[$rowNum][] = 'NIK sudah digunakan.';
            }
            if (!empty($mapped['employee_number']) &&
                Employee::where('employee_number', $mapped['employee_number'])->exists()) {
                $hardErrors[$rowNum][] = 'Nomor karyawan sudah digunakan.';
            }


            if ($v->fails()) {
                $hardErrors[$rowNum] = $v->errors()->all();
            }
            


            // Soft warnings: kolom kosong
            $warn = [];
            foreach ([
                'name' => 'Nama', 'phone_number' => 'Telepon', 'address' => 'Alamat',
                'gender' => 'Gender', 'religion' => 'Agama', 'birth_place' => 'Tempat Lahir',
               'birth_date' => 'Tanggal Lahir', 'marital_status' => 'Status Nikah',
                'last_education' => 'Pendidikan Terakhir', 
                'expected_salary' => 'Gaji Diharapkan', 'bank_name' => 'Bank',
                'bank_account_name' => 'Nama Rekening', 'bank_account_number' => 'No Rekening',
            ] as $k => $label) {
                if (($mapped[$k] ?? null) === null || $mapped[$k] === '') {
                    $warn[] = "⚠️ Kolom <strong>{$label}</strong> kosong.";
                }
            }
            if (empty($mapped['department_name'])) $warn[] = "‼️ <strong>Department</strong> kosong. Harus di-set manual setelah import.";
            if (empty($mapped['section_id']) && empty($mapped['section_name'])) $warn[] = "‼️ <strong>Section</strong> kosong. Harus di-set manual setelah import.";
            if (empty($mapped['position_name'])) $warn[] = "‼️ <strong>Position</strong> kosong. Harus di-set manual setelah import.";

            // Resolve referensi (jika tidak ketemu → warning)
            $refWarn = $this->resolveReferencesAsWarning($mapped, $deptMap, $sectMap, $posMap, $pgByCode, $pgByName);
            if ($refWarn) $warn = array_merge($warn, $refWarn);

            if ($warn) $softWarnings[$rowNum] = $warn;

            $parsedRows[] = $mapped;
        }

        return [$parsedRows, $hardErrors, $softWarnings];
    }

    /** Buat paginator dari array (untuk preview) */
    private function paginateArray(array $items, Request $request): array
    {
        $page    = max(1, (int) $request->query('page', 1));
        $perPage = max(1, (int) $request->query('per_page', 50));

        $collection = collect($items)->values();
        $slice      = $collection->forPage($page, $perPage)->values();

        $paginator = new LengthAwarePaginator(
            $slice,
            $collection->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return [$paginator, $perPage];
    }

    /**
     * Resolve referensi via map; jika tidak ketemu → warning (tidak blok).
     * Mengubah $mapped by-ref saat match ditemukan.
     */
    private function resolveReferencesAsWarning(
        array &$mapped,
        $deptMap,
        $sectMap,
        $posMap,
        $pgByCode,
        $pgByName
    ): array {
        $warns = [];

        // Department
        if (!empty($mapped['department_name'])) {
            $depId = $deptMap[$mapped['department_name']] ?? null;
            if ($depId)  $mapped['department_id'] = $depId;
            else         $warns[] = '‼️ Department tidak ditemukan: ' . $mapped['department_name'] . ' (set manual nanti).';
        }

        // Section
        if (!empty($mapped['section_name'])) {
            $secId = $sectMap[$mapped['section_name']] ?? null;
            if ($secId)  $mapped['section_id'] = $secId;
            else         $warns[] = '‼️ Section tidak ditemukan: ' . $mapped['section_name'] . ' (set manual nanti).';
        }

        // Position
        if (!empty($mapped['position_name'])) {
            $posId = $posMap[$mapped['position_name']] ?? null;
            if ($posId)  $mapped['position_id'] = $posId;
            else         $warns[] = '‼️ Position tidak ditemukan: ' . $mapped['position_name'] . ' (set manual nanti).';
        }

        // Pay Group (code/name)
        $pgId = null;
        if (!empty($mapped['pay_group_code'])) {
            $pgId = $pgByCode[$mapped['pay_group_code']] ?? null;
        }
        if (!$pgId && !empty($mapped['pay_group_name'])) {
            $pgId = $pgByName[$mapped['pay_group_name']] ?? null;
        }
        if ($pgId)  $mapped['pay_group_id'] = $pgId;
        elseif (!empty($mapped['pay_group_code']) || !empty($mapped['pay_group_name'])) {
            $warns[] = '⚠️ Pay Group tidak ditemukan: ' . ($mapped['pay_group_code'] ?? $mapped['pay_group_name']) . ' (opsional, bisa set nanti).';
        }

        return $warns;
    }

    /** Bersihkan data sebelum insert: '' → null, cast id, normalisasi tanggal & salary */
    private function sanitizeRowForInsert(array $data): array
    {
        $nullIfEmpty = function ($v) {
            return (is_string($v) && trim($v) === '') ? null : $v;
        };

        foreach ($data as $k => $v) {
            $data[$k] = $nullIfEmpty($v);
        }

        // Kolom *_id harus int atau null
        foreach (['department_id','position_id','group_id','section_id','pay_group_id','user_id'] as $k) {
            if (!array_key_exists($k, $data)) continue;
            $data[$k] = is_null($data[$k]) ? null : (is_numeric($data[$k]) ? (int)$data[$k] : null);
        }

        // Salary numeric (boleh 0)
        if (array_key_exists('expected_salary', $data)) {
            $v = $data['expected_salary'];
            $data['expected_salary'] = ($v === null || $v === '') ? 0 : (float) preg_replace('/[^\d.]/', '', (string)$v);
        }

        // Normalisasi tanggal
        foreach (['date_of_birth','birth_date','tmt','contract_end_date'] as $dk) {
            if (!array_key_exists($dk, $data)) continue;

            if ($data[$dk] === null || $data[$dk] === '') {
                $data[$dk] = null;
                continue;
            }
            $val = $data[$dk];
            try {
                if (is_numeric($val)) {
                    $dt = XlsDate::excelToDateTimeObject($val);
                    $data[$dk] = Carbon::instance($dt)->format('Y-m-d');
                } else {
                    $data[$dk] = Carbon::parse($val)->format('Y-m-d');
                }
            } catch (\Throwable $e) {
                $data[$dk] = null;
            }
        }

        // Default bank_name
        if (empty($data['bank_name'])) $data['bank_name'] = 'Mandiri';

        // Alias: jika 'salary' yang diisi, pakai sebagai expected_salary
        if (!isset($data['expected_salary']) && isset($data['salary'])) {
            $v = $data['salary'];
            $data['expected_salary'] = ($v === null || $v === '') ? 0 : (float) preg_replace('/[^\d.]/', '', (string)$v);
        }
 
        // dependents_count → int 0–3 (default 0)
        if (array_key_exists('dependents_count', $data)) {
            $v = $data['dependents_count'];
            $v = is_numeric($v) ? (int)$v : 0;
            if ($v < 0) $v = 0; if ($v > 3) $v = 3;
            $data['dependents_count'] = $v;
        } else {
            $data['dependents_count'] = 0;
        }


        return $data;
    }
}
