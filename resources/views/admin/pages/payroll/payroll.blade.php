{{-- resources/views/admin/pages/payroll.blade.php --}}
@extends('layouts.master')

@section('title','Daftar Payroll')

@section('content')
<div class="container-fluid">
  {{-- Alert sukses --}}
  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      {{ session('success') }}
      <button type="button" class="close" data-dismiss="alert" aria-label="Tutup">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>
  @endif

  <div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
      <h6 class="m-0 font-weight-bold text-primary">Daftar Payroll</h6>
      <div class="d-flex align-items-center">
        {{-- Pencarian --}}
        <form action="{{ route('admin.payroll.index') }}" method="GET" class="form-inline mr-3">
          <div class="input-group input-group-sm">
            <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Cari nama…">
            <div class="input-group-append">
              <button class="btn btn-secondary"><i class="fas fa-search"></i></button>
            </div>
          </div>
        </form>

        {{-- Hapus Semua --}}
        <form action="{{ route('admin.payroll.destroyAll') }}" method="POST" class="mr-3">
          @csrf @method('DELETE')
          <button class="btn btn-danger btn-sm">Hapus Semua</button>
        </form>

        {{-- Buat Payroll --}}
        <a href="{{ route('admin.payroll.create') }}" class="btn btn-primary btn-sm">+ Buat Payroll</a>
      </div>
    </div>

    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-striped table-hover table-sm">
          <thead class="thead-light">
            <tr>
              <th>No</th>
              <th>Nama</th>
              <th>Periode</th>
              <th>Gaji</th>
              <th>Tunjangan</th>
              <th>Potongan</th>
              <th>Lembur</th>
              <th>Net</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            @forelse($payrolls as $i => $p)
            <tr>
              <td>{{ $payrolls->firstItem() + $i }}</td>
              <td>{{ $p->employee->name }}</td>
              <td>{{ \Carbon\Carbon::parse($p->start_date)->format('Y-m') }}</td>
              <td>Rp {{ number_format($p->basic_salary,0,',','.') }}</td>
              <td>Rp {{ number_format($p->total_allowances,0,',','.') }}</td>
              <td>Rp {{ number_format($p->total_deductions,0,',','.') }}</td>
              <td>Rp {{ number_format($p->overtime_amount,0,',','.') }}</td>
              <td>Rp {{ number_format($p->net_salary,0,',','.') }}</td>
              <td>
                <span class="badge badge-{{ $p->status==='approved' ? 'success' : 'secondary' }}">
                  {{ ucfirst($p->status) }}
                </span>
              </td>
              <td>
                <a href="{{ route('admin.payroll.show', $p) }}" class="btn btn-info btn-sm" title="Lihat">
                  <i class="fas fa-eye"></i>
                </a>

                @if($p->status !== 'approved')
                  <button type="button"
                          data-id="{{ $p->id }}"
                          class="btn btn-success btn-sm btn-approve"
                          title="Approve">
                    <i class="fas fa-check"></i>
                  </button>

                  <form id="approve-form-{{ $p->id }}"
                        action="{{ route('admin.payroll.approve', $p->id) }}"
                        method="POST"
                        class="d-none">
                    @csrf @method('PATCH')
                  </form>
                @endif
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="10" class="text-center">Data payroll tidak ditemukan.</td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      {{-- Pagination --}}
      <nav class="d-flex justify-content-center mt-3">
        {{ $payrolls->links('pagination::bootstrap-4') }}
      </nav>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  document.querySelectorAll('.btn-approve').forEach(btn => {
    btn.addEventListener('click', function() {
      Swal.fire({
        title: 'Approve payroll ini?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, approve',
        cancelButtonText: 'Batal'
      }).then(result => {
        if (result.isConfirmed) {
          document.getElementById('approve-form-' + btn.dataset.id).submit();
        }
      });
    });
  });
</script>
@endpush
