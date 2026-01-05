@extends('layouts.app')

@section('title', 'Pengajuan Cuti Saya')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Pengajuan Cuti Saya</h1>
            <p class="text-muted mb-0">Riwayat dan status pengajuan cuti Anda</p>
        </div>
        <a href="{{ route('leaves.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Ajukan Cuti Baru
        </a>
    </div>

    <!-- Card Summary -->
    <div class="row mb-4">

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Pengajuan Tertunda</h6>
                            <h4 class="mb-0 text-warning">{{ $pendingLeaves ?? '0' }}</h4>
                        </div>
                        <div class="icon-shape bg-warning text-white rounded p-3">
                            <i class="fas fa-hourglass-half fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Disetujui</h6>
                            <h4 class="mb-0 text-success">{{ $approvedLeaves ?? '0' }}</h4>
                        </div>
                        <div class="icon-shape bg-success text-white rounded p-3">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Ditolak</h6>
                            <h4 class="mb-0 text-danger">{{ $rejectedLeaves ?? '0' }}</h4>
                        </div>
                        <div class="icon-shape bg-danger text-white rounded p-3">
                            <i class="fas fa-times-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Riwayat Cuti -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 border-bottom">
            <h5 class="mb-0">Riwayat Pengajuan Cuti</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Tanggal Pengajuan</th>
                            <th>Jenis Cuti</th>
                            <th>Periode Cuti</th>
                            <th>Jumlah Hari</th>
                            <th>Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($leaves as $leave)
                            @php
                                $createdAt = $leave->created_at;
                                $startDate = \Carbon\Carbon::parse($leave->start_date);
                                $endDate = \Carbon\Carbon::parse($leave->end_date);
                                $days = $startDate->diffInDays($endDate) + 1;

                                $leaveTypeName = [
                                    'annual' => 'Cuti Tahunan',
                                    'sick' => 'Cuti Sakit',
                                    'personal' => 'Cuti Pribadi',
                                    'maternity' => 'Cuti Melahirkan',
                                    'paternity' => 'Cuti Ayah',
                                    'unpaid' => 'Cuti Tanpa Gaji'
                                ][$leave->leave_type] ?? 'Cuti Tahunan';
                            @endphp

                            <tr>
                                <td class="ps-4">{{ $createdAt->format('d M Y H:i') }}</td>
                                <td>{{ $leaveTypeName }}</td>
                                <td>
                                    {{ $startDate->format('d M Y') }}
                                    <br>
                                    <small class="text-muted">s/d</small>
                                    <br>
                                    {{ $endDate->format('d M Y') }}
                                </td>
                                <td>{{ $days }} Hari</td>
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
                                            <span class="badge bg-info">Proses</span>
                                    @endswitch
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('leaves.show', $leave->id) }}" class="btn btn-sm btn-outline-primary" title="Lihat Detail">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fas fa-calendar-xmark fa-3x mb-3 text-muted"></i>
                                        <p class="mb-3">Belum ada pengajuan cuti</p>
                                        <a href="{{ route('leaves.create') }}" class="btn btn-primary">
                                            <i class="fas fa-plus me-2"></i>Ajukan Cuti Pertama Anda
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($leaves->hasPages())
            <div class="card-footer bg-white border-top">
                {{ $leaves->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
@endpush
