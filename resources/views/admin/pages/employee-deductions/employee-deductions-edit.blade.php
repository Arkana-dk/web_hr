{{-- resources/views/admin/pages/employee-deductions-edit.blade.php --}}
@extends('layouts.master')

@section('title','Edit Potongan Karyawan')

@section('content')
<div class="container-fluid">
  <div class="card shadow mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
      {{-- Pastikan variabelnya $selectedEmployee --}}
      <h6 class="m-0 font-weight-bold text-primary">
        Edit Potongan {{ $selectedEmployee->name }}
      </h6>
      <a href="{{ route('admin.employee-deductions.index') }}"
         class="btn btn-sm btn-secondary">← Kembali</a>
    </div>
    <div class="card-body">
      <form action="{{ route('admin.employee-deductions.update', $selectedEmployee->id) }}"
            method="POST">
        @csrf
        @method('PUT')

        {{-- Hidden employee_id --}}
        <input type="hidden" name="employee_id" value="{{ $selectedEmployee->id }}">

        {{-- Tabel dinamis potongan --}}
        <table class="table mb-3" id="tbl-edit">
          <thead>
            <tr>
              <th>Potongan</th>
              <th>Jumlah (Rp)</th>
              <th style="width:5%"></th>
            </tr>
          </thead>
          <tbody>
            @forelse($selectedEmployee->deductions as $idx => $ded)
            <tr>
              <td>
                <select name="potongan_ids[]" class="form-select deduction-select" required>
                  <option value="">-- Pilih Potongan --</option>
                  @foreach($potongans as $p)
                    <option value="{{ $p->id }}"
                            data-amount="{{ $p->amount }}"
                            {{ $p->id === $ded->potongan_id ? 'selected' : '' }}>
                      {{ $p->name }} (Rp {{ number_format($p->amount,0,',','.') }})
                    </option>
                  @endforeach
                </select>
              </td>
              <td>
                <input type="text"
                       class="form-control amount-input"
                       data-raw="{{ $ded->amount }}"
                       value="Rp {{ number_format($ded->amount,0,',','.') }}"
                       readonly>
              </td>
              <td class="text-center">
                <button type="button"
                        class="btn btn-sm {{ $idx ? 'btn-danger btn-remove-row' : 'btn-success btn-add-row' }}">
                  <i class="fas fa-{{ $idx ? 'minus' : 'plus' }}"></i>
                </button>
              </td>
            </tr>
            @empty
            {{-- Jika belum ada potongan, berikan satu baris kosong --}}
            <tr>
              <td>
                <select name="potongan_ids[]" class="form-select deduction-select" required>
                  <option value="">-- Pilih Potongan --</option>
                  @foreach($potongans as $p)
                    <option value="{{ $p->id }}"
                            data-amount="{{ $p->amount }}">
                      {{ $p->name }} (Rp {{ number_format($p->amount,0,',','.') }})
                    </option>
                  @endforeach
                </select>
              </td>
              <td><input type="text" class="form-control amount-input" readonly></td>
              <td class="text-center">
                <button type="button" class="btn btn-sm btn-success btn-add-row">
                  <i class="fas fa-plus"></i>
                </button>
              </td>
            </tr>
            @endforelse
          </tbody>
        </table>

        {{-- Total --}}
        <div class="mb-3">
          <label class="form-label">Total Semua</label>
          <p id="grandTotalEdit" class="fw-bold">Rp 0</p>
        </div>

        <button type="submit" class="btn btn-primary">
          <i class="fas fa-save"></i> Simpan Perubahan
        </button>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  const tbody   = document.querySelector('#tbl-edit tbody');
  const totalEl = document.getElementById('grandTotalEdit');

  function recalc(){
    let sum = 0;
    tbody.querySelectorAll('.amount-input').forEach(inp=>{
      sum += parseFloat(inp.dataset.raw||0);
    });
    totalEl.textContent = 'Rp ' + sum.toLocaleString('id-ID');
  }

  // Saat select berubah, update nilai dan total
  tbody.addEventListener('change', e=>{
    if (e.target.matches('.deduction-select')) {
      const opt = e.target.selectedOptions[0];
      const amt = opt ? Number(opt.dataset.amount) : 0;
      const row = e.target.closest('tr');
      const inp = row.querySelector('.amount-input');
      inp.value = 'Rp ' + amt.toLocaleString('id-ID');
      inp.dataset.raw = amt;
      recalc();
    }
  });

  // Tambah / hapus baris
  tbody.addEventListener('click', e=>{
    if (e.target.closest('.btn-add-row')) {
      const newRow = tbody.rows[0].cloneNode(true);
      newRow.querySelectorAll('select').forEach(s=> s.value = '');
      newRow.querySelectorAll('.amount-input').forEach(i=>{
        i.value = '';
        i.dataset.raw = 0;
      });
      tbody.appendChild(newRow);
    }
    if (e.target.closest('.btn-remove-row')) {
      e.target.closest('tr').remove();
      recalc();
    }
  });

  // Ubah tombol plus jadi minus pada baris baru
  tbody.addEventListener('DOMNodeInserted', e=>{
    if (e.target.tagName === 'TR') {
      const btn = e.target.querySelector('.btn-add-row');
      btn.classList.replace('btn-success','btn-danger');
      btn.classList.replace('btn-add-row','btn-remove-row');
      btn.innerHTML = '<i class="fas fa-minus"></i>';
    }
  });

  // Hitung total awal
  recalc();
});
</script>
@endpush
