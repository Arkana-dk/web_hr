<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WorkScheduleImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // atau cek role user kalau mau dibatasi
    }

    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:xlsx,xls|max:5120', 
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'File jadwal wajib diunggah.',
            'file.mimes'    => 'Format file harus Excel (.xlsx / .xls).',
            'file.max'      => 'Ukuran file maksimal 5MB.',
        ];
    }
}
