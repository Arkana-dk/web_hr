@extends('layouts.master')

@section('title','Tambah Potongan Karyawan')

@section('content')
<div class="container-fluid">
  <div class="card shadow mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h6 class="m-0 font-weight-bold text-primary">Tambah Potongan Karyawan</h6>
      <a href="{{ route('admin.employee-deductions.index') }}" class="btn btn-sm btn-secondary">← Kembali</a>
    </div>
    <div class="card-body">
      <form action="{{ route('admin.employee-deductions.store') }}" method="POST">
        @csrf

        {{-- Karyawan sudah terpilih --}}
        <div class="mb-3">
          <label class="form-label">Karyawan</label>
          <input type="text" class="form-control" value="{{ $selectedEmployee->name }}" readonly>
          <input type="hidden" name="employee_id" value="{{ $selectedEmployee->id }}">
        </div>

        {{-- Tabel dynamic potongan --}}
        <table class="table mb-3" id="tbl-deductions">
          <thead>
            <tr>
              <th>Potongan</th>
              <th>Jumlah (Rp)</th>
              <th style="width:5%"></th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>
                <select name="potongan_ids[]" class="form-select deduction-select" required>
                  <option value="">-- Pilih Potongan --</option>
                  @foreach($potongans as $p)
                  <option value="{{ $p->id }}" data-amount="{{ $p->amount }}">
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
          </tbody>
        </table>

        {{-- Total --}}
        <div class="mb-3">
          <label class="form-label">Total Semua</label>
          <p id="grandTotalDeduction" class="fw-bold">Rp 0</p>
        </div>

        <button class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  const tbody = document.querySelector('#tbl-deductions tbody');
  const totalEl = document.getElementById('grandTotalDeduction');

  function recalc(){
    let sum = 0;
    tbody.querySelectorAll('.amount-input').forEach(inp=>{
      sum += parseFloat(inp.dataset.raw||0);
    });
    totalEl.textContent = 'Rp ' + sum.toLocaleString('id-ID');
  }

  tbody.addEventListener('change', e=>{
    if(e.target.matches('.deduction-select')){
      const opt = e.target.selectedOptions[0],
            row = e.target.closest('tr'),
            inp = row.querySelector('.amount-input'),
            amt = opt? Number(opt.dataset.amount):0;
      inp.value = 'Rp ' + amt.toLocaleString('id-ID');
      inp.dataset.raw = amt;
      recalc();
    }
  });

  tbody.addEventListener('click', e=>{
    if(e.target.closest('.btn-add-row')){
      const newRow = tbody.rows[0].cloneNode(true);
      newRow.querySelector('.deduction-select').value = '';
      const inp = newRow.querySelector('.amount-input');
      inp.value = ''; inp.dataset.raw=0;
      tbody.appendChild(newRow);
    }
    if(e.target.closest('.btn-remove-row')){
      e.target.closest('tr').remove(); recalc();
    }
  });

  tbody.addEventListener('DOMNodeInserted', e=>{
    if(e.target.tagName==='TR'){
      const btn = e.target.querySelector('.btn-add-row');
      btn.classList.replace('btn-success','btn-danger');
      btn.classList.replace('btn-add-row','btn-remove-row');
      btn.innerHTML = '<i class="fas fa-minus"></i>';
    }
  });
});
</script>
@endpush
