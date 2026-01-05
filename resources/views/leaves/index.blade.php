{{-- resources/views/leaves/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Manajemen Pengajuan Cuti')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Manajemen Pengajuan Cuti</h1>
            <p class="text-muted mb-0">Kelola semua pengajuan cuti karyawan</p>
        </div>
        <!-- <a href="{{ route('leaves.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Ajukan Cuti Atas Nama Karyawan
        </a> -->
    </div>

    <!-- Filter -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('leaves.index') }}">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="all" {{ $status == 'all' ? 'selected' : '' }}>Semua Status</option>
                            <option value="pending" {{ $status == 'pending' ? 'selected' : '' }}>Menunggu</option>
                            <option value="approved" {{ $status == 'approved' ? 'selected' : '' }}>Disetujui</option>
                            <option value="rejected" {{ $status == 'rejected' ? 'selected' : '' }}>Ditolak</option>
                            <option value="canceled" {{ $status == 'canceled' ? 'selected' : '' }}>Dibatalkan</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Karyawan</label>
                        <select name="employee_id" class="form-select">
                            <option value="">Semua Karyawan</option>
                            @foreach($employees as $id => $emp)
                                <option value="{{ $id }}" {{ $employeeId == $id ? 'selected' : '' }}>
                                    {{ $emp['name'] }} ({{ $emp['department'] ?? 'Tidak Ada Dept' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-outline-primary me-2">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <a href="{{ route('leaves.index') }}" class="btn btn-outline-secondary">
                            Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Cuti -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0">Daftar Pengajuan Cuti</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>No</th>
                            <th>Karyawan</th>
                            <th>Departemen</th>
                            <th>Jenis Cuti</th>
                            <th>Tanggal Mulai</th>
                            <th>Tanggal Selesai</th>
                            <th>Status</th>
                            <th>Diajukan Pada</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($leaves as $leave)
                            <tr>
                                <td>{{ $loop->iteration + $leaves->firstItem() - 1 }}</td>
                                <td>{{ $leave->employee_name }}</td>
                                <td>{{ $leave->employee_department }}</td>
                                <td>
                                    @php
                                        $types = [
                                            'annual' => 'Cuti Tahunan',
                                            'sick' => 'Cuti Sakit',
                                            'personal' => 'Cuti Pribadi',
                                            'maternity' => 'Cuti Melahirkan',
                                            'paternity' => 'Cuti Ayah',
                                            'unpaid' => 'Tanpa Gaji'
                                        ];
                                    @endphp
                                    {{ $types[$leave->leave_type] ?? ucfirst($leave->leave_type) }}
                                </td>
                                <td>{{ \Carbon\Carbon::parse($leave->start_date)->format('d/m/Y') }}</td>
                                <td>{{ \Carbon\Carbon::parse($leave->end_date)->format('d/m/Y') }}</td>
                                <td>
                                    @switch($leave->status)
                                        @case('pending')
                                            <span class="badge bg-warning text-dark">Menunggu</span>
                                            @break
                                        @case('approved')
                                            <span class="badge bg-success">Disetujui</span>
                                            @break
                                        @case('rejected')
                                            <span class="badge bg-danger">Ditolak</span>
                                            @break
                                        @case('canceled')
                                            <span class="badge bg-secondary">Dibatalkan</span>
                                            @break
                                        @default
                                            <span class="badge bg-info">{{ ucfirst($leave->status) }}</span>
                                    @endswitch
                                </td>
                                <td>{{ $leave->created_at->format('d/m/Y H:i') }}</td>
                                <td class="text-center">
    <!-- Tombol Detail -->
    <a href="{{ route('leaves.show', $leave->id) }}" class="btn btn-sm btn-info" title="Lihat Detail">
        <i class="fas fa-eye"></i>
    </a>

    <!-- Tombol Approve & Reject hanya jika status pending -->
    @if($leave->status === 'pending')
        <!-- Approve -->
        <form action="{{ route('leaves.approve', $leave->id) }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-sm btn-success ms-1" title="Setujui Cuti"
                onclick="return confirm('Yakin ingin menyetujui pengajuan cuti ini?')">
                <i class="fas fa-check"></i>
            </button>
        </form>

        <!-- Reject dengan Modal -->
        <button type="button" class="btn btn-sm btn-danger ms-1" title="Tolak Cuti"
            data-bs-toggle="modal" data-bs-target="#rejectModal-{{ $leave->id }}">
            <i class="fas fa-times"></i>
        </button>
    @else
        <span class="text-muted small">Sudah diproses</span>
    @endif
</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="fas fa-inbox fa-3x mb-3"></i>
                                    <p>Tidak ada data pengajuan cuti</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white">
            {{ $leaves->appends(request()->query())->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
@endpush
