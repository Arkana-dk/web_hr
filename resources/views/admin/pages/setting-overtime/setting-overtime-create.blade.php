@extends('layouts.master')

@section('title', 'Pengaturan Rate Lembur')

@section('content')
<div class="container-fluid">

    <h3 class="mb-4">Pengaturan Rate Lembur</h3>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-primary text-white d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold">Edit Rate Lembur per Jam</h6>

            <!-- Tombol Enable Edit -->
            <button type="button" class="btn btn-warning btn-sm" id="enableEditBtn">
                <i class="fas fa-lock-open"></i> Enable Edit
            </button>
        </div>
        <div class="card-body">
            <form id="rateForm" action="{{ route('admin.setting_overtime.update') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="rate_per_hour">Rate Lembur per Jam (Rp)</label>
                    <input 
                        type="number" 
                        name="rate_per_hour" 
                        id="rate_per_hour" 
                        value="{{ $setting->rate_per_hour ?? 0 }}" 
                        class="form-control @error('rate_per_hour') is-invalid @enderror" 
                        disabled 
                        required
                    >

                    @error('rate_per_hour')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-success mt-3" id="saveBtn" disabled>
                    <i class="fas fa-save"></i> Simpan Perubahan
                </button>
            </form>
        </div>
    </div>

    <!-- Pindahkan script di sini supaya pasti jalan -->
    <script>
        document.getElementById('enableEditBtn').addEventListener('click', function() {
            document.getElementById('rate_per_hour').disabled = false;
            document.getElementById('saveBtn').disabled = false;

            this.innerHTML = '<i class="fas fa-lock"></i> Editing Enabled';
            this.classList.remove('btn-warning');
            this.classList.add('btn-secondary');
        });

        document.getElementById('rateForm').addEventListener('submit', function(event) {
            if (! confirm('Apakah Anda yakin ingin merubah Rate Lembur?')) {
                event.preventDefault();
            }
        });
    </script>

</div>
@endsection
