<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Position;
use App\Models\User;
use App\Models\Group;
use App\Models\Section;
use App\Models\PayGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as XlsDate;


//========= cek debug gate superadmin ========//



class EmployeeController extends Controller
{

    public function __construct()
    {
        // READ (index & show) → boleh super-admin atau yang punya hr.employee.view_basic
        $this->middleware('role_or_permission:super-admin|hr.employee.view_basic')
             ->only(['index', 'show']);

        // WRITE (create, store, edit, update, destroy) → butuh hr.employee.manage
        $this->middleware('role_or_permission:super-admin|hr.employee.manage')
             ->only(['create','store','edit','update','destroy']);
    }

    public function index(Request $request)
    {
        // Log kecil (opsional)
        $total = Employee::count();
        Log::info('EMP_INDEX', ['total_employees' => $total, 'q' => $request->search]);

        // Query dasar + eager load biar anti N+1
        $query = Employee::query()
            ->with([
                'department',        // langsung eager load dept
                'section',
                'group',             // organisasi (bukan Pay Group)
                'position',
                'payGroup',          // Pay Group payroll
            ]);

        // ===== PENCARIAN (nama / nomor karyawan / email) =====
        if ($term = trim((string) $request->get('search', ''))) {
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                ->orWhere('employee_number', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%");
            });
        }

        // (Opsional) contoh filter tambahan jika nanti kamu tambahkan dropdown di UI:
        // if ($request->filled('department_id')) {
        //     $query->where('department_id', $request->integer('department_id'));
        // }
        // if ($request->filled('position_id')) {
        //     $query->where('position_id', $request->integer('position_id'));
        // }
        // if ($request->filled('group_id')) {
        //     $query->where('group_id', $request->integer('group_id'));
        // }

        $employees = $query
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString(); // penting: bawa ?search= saat pindah halaman

        // kirimkan juga nilai "search" biar sticky di input lama yang pakai $search
        return view('admin.pages.employee.index', [
            'employees' => $employees,
            'search'    => $request->get('search', ''),
        ]);
    }
    public function show(Employee $employee)
    {
        Log::info('EMP_SHOW', ['employee_id' => $employee->id]);
        return view('admin.pages.employee.show', compact('employee'));
    }

    public function create()
    {
        $departments = Department::with(['sections.positions'])->get();
        $sections    = Section::all();
        $groups      = Group::orderBy('name')->get();
        $payGroups   = PayGroup::orderBy('name')->get();

        // deptPositions = { dept_id: [ {id, name}, ... ] }
        $deptPositions = [];
        foreach ($departments as $department) {
            $positionSet = collect();
            foreach ($department->sections as $section) {
                $positionSet = $positionSet->merge($section->positions);
            }
            $deptPositions[$department->id] = $positionSet
                ->unique('id')
                ->map(fn($pos) => ['id' => $pos->id, 'name' => $pos->name])
                ->values()
                ->all();
        }

        Log::info('EMP_CREATE_FORM', [
            'departments' => $departments->count(),
            'sections'    => $sections->count(),
            'groups'      => $groups->count(),
            'pay_groups'  => $payGroups->count(),
        ]);

        return view('admin.pages.employee.create', compact(
            'departments', 'sections', 'groups', 'payGroups', 'deptPositions'
        ));
    }

    public function store(Request $request)
    {
        $reqId = (string) Str::uuid();
        Log::info('EMP_STORE_START', [
            'req_id'     => $reqId,
            'actor_id'   => optional($request->user())->id,
            'actor_role' => optional($request->user())->role,
            'db_conn'    => config('database.default'),
            'db_name'    => config('database.connections.'.config('database.default').'.database'),
            'ip'         => $request->ip(),
        ]);

        $data = $request->validate([
            'name'                      => 'required|string|max:255',
            'national_identity_number'  => 'required|string|max:255|unique:employees,national_identity_number',
            'email'                     => 'required|email|unique:users,email|unique:employees,email',
            'password'                  => 'required|string|min:6',
            'title'                     => 'nullable|string|max:255',
            'department_id'             => 'required|exists:departments,id',
            'position_id'               => 'required|exists:positions,id',
            'gender'                    => 'required|in:Laki-laki,Perempuan',
            'address'                   => 'required|string',
            'place_of_birth'            => 'required|string',
            'date_of_birth'             => 'required|date',
            'family_number_card'        => 'required|numeric',
            'religion'                  => 'required|string',
            'phone'                     => 'required|string',
            'marital_status'            => ['required', Rule::in(['Sudah Kawin', 'Belum Kawin'])],
            'education'                 => 'required|string',
            'tmt'                       => 'required|date',
            'contract_end_date'         => 'required|date|after_or_equal:tmt',
            'salary'                    => 'required|numeric',
            'photo'                     => 'nullable|mimes:jpg,jpeg,png|max:2048',
            'bank_account_name'         => 'required|string|max:255',
            'bank_account_number'       => 'required|string|max:50',
            'group_id'                  => 'nullable|exists:groups,id',
            'section_id'                => 'nullable|exists:sections,id',
            'pay_group_id'              => 'nullable|exists:pay_groups,id',
            'dependents_count'          => 'required|numeric',
        ]);

        DB::beginTransaction();
        try {
            // Buat user (admin hanya boleh buat role 'user')
            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => Hash::make($data['password']),
                'role'     => 'user',
                'photo'    => $request->file('photo')
                                ? $request->file('photo')->store('users', 'public')
                                : null,
            ]);
            Log::info('USER_CREATED', ['req_id' => $reqId, 'user_id' => $user->id, 'email' => $user->email]);

            // Siapkan dan simpan employee
            $employeeData = $data;
            $employeeData['user_id'] = $user->id;

            $nextId = (int) (Employee::max('id') ?? 0) + 1;
            $employeeData['employee_number'] = 'EMP-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);

            if ($request->hasFile('photo')) {
                $employeeData['photo'] = $request->file('photo')->store('employees', 'public');
            }

            $employee = Employee::create($employeeData);

            Log::info('EMP_CREATED', [
                'req_id'       => $reqId,
                'employee_id'  => $employee->id,
                'user_id'      => $user->id,
                'pay_group_id' => $employee->pay_group_id,
                'group_id'     => $employee->group_id,
            ]);

            DB::commit();
            Log::info('EMP_COMMITTED', ['req_id' => $reqId, 'employee_id' => $employee->id]);

            return redirect()->route('admin.employees.index')
                ->with('success', 'Pegawai + User berhasil dibuat.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('EMP_STORE_FAIL', ['req_id' => $reqId, 'error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Gagal menyimpan data: ' . $e->getMessage()])->withInput();
        }
    }

    public function edit(Employee $employee)
    {
        $departments = Department::with(['sections.positions'])->get();
        $sections    = Section::all();
        $groups      = Group::orderBy('name')->get();
        $payGroups   = PayGroup::orderBy('name')->get();

        $deptPositions = [];
        foreach ($departments as $department) {
            $positionSet = collect();
            foreach ($department->sections as $section) {
                $positionSet = $positionSet->merge($section->positions);
            }
            $deptPositions[$department->id] = $positionSet
                ->unique('id')
                ->map(fn($pos) => ['id' => $pos->id, 'name' => $pos->name])
                ->values()
                ->all();
        }

        Log::info('EMP_EDIT_FORM', [
            'employee_id' => $employee->id,
            'groups'      => $groups->count(),
            'pay_groups'  => $payGroups->count(),
        ]);

        return view('admin.pages.employee.edit', compact(
            'employee', 'departments', 'sections', 'groups', 'payGroups', 'deptPositions'
        ));
    }

    public function update(Request $request, Employee $employee)
    {
        // EDIT EMPLOYEE-ONLY: tidak sentuh users/email/password
        $reqId = (string) Str::uuid();
        Log::info('EMP_UPDATE_START', [
            'req_id'      => $reqId,
            'employee_id' => $employee->id,
            'actor_id'    => optional($request->user())->id,
            'db_conn'     => config('database.default'),
            'db_name'     => config('database.connections.'.config('database.default').'.database'),
        ]);

        $data = $request->validate([
            'name'                      => 'required|string|max:255',
            'national_identity_number'  => ['required','string','max:255', Rule::unique('employees','national_identity_number')->ignore($employee->id)],
            'title'                     => 'nullable|string|max:255',
            'department_id'             => 'required|exists:departments,id',
            'position_id'               => 'required|exists:positions,id',
            'gender'                    => 'required|in:Laki-laki,Perempuan',
            'address'                   => 'required|string',
            'place_of_birth'            => 'required|string',
            'date_of_birth'             => 'required|date',
            'family_number_card'        => 'required|numeric',
            'religion'                  => 'required|string',
            'phone'                     => 'required|string',
            'marital_status'            => ['required', Rule::in(['Sudah Kawin', 'Belum Kawin'])],
            'education'                 => 'required|string',
            'tmt'                       => 'required|date',
            'contract_end_date'         => 'required|date|after_or_equal:tmt',
            'salary'                    => 'required|numeric',
            'photo'                     => 'nullable|mimes:jpg,jpeg,png|max:2048',
            'bank_account_name'         => 'required|string|max:255',
            'bank_account_number'       => 'required|string|max:50',
            'group_id'                  => 'nullable|exists:groups,id',
            'section_id'                => 'nullable|exists:sections,id',
            'pay_group_id'              => 'nullable|exists:pay_groups,id',
            'dependents_count'          => 'required|numeric',
        ]);

        DB::beginTransaction();
        try {
            if ($request->hasFile('photo')) {
                if ($employee->photo) {
                    Storage::disk('public')->delete($employee->photo);
                }
                $data['photo'] = $request->file('photo')->store('employees', 'public');
            }

            $employee->update($data);

             // kalau ada input password baru, update juga di tabel users
        if ($request->filled('password') && $employee->user) {
            $employee->user->update([
                'password' => Hash::make($request->password),
            ]);
            Log::info('EMP_PASSWORD_UPDATED', ['req_id' => $reqId, 'user_id' => $employee->user->id]);
        }

            Log::info('EMP_UPDATED', [
                'req_id'       => $reqId,
                'employee_id'  => $employee->id,
                'pay_group_id' => $employee->pay_group_id,
                'group_id'     => $employee->group_id,
                'user_touched' => false,
            ]);

            DB::commit();
            Log::info('EMP_UPDATE_COMMITTED', ['req_id' => $reqId, 'employee_id' => $employee->id]);

            return redirect()->route('admin.employees.index')
                ->with('success', 'Data pegawai berhasil diperbarui.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('EMP_UPDATE_FAIL', ['req_id' => $reqId, 'error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Gagal memperbarui data: ' . $e->getMessage()])->withInput();
        }
    }

    public function destroy(Employee $employee)
    {
        $reqId = (string) Str::uuid();
        Log::warning('EMP_DELETE_START', ['req_id' => $reqId, 'employee_id' => $employee->id]);

        if ($employee->photo) {
            Storage::disk('public')->delete($employee->photo);
            Log::info('EMP_PHOTO_DELETED', ['req_id' => $reqId, 'path' => $employee->photo]);
        }

        // (opsional) hapus user terkait
        if ($employee->user) {
            $uid = $employee->user->id;
            $employee->user->delete();
            Log::info('EMP_USER_DELETED', ['req_id' => $reqId, 'user_id' => $uid]);
        }

        $employee->delete();
        Log::warning('EMP_DELETED', ['req_id' => $reqId, 'employee_id' => $employee->id]);

        return redirect()
            ->route('admin.employees.index')
            ->with('success', 'Pegawai (dan user terkait) berhasil dihapus.');
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('selected_ids', []);
        Log::warning('EMP_BULK_DELETE_START', ['ids' => $ids, 'count' => count($ids)]);

        if (empty($ids)) {
            return redirect()->back()->with('error', 'Tidak ada data yang dipilih.');
        }

        $employees = Employee::whereIn('id', $ids)->get();
        foreach ($employees as $employee) {
            if ($employee->photo) {
                Storage::disk('public')->delete($employee->photo);
            }
            if ($employee->user) {
                $employee->user->delete();
            }
            $employee->delete();
        }

        Log::warning('EMP_BULK_DELETE_DONE', ['deleted' => count($ids)]);
        return redirect()->route('admin.employees.index')
            ->with('success', 'Beberapa data pegawai berhasil dihapus.');
    }

    // ─────────────────────────────────────────────────────────────
        // 1) FORM (biarkan seperti sebelumnya)
        public function importForm()
        {
            Log::info('EMP_IMPORT_FORM');
            return view('admin.pages.employee-import.index');
        }


    // ─────────────────────────────────────────────────────────────
// 2) PREVIEW: baca Excel → normalisasi header → map → validasi → session
public function importPreview(Request $request)
{
    $reqId = (string) Str::uuid();
    Log::info('EMP_IMPORT_PREVIEW_START', [
        'req_id'  => $reqId,
        'file_ok' => $request->hasFile('file'),
        'name'    => $request->file('file')?->getClientOriginalName(),
    ]);

    $request->validate([
        'file' => 'required|file|mimes:xls,xlsx|max:20480',
    ]);

    try {
        $sheet = Excel::toCollection(null, $request->file('file'))->first();
        if (!$sheet || $sheet->isEmpty()) {
            Log::warning('EMP_IMPORT_PREVIEW_EMPTY', ['req_id' => $reqId]);
            return back()->with('error', 'File kosong atau format tidak sesuai.');
        }

        // Normalisasi header SEKALI di sini
        $rawHeader = $sheet->first();              // baris 1
        $header    = $this->normalizeHeader($rawHeader);
        $rows      = $sheet->skip(1);

        // Prefetch referensi (hemat query)
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

        $parsedRows = [];
        $errors     = [];

        foreach ($rows as $i => $row) {
            $rowNum = $i + 2; // header = baris 1
            $data   = $header->combine($row); // pakai header yang sudah dinormalisasi

            // Map kolom Excel → struktur internal lengkap (ikut DB)
            $mapped = $this->mapRow($data);

            // Normalisasi nilai (tanggal Excel serial → Y-m-d, salary, clamp dependents 0–3, enum, dll)
            $mapped = $this->sanitizeRowForInsert($mapped);

            // Resolve referensi (by name/code → *_id), jika tidak ketemu → error (blok)
            $refErr = $this->resolveReferencesForPreview($mapped, $deptMap, $sectMap, $posMap, $pgByCode, $pgByName);
            if (!empty($refErr)) {
                $errors[$rowNum] = array_merge($errors[$rowNum] ?? [], $refErr);
            }

            // Hard validator (email wajib valid & unik, enum valid, dependents 0–3)
            $v = Validator::make($mapped, [
                'name'             => 'required',
                'email'            => 'required|email|unique:employees,email|unique:users,email',
                'gender'           => 'nullable|in:Laki-laki,Perempuan',
                'marital_status'   => 'nullable|in:Sudah Kawin,Belum Kawin',
                'dependents_count' => 'nullable|integer|min:0|max:3',
            ], [], [
                'email' => 'Email',
            ]);
            if ($v->fails()) {
                $errors[$rowNum] = array_merge($errors[$rowNum] ?? [], $v->errors()->all());
            }

            // Tambahan safety: cek unik NIK / nomor karyawan jika diisi
            if (!empty($mapped['national_identity_number']) &&
                Employee::where('national_identity_number', $mapped['national_identity_number'])->exists()) {
                $errors[$rowNum][] = '❌ NIK sudah digunakan.';
            }
            if (!empty($mapped['employee_number']) &&
                Employee::where('employee_number', $mapped['employee_number'])->exists()) {
                $errors[$rowNum][] = '❌ Nomor karyawan sudah digunakan.';
            }

            $parsedRows[] = $mapped;
        }

        // Simpan ke session untuk step Store
        session([
            'import_rows'   => $parsedRows,
            'import_errors' => $errors,
        ]);

        Log::info('EMP_IMPORT_PREVIEW_DONE', [
            'req_id' => $reqId,
            'rows'   => count($parsedRows),
            'errors' => count($errors),
        ]);

        // Tampilkan preview (pakai view lama kamu)
        return view('admin.pages.employee-import.preview', [
            'rows'   => $parsedRows,
            'errors' => $errors,
        ]);
    } catch (\Throwable $e) {
        Log::error('EMP_IMPORT_PREVIEW_FAIL', ['req_id' => $reqId, 'error' => $e->getMessage()]);
        return back()->with('error', 'Gagal membaca file Excel: ' . $e->getMessage());
    }
}

    // ─────────────────────────────────────────────────────────────
// 3) STORE: commit yang lolos preview
public function importStore(Request $request)
{
    $reqId = (string) Str::uuid();
    Log::info('EMP_IMPORT_STORE_START', [
        'req_id'  => $reqId,
        'db_conn' => config('database.default'),
        'db_name' => config('database.connections.'.config('database.default').'.database'),
    ]);

    $rows   = session('import_rows');
    $errors = session('import_errors');

    if (!$rows || count($rows) === 0) {
        Log::warning('EMP_IMPORT_STORE_NODATA', ['req_id' => $reqId]);
        return redirect()->route('admin.employee.import.form')
            ->with('error', 'Data tidak ditemukan. Silakan upload ulang.');
    }

    if (!empty($errors)) {
        Log::warning('EMP_IMPORT_STORE_HAS_ERRORS', ['req_id' => $reqId, 'errors' => $errors]);
        return redirect()->route('admin.employee.import.form')
            ->with('error', 'Data tidak valid. Silakan perbaiki dan upload ulang.');
    }

    $inserted = 0;

    foreach ($rows as $data) {
        DB::beginTransaction();
        try {
            // Safety hard check lagi
            if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)
                || Employee::where('email', $data['email'])->exists()
                || User::where('email', $data['email'])->exists()
            ) {
                DB::rollBack();
                Log::info('EMP_IMPORT_SKIP_HARD', ['req_id' => $reqId, 'email' => $data['email'] ?? null]);
                continue;
            }

            // Buat user
            $user = User::create([
                'name'     => $data['name'] ?: '(Tanpa Nama)',
                'email'    => $data['email'],
                'password' => Hash::make('123456789'),
                'role'     => 'user',
            ]);

            $nextId = (int) (Employee::max('id') ?? 0) + 1;

            // Insert employee (lengkap sesuai kolom DB)
            $emp = Employee::create([
                'user_id'                  => $user->id,
                'role'                     => 'employee',
                'name'                     => $data['name'] ?: '(Tanpa Nama)',
                'national_identity_number' => $data['national_identity_number'] ?? ('national_identity_number-' . str_pad($nextId, 5, '0', STR_PAD_LEFT)),
                'family_number_card'       => $data['family_number_card'] ?? null,
                'email'                    => $data['email'],
                'gender'                   => $data['gender'] ?? 'Laki-laki', // default aman utk NOT NULL
                'title'                    => $data['position_name'] ?? null,
                'photo'                    => null,
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

                'tmt'                      => $data['tmt'] ?? null,
                'contract_end_date'        => $data['contract_end_date'] ?? null,

                'bank_name'                => $data['bank_name'] ?? 'Mandiri',
                'bank_account_name'        => $data['bank_account_name'] ?? null,
                'bank_account_number'      => $data['bank_account_number'] ?? null,

                'salary'                   => $data['expected_salary'] ?? 0,
                'employee_number'          => $data['employee_number'] ?? ('EMP-' . str_pad($nextId, 5, '0', STR_PAD_LEFT)),
                'pay_group_id'             => $data['pay_group_id'] ?? null,
            ]);

            DB::commit();
            $inserted++;
            Log::info('EMP_IMPORT_ROW_DONE', ['req_id' => $reqId, 'employee_id' => $emp->id, 'email' => $emp->email]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('EMP_IMPORT_ROW_FAIL', ['req_id' => $reqId, 'email' => $data['email'] ?? null, 'error' => $e->getMessage()]);
            return redirect()->route('admin.employee.import.form')
                ->with('error', 'Gagal menyimpan data untuk email: ' . ($data['email'] ?? '-') . ' - ' . $e->getMessage());
        }
    }

    Log::info('EMP_IMPORT_STORE_DONE', ['req_id' => $reqId, 'inserted' => $inserted]);

    return redirect()->route('admin.employee.import.form')
        ->with('success', '✅ Berhasil mengimport semua data pegawai. (Insert: '.$inserted.')');
}
// ─────────────────────────────────────────────────────────────
// 4) HELPER: normalisasi header → snake_case lowercase
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

// 5) HELPER: map kolom Excel → struktur internal lengkap
private function mapRow($data): array
{
    $g = fn($k) => $data[$k] ?? null;

    return [
        'name'                     => $g('name'),
        'email'                    => $g('email'),
        'phone_number'             => $g('phone_number') ?? $g('phone'),
        'address'                  => $g('address'),
        'gender'                   => $g('gender'),
        'religion'                 => $g('religion'),

        'birth_place'              => $g('birth_place') ?? $g('place_of_birth'),
        'birth_date'               => $g('birth_date') ?? $g('date_of_birth'),

        'marital_status'           => $g('marital_status') ?? $g('status'),
        'dependents_count'         => $g('dependents_count'),
        'last_education'           => $g('last_education') ?? $g('education'),

        'expected_salary'          => $g('expected_salary') ?? $g('salary'),

        'national_identity_number' => $g('national_identity_number'),
        'family_number_card'       => $g('family_number_card'),
        'kk_number'                => $g('kk_number'),
        'employee_number'          => $g('employee_number'),

        'tmt'                      => $g('tmt'),
        'contract_end_date'        => $g('contract_end_date'),

        'department_id'            => $g('department_id'),
        'section_id'               => $g('section_id'),
        'position_id'              => $g('position_id'),

        'department_name'          => $g('department_name'),
        'section_name'             => $g('section_name'),
        'position_name'            => $g('position_name'),

        'bank_name'                => $g('bank_name'),
        'bank_account_name'        => $g('bank_account_name'),
        'bank_account_number'      => $g('bank_account_number'),

        'pay_group_code'           => $g('pay_group_code'),
        'pay_group_name'           => $g('pay_group_name'),
        'pay_group_id'             => $g('pay_group_id'),

        'group_id'                 => $g('group_id'),
    ];
}

// 6) HELPER: normalisasi nilai (tanggal Excel serial → Y-m-d, salary, enum, clamp dependents)
private function sanitizeRowForInsert(array $data): array
{
    $nullIfEmpty = function ($v) {
        return (is_string($v) && trim($v) === '') ? null : $v;
    };
    foreach ($data as $k => $v) $data[$k] = $nullIfEmpty($v);

    // *_id → int/null
    foreach (['department_id','position_id','group_id','section_id','pay_group_id'] as $k) {
        if (!array_key_exists($k, $data)) continue;
        $data[$k] = is_null($data[$k]) ? null : (is_numeric($data[$k]) ? (int)$data[$k] : null);
    }

    // Salary
    if (array_key_exists('expected_salary', $data)) {
        $v = $data['expected_salary'];
        $data['expected_salary'] = ($v === null || $v === '') ? 0 : (float) preg_replace('/[^\d.]/', '', (string)$v);
    }

    // Tanggal (support Excel serial & string)
    foreach (['date_of_birth','birth_date','tmt','contract_end_date'] as $dk) {
        if (!array_key_exists($dk, $data)) continue;
        $val = $data[$dk];
        if ($val === null || $val === '') { $data[$dk] = null; continue; }
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

    // Enum normalisasi (longgar)
    if (isset($data['gender']) && $data['gender'] !== null) {
        $g = mb_strtolower(trim((string)$data['gender']));
        if (in_array($g, ['l','laki-laki','male','m']))       $data['gender'] = 'Laki-laki';
        elseif (in_array($g, ['p','perempuan','female','f'])) $data['gender'] = 'Perempuan';
        else $data['gender'] = null;
    }
    if (isset($data['marital_status']) && $data['marital_status'] !== null) {
        $s = mb_strtolower(trim((string)$data['marital_status']));
        if (in_array($s, ['sudah kawin','kawin','k','menikah'])) $data['marital_status'] = 'Sudah Kawin';
        elseif (in_array($s, ['belum kawin','tk','single','belum menikah'])) $data['marital_status'] = 'Belum Kawin';
    }

    // dependents_count clamp 0–3 (default 0)
    if (array_key_exists('dependents_count', $data)) {
        $v = $data['dependents_count'];
        $v = is_numeric($v) ? (int)$v : 0;
        if ($v < 0) $v = 0; if ($v > 3) $v = 3;
        $data['dependents_count'] = $v;
    } else {
        $data['dependents_count'] = 0;
    }

    // Default bank name
    if (empty($data['bank_name'])) $data['bank_name'] = 'Mandiri';

    return $data;
}

// 7) HELPER: resolve referensi (by name/code) → *_id, jika tak ketemu jadikan error preview
private function resolveReferencesForPreview(
    array &$mapped,
    $deptMap, $sectMap, $posMap, $pgByCode, $pgByName
): array {
    $errs = [];

    if (!empty($mapped['department_name'])) {
        $id = $deptMap[$mapped['department_name']] ?? null;
        if ($id) $mapped['department_id'] = $id; else $errs[] = '❌ Departemen tidak ditemukan: '.$mapped['department_name'];
    }

    if (!empty($mapped['section_name'])) {
        $id = $sectMap[$mapped['section_name']] ?? null;
        if ($id) $mapped['section_id'] = $id; else $errs[] = '❌ Section tidak ditemukan: '.$mapped['section_name'];
    }

    if (!empty($mapped['position_name'])) {
        $id = $posMap[$mapped['position_name']] ?? null;
        if ($id) $mapped['position_id'] = $id; else $errs[] = '❌ Jabatan tidak ditemukan: '.$mapped['position_name'];
    }

    $pgId = null;
    if (!empty($mapped['pay_group_code'])) $pgId = $pgByCode[$mapped['pay_group_code']] ?? null;
    if (!$pgId && !empty($mapped['pay_group_name'])) $pgId = $pgByName[$mapped['pay_group_name']] ?? null;
    if ($pgId) $mapped['pay_group_id'] = $pgId;
    elseif (!empty($mapped['pay_group_code']) || !empty($mapped['pay_group_name'])) {
        $errs[] = '❌ Pay Group tidak ditemukan: '.($mapped['pay_group_code'] ?? $mapped['pay_group_name']);
    }

    return $errs;
}
    
}
