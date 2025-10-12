@extends('layouts.master')
@section('title','Calendar Settings')

@section('content')
<div class="container-fluid">
  <h1 class="h3 text-gray-800 mb-4">Calendar Settings</h1>
  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  <div class="card mb-4">
    <div class="card-header">Tambah / Edit Jadwal</div>
    <div class="card-body">
      <form id="form-cal"
            action="{{ route('admin.calendars.store') }}"
            method="POST"
            class="form-inline">
        @csrf
        <input type="hidden" name="id" id="item-id">

        <div class="form-group mr-2">
          <label class="mr-1">Tanggal:</label>
          <input type="date" id="date" name="date" class="form-control" required>
        </div>

        <div class="form-group mr-2">
          <label class="mr-1">Keterangan:</label>
          <input type="text" id="description" name="description" class="form-control" required>
        </div>

        <div class="form-group mr-2">
          <label class="mr-1">Tipe:</label>
          <select id="type" name="type" class="form-control" required>
            <option value="Masuk">Masuk</option>
            <option value="Warning">Warning</option>
            <option value="Libur">Libur</option>
          </select>
        </div>

        <div class="form-group mr-2">
          <label class="mr-1">Warna:</label>
          <input type="color" id="color" name="color" class="form-control" value="#3788d8">
        </div>

        <button type="submit" id="btn-save" class="btn btn-success">Simpan</button>
        <button type="button" id="btn-cancel" class="btn btn-secondary ml-2" style="display:none;">Batal</button>
      </form>
    </div>
  </div>

  <div class="card shadow">
    <div class="card-header">Daftar Jadwal</div>
    <div class="card-body p-0">
      <table class="table mb-0">
        <thead>
          <tr>
            <th>No</th>
            <th>Tanggal</th>
            <th>Keterangan</th>
            <th>Tipe</th>
            <th>Warna</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($items as $i)
            <tr>
              <td>{{ $loop->iteration }}</td>
              <td>{{ $i->date->format('d M Y') }}</td>
              <td>{{ $i->description }}</td>
              <td>{{ $i->type }}</td>
              <td>
                <span class="badge" style="background: {{ $i->color }}">{{ $i->type }}</span>
              </td>
              <td>
                <button class="btn btn-sm btn-primary btn-edit"
                        data-id="{{ $i->id }}"
                        data-date="{{ $i->date->toDateString() }}"
                        data-description="{{ $i->description }}"
                        data-type="{{ $i->type }}"
                        data-color="{{ $i->color }}">
                  <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-danger btn-delete" data-id="{{ $i->id }}">
                  <i class="fas fa-trash"></i>
                </button>
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="text-center">Belum ada jadwal.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const form      = document.getElementById('form-cal'),
      inputId   = document.getElementById('item-id'),
      inputDate = document.getElementById('date'),
      inputDesc = document.getElementById('description'),
      inputType = document.getElementById('type'),
      inputColor= document.getElementById('color'),
      btnCancel = document.getElementById('btn-cancel'),
      btnSave   = document.getElementById('btn-save');

// Cancel edit
btnCancel.onclick = () => {
  inputId.value = '';
  form.action   = '{{ route("admin.calendars.store") }}';
  btnCancel.style.display = 'none';
  btnSave.textContent    = 'Simpan';
};

// Edit
document.querySelectorAll('.btn-edit').forEach(btn => {
  btn.onclick = () => {
    form.action   = `/admin/calendars/${btn.dataset.id}`;
    inputId.value = btn.dataset.id;
    inputDate.value = btn.dataset.date;
    inputDesc.value = btn.dataset.description;
    inputType.value = btn.dataset.type;
    inputColor.value= btn.dataset.color;
    btnCancel.style.display = 'inline-block';
    btnSave.textContent    = 'Update';
  };
});

// Delete
document.querySelectorAll('.btn-delete').forEach(btn => {
  btn.onclick = () => {
    Swal.fire({
      title: 'Yakin menghapus jadwal?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Hapus',
      cancelButtonText: 'Batal'
    }).then(res => {
      if (!res.isConfirmed) return;
      fetch(`/admin/calendars/${btn.dataset.id}`, {
        method: 'DELETE',
        headers: {'X-CSRF-TOKEN':'{{ csrf_token() }}'}
      }).then(() => location.reload());
    });
  };
});
</script>
@endpush
