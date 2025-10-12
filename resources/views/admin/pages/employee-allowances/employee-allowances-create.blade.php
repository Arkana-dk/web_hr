@extends('layouts.master')

@section('title','Tambah Tunjangan Karyawan')

@section('content')
<div class="container-fluid">
  <div class="card shadow mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h6 class="m-0 font-weight-bold text-primary">Tambah Tunjangan Karyawan</h6>
      <a href="{{ route('admin.employee-allowances.index') }}" class="btn btn-sm btn-secondary">← Kembali</a>
    </div>
    <div class="card-body">
      <form action="{{ route('admin.employee-allowances.store') }}" method="POST">
        @csrf

        {{-- Karyawan sudah dipilih --}}
        <div class="mb-3">
          <label class="form-label">Karyawan</label>
          <input type="text"
                 class="form-control"
                 value="{{ $selectedEmployee->name }}"
                 readonly>
          <input type="hidden"
                 name="employee_id"
                 value="{{ $selectedEmployee->id }}">
        </div>

        {{-- Tabel dynamic input tunjangan --}}
        <table class="table mb-3" id="tbl-allowances">
          <thead>
            <tr>
              <th>Tunjangan</th>
              <th>Jumlah (Rp)</th>
              <th style="width:5%"></th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>
                <select name="tunjangan_ids[]"
                        class="form-select tunjangan-select"
                        required>
                  <option value="">-- Pilih Tunjangan --</option>
                  @foreach($tunjangans as $t)
                    <option value="{{ $t->id }}"
                            data-amount="{{ $t->amount }}">
                      {{ $t->name }}
                      (Rp {{ number_format($t->amount,0,',','.') }})
                    </option>
                  @endforeach
                </select>
              </td>
              <td>
                <input type="text"
                       class="form-control amount-input"
                       readonly>
              </td>
              <td class="text-center">
                <button type="button"
                        class="btn btn-sm btn-success btn-add-row">
                  <i class="fas fa-plus"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        {{-- Total --}}
        <div class="mb-3">
          <label class="form-label">Total Semua</label>
          <p id="grandTotal" class="fw-bold">Rp 0</p>
        </div>

        <button type="submit" class="btn btn-primary">Simpan</button>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  const tbody = document.querySelector('#tbl-allowances tbody');
  const grandEl = document.getElementById('grandTotal');

  function recalc() {
    let sum = 0;
    tbody.querySelectorAll('.amount-input').forEach(inp => {
      const val = parseFloat(inp.dataset.raw) || 0;
      sum += val;
    });
    grandEl.textContent = 'Rp ' + sum.toLocaleString('id-ID');
  }

  tbody.addEventListener('change', e => {
    if (e.target.matches('.tunjangan-select')) {
      const opt = e.target.selectedOptions[0];
      const row = e.target.closest('tr');
      const inp = row.querySelector('.amount-input');
      const amount = opt ? Number(opt.dataset.amount) : 0;
      inp.value = 'Rp ' + amount.toLocaleString('id-ID');
      inp.dataset.raw = amount;
      recalc();
    }
  });

  tbody.addEventListener('click', e => {
    if (e.target.closest('.btn-add-row')) {
      const newRow = tbody.rows[0].cloneNode(true);
      newRow.querySelector('.tunjangan-select').value = '';
      const inp = newRow.querySelector('.amount-input');
      inp.value = '';
      inp.dataset.raw = 0;
      tbody.appendChild(newRow);
    }
    if (e.target.closest('.btn-remove-row')) {
      const row = e.target.closest('tr');
      if (tbody.rows.length > 1) {
        row.remove();
        recalc();
      }
    }
  });

  tbody.addEventListener('DOMNodeInserted', e => {
    if (e.target.tagName==='TR') {
      const btn = e.target.querySelector('.btn-add-row');
      btn.classList.remove('btn-success','btn-add-row');
      btn.classList.add('btn-danger','btn-remove-row');
      btn.innerHTML = '<i class="fas fa-minus"></i>';
    }
  });
});
</script>
@endpush
