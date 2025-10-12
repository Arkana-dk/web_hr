<?php

namespace App\Imports;

use App\Models\{Employee, Department, Section, Position, User, PayGroup};
use Illuminate\Support\Facades\{DB, Hash, Log};
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\{ToModel, WithHeadingRow, WithValidation};
use PhpOffice\PhpSpreadsheet\Shared\Date as XlsDate;
use Carbon\Carbon;



class EmployeesImport implements ToModel, WithHeadingRow, WithValidation
{
    /** @var array<string,int> Cache ID by name */
    protected array $deptCache = [];
    protected array $sectCache = [];
    protected array $posCache  = [];
    protected array $payCache  = [];

    public function model(array $row)
    {
        // WHY: Trim agar kolom heading/isi rapi, menghindari salah mapping.
        $row = collect($row)->map(fn($v) => is_string($v) ? trim($v) : $v)->all();

        // Normalisasi header yang mungkin berbeda penamaan
        $map = [
            'name' => $row['name'] ?? $row['nama'] ?? null,
            'email' => $row['email'] ?? null,
            'employee_number' => $row['employee_number'] ?? $row['nip'] ?? null,
            'national_identity_number' => $row['national_identity_number'] ?? $row['nik'] ?? null,
            'gender' => $row['gender'] ?? $row['jenis_kelamin'] ?? null,
            'phone' => $row['phone'] ?? $row['telepon'] ?? null,
            'address' => $row['address'] ?? $row['alamat'] ?? null,
            'place_of_birth' => $row['place_of_birth'] ?? $row['tempat_lahir'] ?? null,
            'date_of_birth' => $row['date_of_birth'] ?? $row['tanggal_lahir'] ?? null,
            'department' => $row['department'] ?? $row['departemen'] ?? null,
            'section' => $row['section'] ?? $row['seksi'] ?? null,
            'position' => $row['position'] ?? $row['jabatan'] ?? null,
            'group' => $row['group'] ?? $row['golongan'] ?? null,
            'bank_name' => $row['bank_name'] ?? $row['nama_bank'] ?? 'Mandiri',
            'bank_account_name' => $row['bank_account_name'] ?? $row['nama_rekening'] ?? null,
            'bank_account_number' => $row['bank_account_number'] ?? $row['no_rekening'] ?? null,
            'salary' => $row['salary'] ?? $row['gaji_pokok'] ?? null,
            'pay_group' => $row['pay_group'] ?? $row['grup_gaji'] ?? null,
            'family_number_card' => $row['family_number_card'] ?? $row['no_kk'] ?? null,
            'user_id' => $row['user_id'] ?? null,
        ];

        // Parsing tanggal dari Excel serial atau string
        $map['date_of_birth'] = $this->parseExcelDate($map['date_of_birth']);

        // Resolve relasi by name → id
        $deptId = $this->getDepartmentId($map['department']);
        $sectId = $this->getSectionId($map['section']);
        $posId  = $this->getPositionId($map['position']);
        $payId  = $this->getPayGroupId($map['pay_group']);

        // Buat / ambil user (berdasarkan email)
        $userId = $map['user_id'] ?: $this->getOrCreateUserId($map['name'], $map['email']);

        // Insert employee
        try {
            return DB::transaction(function () use ($map, $deptId, $sectId, $posId, $payId, $userId) {
                return new Employee([
                    'user_id' => $userId,
                    'name' => $map['name'],
                    'national_identity_number' => $map['national_identity_number'],
                    'employee_number' => $map['employee_number'],
                    'email' => $map['email'],
                    'gender' => $map['gender'],
                    'phone' => $map['phone'],
                    'address' => $map['address'],
                    'place_of_birth' => $map['place_of_birth'],
                    'date_of_birth' => $map['date_of_birth'],
                    'department_id' => $deptId,
                    'position_id' => $posId,
                    'group_id' => $map['group'],
                    'section_id' => $sectId,
                    'bank_name' => $map['bank_name'] ?: 'Mandiri',
                    'bank_account_name' => $map['bank_account_name'],
                    'bank_account_number' => $map['bank_account_number'],
                    'salary' => $map['salary'],
                    'pay_group_id' => $payId,
                    'family_number_card' => $map['family_number_card'], // penting → kirim null kalau kosong
                ]);
            });
        } catch (\Throwable $e) {
            // WHY: log dengan konteks email agar mudah lacak
            Log::error('Gagal menyimpan data untuk email: '.$map['email'].' - '.$e->getMessage(), ['exception' => $e]);
            // Skip row (ToModel: return null = lewati)
            return null;
        }
    }

    public function rules(): array
    {
        return [
            'email' => ['required','email'],
            'name' => ['required','string'],
            'employee_number' => ['nullable','string'],
            'national_identity_number' => ['nullable','string'],
            'date_of_birth' => ['nullable'],
            'bank_account_number' => ['nullable','string'],
            'family_number_card' => ['nullable','string'],
        ];
    }

    private function parseExcelDate($val): ?string
    {
        if ($val === null || $val === '') return null;
        try {
            if (is_numeric($val)) {
                return Carbon::instance(XlsDate::excelToDateTimeObject((float) $val))->format('Y-m-d');
            }
            return Carbon::parse($val)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function getOrCreateUserId(?string $name, ?string $email): ?int
    {
        if (!$email) return null; // biarkan nullable jika skema mengizinkan
        $user = User::query()->where('email', $email)->first();
        if ($user) return $user->id;
        $user = User::create([
            'name' => $name ?: Str::before($email, '@'),
            'email' => $email,
            'password' => Hash::make(Str::random(16)), // WHY: password acak agar aman
        ]);
        return $user->id;
    }

    private function getDepartmentId($name): ?int
    {
        if (!$name) return null;
        $key = Str::lower($name);
        if (isset($this->deptCache[$key])) return $this->deptCache[$key];
        $m = Department::firstOrCreate(['name' => $name]);
        return $this->deptCache[$key] = $m->id;
    }

    private function getSectionId($name): ?int
    {
        if (!$name) return null;
        $key = Str::lower($name);
        if (isset($this->sectCache[$key])) return $this->sectCache[$key];
        $m = Section::firstOrCreate(['name' => $name]);
        return $this->sectCache[$key] = $m->id;
    }

    private function getPositionId($name): ?int
    {
        if (!$name) return null;
        $key = Str::lower($name);
        if (isset($this->posCache[$key])) return $this->posCache[$key];
        $m = Position::firstOrCreate(['name' => $name]);
        return $this->posCache[$key] = $m->id;
    }

    private function getPayGroupId($name): ?int
    {
        if (!$name) return null;
        $key = Str::lower($name);
        if (isset($this->payCache[$key])) return $this->payCache[$key];
        $m = PayGroup::firstOrCreate(['name' => $name]);
        return $this->payCache[$key] = $m->id;
    }
}

