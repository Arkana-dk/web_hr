@extends('layouts.master')

@section('title','Upload Data Pegawai')

@push('styles')
<style>
  .upload-card { border: 1px solid rgba(0,0,0,.06); }
  .btn-pill { border-radius: 9999px !important; padding-inline: 1rem; }
  .helper-badge { background: rgba(13,110,253,.08); color: #0d6efd; }
  .dropzone {
    border: 2px dashed rgba(0,0,0,.15);
    border-radius: 1rem;
    padding: 2rem;
    background: rgba(0,0,0,.02);
    transition: background .15s ease, border-color .15s ease;
  }
  .dropzone.dragover { background: rgba(13,110,253,.06); border-color: #0d6efd; }
  .file-meta { font-size: .9rem; }
</style>
@endpush

@section('content')
<div class="container py-4">

  <div class="row g-4">
    <div class="col-12 col-lg-8">
      <div class="card shadow-sm upload-card">
        <div class="card-header bg-white d-flex align-items-center justify-content-between">
          <div>
            <h5 class="mb-0">Upload Excel Data Pegawai</h5>
            <small class="text-muted">Format didukung: .xls, .xlsx • Maks: 20 MB</small>
          </div>
          <span class="badge helper-badge rounded-pill">Step 1 dari 2</span>
        </div>

        <div class="card-body">
          {{-- Flash via SweetAlert juga, tapi tampilkan fallback inline --}}
          @if(session('error'))
            <div class="alert alert-danger mb-3">{{ session('error') }}</div>
          @endif
          @if(session('success'))
            <div class="alert alert-success mb-3">{{ session('success') }}</div>
          @endif

          <form id="upload-form" action="{{ route('admin.employee.import.preview') }}" method="POST" enctype="multipart/form-data">
            @csrf

            {{-- Drag & Drop Area --}}
            <div id="dropzone" class="dropzone mb-3 text-center">
              <i class="fas fa-file-excel fa-2x mb-2 text-success"></i>
              <div class="mb-1"><strong>Tarik & letakkan file Excel di sini</strong></div>
              <div class="text-muted small mb-2">atau</div>
              <label for="file" class="btn btn-outline-primary btn-sm btn-pill">
                <i class="fas fa-folder-open me-1"></i> Pilih File
              </label>
              <input type="file" name="file" id="file" class="d-none" accept=".xls,.xlsx" required>
              <div id="file-info" class="file-meta mt-3 text-muted"></div>
            </div>

            {{-- Opsi tampilan preview --}}
            <div class="row g-3 mb-3">
              <div class="col-6 col-sm-4">
                <label class="form-label small text-muted mb-1">Baris per halaman</label>
                <select name="per_page" class="form-select">
                  @foreach([25,50,100,200] as $opt)
                    <option value="{{ $opt }}">{{ $opt }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-6 col-sm-8 d-flex align-items-end justify-content-end gap-2">
                <button type="button" id="btn-reset" class="btn btn-outline-secondary btn-pill">
                  <i class="fas fa-undo me-1"></i> Reset
                </button>
                <button type="submit" id="btn-preview" class="btn btn-success btn-pill">
                  <i class="fas fa-eye me-1"></i> Preview Data
                </button>
              </div>
            </div>

            <div class="d-flex flex-wrap gap-2">
              <a href="{{ route('admin.employees.index') }}" class="btn btn-outline-secondary btn-pill">
                <i class="fas fa-arrow-left me-1"></i> Kembali
              </a>

              {{-- Opsional: link template jika tersedia --}}
              <a href="{{ route('admin.employee.import.template') }}" class="btn btn-outline-primary btn-pill">
                <i class="fas fa-download me-1"></i> Download Template
              </a>
            </div>
          </form>
        </div>
      </div>
    </div>

    {{-- Panel bantuan --}}
    <div class="col-12 col-lg-4">
      <div class="card shadow-sm h-100">
        <div class="card-header bg-white">
          <h6 class="mb-0">Panduan Singkat</h6>
        </div>
        <div class="card-body">
          <ol class="mb-3">
            <li>Unduh template (opsional), isi data pegawai.</li>
            <li>Upload file Excel lalu klik <em>Preview Data</em>.</li>
            <li>Perbaiki jika ada <strong>Hard Error</strong> (email invalid/duplikat).</li>
            <li>Jika hanya <strong>Warning</strong>, tetap bisa disimpan dan dilengkapi manual.</li>
          </ol>
          <div class="small text-muted">
            Kolom penting: <code>name</code>, <code>email</code>, <code>department_name</code>, <code>position_name</code>, <code>section_name</code> (opsional).
          </div>
        </div>
      </div>
    </div>
  </div>

</div>
@endsection

@push('scripts')
  {{-- SweetAlert2 CDN (atau pindahkan ke asset pipeline proyekmu) --}}
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js" defer></script>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Flash via SweetAlert (jika ada)
      @if (session('success'))
        Swal.fire({ icon: 'success', title: 'Berhasil', text: @json(session('success')), confirmButtonText: 'OK' });
      @endif
      @if (session('error'))
        Swal.fire({ icon: 'error', title: 'Gagal', text: @json(session('error')), confirmButtonText: 'OK' });
      @endif

      const dropzone = document.getElementById('dropzone');
      const fileInput = document.getElementById('file');
      const fileInfo = document.getElementById('file-info');
      const btnReset = document.getElementById('btn-reset');
      const form = document.getElementById('upload-form');
      const btnPreview = document.getElementById('btn-preview');

      const MAX_SIZE = 20 * 1024 * 1024; // 20 MB
      const ALLOWED = ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];

      function humanSize(bytes) {
        const units = ['B','KB','MB','GB'];
        let i = 0; let num = bytes;
        while (num >= 1024 && i < units.length - 1) { num /= 1024; i++; }
        return num.toFixed(1) + ' ' + units[i];
      }

      function setFileInfo(file) {
        if (!file) { fileInfo.innerHTML = ''; return; }
        fileInfo.innerHTML = `
          <div class="d-flex align-items-center justify-content-center gap-2">
            <span class="badge bg-success rounded-pill"><i class="fas fa-check me-1"></i> ${file.name}</span>
            <span class="text-muted">${humanSize(file.size)}</span>
          </div>
        `;
      }

      // Drag & drop handlers
      ;['dragenter','dragover'].forEach(evt => {
        dropzone.addEventListener(evt, e => { e.preventDefault(); e.stopPropagation(); dropzone.classList.add('dragover'); });
      });
      ;['dragleave','drop'].forEach(evt => {
        dropzone.addEventListener(evt, e => { e.preventDefault(); e.stopPropagation(); dropzone.classList.remove('dragover'); });
      });
      dropzone.addEventListener('drop', e => {
        if (e.dataTransfer.files && e.dataTransfer.files.length) {
          fileInput.files = e.dataTransfer.files;
          setFileInfo(fileInput.files[0]);
        }
      });

      // Click to open picker
      dropzone.addEventListener('click', e => {
        const wasButton = e.target.closest('label[for="file"]');
        if (!wasButton) { /* klik area kosong tetap buka picker */ fileInput.click(); }
      });

      // On manual select
      fileInput.addEventListener('change', () => setFileInfo(fileInput.files[0]));

      // Reset
      btnReset.addEventListener('click', () => {
        fileInput.value = '';
        setFileInfo(null);
      });

      // Validate & confirm before submit
      form.addEventListener('submit', function(e) {
        const file = fileInput.files[0];
        if (!file) {
          e.preventDefault();
          Swal.fire({ icon: 'warning', title: 'Tidak ada file', text: 'Silakan pilih file Excel terlebih dahulu.' });
          return;
        }
        if (!ALLOWED.includes(file.type) && !file.name.match(/\.(xls|xlsx)$/i)) {
          e.preventDefault();
          Swal.fire({ icon: 'error', title: 'Tipe file tidak didukung', text: 'Gunakan file .xls atau .xlsx.' });
          return;
        }
        if (file.size > MAX_SIZE) {
          e.preventDefault();
          Swal.fire({ icon: 'error', title: 'File terlalu besar', text: 'Maksimum 20 MB.' });
          return;
        }

        // Konfirmasi manis
        e.preventDefault();
        Swal.fire({
          icon: 'question',
          title: 'Preview data sekarang?',
          text: 'File akan diproses dan ditampilkan pada halaman preview.',
          showCancelButton: true,
          confirmButtonText: 'Ya, lanjut',
          cancelButtonText: 'Batal'
        }).then(res => {
          if (res.isConfirmed) {
            // Optional: spinner state
            btnPreview.disabled = true;
            btnPreview.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Memproses...';
            form.submit();
          }
        });
      });
    });
  </script>
@endpush
