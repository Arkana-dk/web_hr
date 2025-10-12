<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WorkScheduleExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Sesuaikan policy/permission kamu di sini bila perlu
        return true;
    }

    public function rules(): array
    {
        return [
            'department_id' => ['required','integer','exists:departments,id'],
            'section_id'    => ['nullable','integer','exists:sections,id'],
            'position_id'   => ['nullable','integer','exists:positions,id'],
            'month'         => ['required','date_format:Y-m'],
        ];
    }

    public function messages(): array
    {
        return [
            'month.date_format' => 'Format bulan harus Y-m, contoh: 2025-09.',
        ];
    }
}
