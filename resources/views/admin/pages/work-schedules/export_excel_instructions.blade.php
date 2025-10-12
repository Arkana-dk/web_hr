@php
    $ts = \Carbon\Carbon::parse($meta['generated_at'] ?? now())->format('Y-m-d H:i');
@endphp
<table border="1" cellspacing="0" cellpadding="4">
    {{-- Title --}}
    <tr><td colspan="8"><strong>Template Jadwal Kerja</strong></td></tr>

    {{-- Meta --}}
    <tr><td><strong>Bulan</strong></td><td colspan="7">{{ $meta['month'] ?? '' }}</td></tr>
    <tr><td><strong>Department</strong></td><td colspan="7">{{ $meta['department'] ?? '' }}</td></tr>
    <tr><td><strong>Section</strong></td><td colspan="7">{{ $meta['section'] ?? '-' }}</td></tr>
    <tr><td><strong>Position</strong></td><td colspan="7">{{ $meta['position'] ?? '-' }}</td></tr>
    <tr><td><strong>Dibuat</strong></td><td colspan="7">{{ $ts }}</td></tr>

    {{-- Spacer --}}
    <tr><td colspan="8"></td></tr>

    {{-- Petunjuk --}}
    <tr><td colspan="8"><strong>Petunjuk</strong></td></tr>
    <tr><td colspan="8">1) Isi jadwal pada Sheet <strong>JADWAL</strong>.</td></tr>
    <tr><td colspan="8">2) Kolom tanggal dibuat otomatis sesuai bulan yang dipilih.</td></tr>
    <tr><td colspan="8">3) Masukkan <em>Jadwal Shifts</em> (pakai kolom <strong>name</strong> dari daftar shift) pada sel tanggal.</td></tr>
    <tr><td colspan="8">4) Jangan ubah header/struktur kolom.</td></tr>

    {{-- Spacer --}}
    <tr><td colspan="8"></td></tr>

    {{-- Daftar Shift --}}
    <tr>
        <td colspan="8"><strong>Daftar Shift (aktif)</strong></td>
    </tr>
    <tr>
        <td><strong>ShiftCode (name)</strong></td>
        <td><strong>Start</strong></td>
        <td><strong>End</strong></td>
        <td colspan="5"></td>
    </tr>
    @foreach($shifts as $s)
        <tr>
            <td>{{ $s->name }}</td>
            <td>{{ $s->start_time }}</td>
            <td>{{ $s->end_time }}</td>
            <td colspan="5"></td>
        </tr>
    @endforeach

    {{-- Spacer --}}
    <tr><td colspan="8"></td></tr>

    {{-- Daftar Karyawan --}}
    <tr><td colspan="8"><strong>Daftar Karyawan (aktif sesuai filter)</strong></td></tr>
    <tr>
        <td><strong>EmployeeID</strong></td>
        <td><strong>NIK</strong></td>
        <td><strong>Nama</strong></td>
        <td><strong>Department</strong></td>
        <td><strong>Section</strong></td>
        <td><strong>Position</strong></td>
        <td colspan="2"></td>
    </tr>
    @foreach($employees as $e)
        <tr>
            <td>{{ $e->employee_number }}</td>
            <td>{{ $e->national_identity_number }}</td>
            <td>{{ $e->name }}</td>
            <td>{{ optional($e->department)->name ?? '' }}</td>
            <td>{{ optional($e->section)->name ?? '' }}</td>
            <td>{{ optional($e->position)->name ?? '' }}</td>
            <td colspan="2"></td>
        </tr>
    @endforeach
</table>
