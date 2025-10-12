{{-- resources/views/admin/pages/employee-allowances-edit.blade.php --}}
@extends('layouts.master')

@section('title','Edit Tunjangan Karyawan')

@section('content')
<div class="container-fluid">
  <div class="card shadow mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
      {{-- Gunakan selectedEmployee seperti di controller --}}
      <h6 class="m-0 font-weight-bold text-primary">
        Edit Tunjangan {{ $selectedEmployee->name }}
      </h6>
      <a href="{{ route('admin.employee-allowances.index') }}"
         class="btn btn-sm btn-secondary">← Kembali</a>
    </div>
    <div class="card-body">
      <form action="{{ route('admin.employee-allowances.update', ['employee' => $selectedEmployee->id]) }}"
            method="POST">
        @csrf
        @method('PUT')

        {{-- Karyawan read-only --}}
        <div class="mb-3">
          <label class="form-label">Karyawan</label>
          <input type="text" class="form-control" value="{{ $selectedEmployee->name }}" readonly>
          <input type="hidden" name="employee_id" value="{{ $selectedEmployee->id }}">
        </div>

        {{-- Dynamic table multi-tunjangan --}}
        <table class="table mb-3" id="tbl-edit">
          <thead>
            <tr>
              <th>Tunjangan</th>
              <th>Jumlah (Rp)</th>
              <th style="width:5%"></th>
            </tr>
          </thead>
          <tbody>
            @forelse($selectedEmployee->allowances as $idx => $allow)
            <tr>
              <td>
                <select name="tunjangan_ids[]" class="form-select tunjangan-select" required>
                  <option value="">-- Pilih Tunjangan --</option>
                  @foreach($tunjangans as $t)
                    <option value="{{ $t->id }}"
                            data-amount="{{ $t->amount }}"
                            {{ $t->id == $allow->tunjangan_id ? 'selected' : '' }}>
                      {{ $t->name }} (Rp {{ number_format($t->amount,0,',','.') }})
                    </option>
                  @endforeach
                </select>
              </td>
              <td>
                <input type="text"
                       class="form-control amount-input"
                       data-raw="{{ $allow->amount }}"
                       value="Rp {{ number_format($allow->amount,0,',','.') }}"
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
            <tr>
              <td>
                <select name="tunjangan_ids[]" class="form-select tunjangan-select" required>
                  <option value="">-- Pilih Tunjangan --</option>
                  @foreach($tunjangans as $t)
                    <option value="{{ $t->id }}" data-amount="{{ $t->amount }}">
                      {{ $t->name }} (Rp {{ number_format($t->amount,0,',','.') }})
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

        {{-- Total Semua --}}
        <div class="mb-3">
          <label class="form-label">Total Semua</label>
          <p id="grandTotalEdit" class="fw-bold">Rp 0</p>
        </div>

        <button type="submit" class="btn btn-primary">Update</button>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  const tbody = document.querySelector('#tbl-edit tbody');
  const totalEl = document.getElementById('grandTotalEdit');

  function recalc() {
    let sum = 0;
    tbody.querySelectorAll('.amount-input').forEach(inp => {
      sum += parseFloat(inp.dataset.raw) || 0;
    });
    totalEl.textContent = 'Rp ' + sum.toLocaleString('id-ID');
  }

  tbody.addEventListener('change', e => {
    if (e.target.matches('.tunjangan-select')) {
      const opt = e.target.selectedOptions[0];
      const val = opt ? Number(opt.dataset.amount) : 0;
      const row = e.target.closest('tr');
      const inp = row.querySelector('.amount-input');
      inp.value = 'Rp ' + val.toLocaleString('id-ID');
      inp.dataset.raw = val;
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
      e.target.closest('tr').remove();
      recalc();
    }
  });

  // initial compute
  recalc();
});
</script>
@endpush
