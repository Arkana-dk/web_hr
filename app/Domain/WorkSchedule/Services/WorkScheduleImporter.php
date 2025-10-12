<?php

namespace App\Domain\WorkSchedule\Services;

use App\Domain\WorkSchedule\Contracts\WorkScheduleImporterContract;
use App\Domain\WorkSchedule\DTOs\WorkScheduleParseResult;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\WorkSchedule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class WorkScheduleImporter implements WorkScheduleImporterContract
{
    public function parse(Collection $sheet): WorkScheduleParseResult
    {
        $dateRow = $sheet[3] ?? null;                 // sama seperti controller lama
        if (!$dateRow) {
            throw new \RuntimeException('Baris tanggal tidak ditemukan di sheet.');
        }

        $dates = collect($dateRow)->slice(3)->values();
        $invalidEmployees = [];
        $validSchedules   = [];

        foreach ($sheet->slice(4) as $rowIndex => $row) {
            $employeeRaw    = $row[1] ?? '';
            $employeeNumber = trim(preg_replace('/[\x00-\x1F\x7F]/u', '', (string)$employeeRaw));
            if (!$employeeNumber) continue;

            $employee = Employee::whereRaw('TRIM(employee_number) = ?', [$employeeNumber])->first();
            if (!$employee) { $invalidEmployees[] = $employeeNumber; continue; }

            $cells = collect($row)->slice(3)->values();

            foreach ($cells as $i => $value) {
                if (empty($value) || !isset($dates[$i])) continue;

                $code = ShiftCodeParser::fromCell((string)$value);
                if (!$code) continue;

                $shift = Shift::where('name', 'like', "%[{$code}]%")->first();
                if (!$shift) { Log::warning("Shift tidak ditemukan: {$code}"); continue; }

                // normalisasi tanggal
                $excelDate = $dates[$i];
                $workDate = null;

                try {
                    if ($excelDate instanceof \DateTimeInterface) {
                        $workDate = $excelDate->format('Y-m-d');
                    } elseif (is_numeric($excelDate)) {
                        $workDate = ExcelDate::excelToDateTimeObject($excelDate)->format('Y-m-d');
                    } elseif (is_string($excelDate)) {
                        $parsed = strtotime(trim($excelDate));
                        $workDate = $parsed ? date('Y-m-d', $parsed) : null;
                        if ($workDate === '1970-01-01') { $workDate = null; }
                    }
                } catch (\Throwable $e) {
                    Log::error("Tanggal invalid r{$rowIndex} c{$i}: ".$e->getMessage());
                }

                if (!$workDate) continue;

                // hindari duplikasi exact
                $exists = WorkSchedule::where('employee_id', $employee->id)
                    ->whereDate('work_date', $workDate)
                    ->where('shift_id', $shift->id)
                    ->exists();

                if ($exists) continue;

                $validSchedules[] = [
                    'employee_id'     => $employee->id,
                    'employee_number' => $employee->employee_number,
                    'employee_name'   => $employee->name,
                    'work_date'       => $workDate,
                    'shift_id'        => $shift->id,
                    'shift_name'      => $shift->name,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ];
            }
        }

        return new WorkScheduleParseResult($validSchedules, $invalidEmployees);
    }

    /** @inheritDoc */
    public function persist(array $validSchedules): array
    {
        $created = 0; $updated = 0;

        foreach ($validSchedules as $schedule) {
            $key = [
                'employee_id' => $schedule['employee_id'],
                'work_date'   => $schedule['work_date'],
            ];

            $existing = WorkSchedule::where($key)->first();
            if ($existing) {
                $existing->update(['shift_id' => $schedule['shift_id']]);
                $updated++;
            } else {
                WorkSchedule::create($key + ['shift_id' => $schedule['shift_id']]);
                $created++;
            }
        }

        return compact('created','updated');
    }
}
