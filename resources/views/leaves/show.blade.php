{{-- resources/views/leaves/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Detail Pengajuan Cuti')

@section('content')
<div class="container-fluid py-4">
    <!-- Toast Notification -->
    @if(session('success'))
        <div class="position-fixed top-0 end-0 p-3" style="z-index: 9999;">
            <div class="toast align-items-center text-bg-success border-0 show" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fas fa-check-circle me-2"></i>
                        {{ session('success') }}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="position-fixed top-0 end-0 p-3" style="z-index: 9999;">
            <div class="toast align-items-center text-bg-danger border-0 show" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        {{ session('error') }}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        </div>
    @endif

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Detail Pengajuan Cuti</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="{{ $role === 'employee' ? route('leaves.my') : route('leaves.index') }}">
                            {{ $role === 'employee' ? 'Pengajuan Cuti Saya' : 'Manajemen Cuti' }}
                        </a>
                    </li>
                    <li class="breadcrumb-item active">Detail</li>
                </ol>
            </nav>
        </div>
        <a href="{{ $role === 'employee' ? route('leaves.my') : route('leaves.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Kembali
        </a>
    </div>

    <div class="row">
        <!-- Informasi Pengajuan -->
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">Informasi Pengajuan</h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <strong class="text-muted">Karyawan</strong>
                            <p class="mb-0 fs-5">{{ $employee['name'] }}</p>
                        </div>
                        <div class="col-md-6">
                            <strong class="text-muted">Departemen</strong>
                            <p class="mb-0 fs-5">{{ $employee['department'] ?? '-' }}</p>
                        </div>
                        <div class="col-md-6">
                            <strong class="text-muted">Jenis Cuti</strong>
                            <p class="mb-0 fs-5">{{ $leaveTypeName }}</p>
                        </div>
                        <div class="col-md-6">
                            <strong class="text-muted">Jumlah Hari</strong>
                            <p class="mb-0 fs-5">{{ $days }} hari</p>
                        </div>
                        <div class="col-md-6">
                            <strong class="text-muted">Tanggal Mulai</strong>
                            <p class="mb-0 fs-5">{{ $startDate->format('d F Y') }}</p>
                        </div>
                        <div class="col-md-6">
                            <strong class="text-muted">Tanggal Selesai</strong>
                            <p class="mb-0 fs-5">{{ $endDate->format('d F Y') }}</p>
                        </div>
                        <div class="col-12">
                            <strong class="text-muted">Alasan Cuti</strong>
                            <p class="mb-0 mt-2 p-3 bg-light rounded">{{ $leave->reason }}</p>
                        </div>
                        <div class="col-12">
                            <strong class="text-muted">Kontak Selama Cuti</strong>
                            <p class="mb-0 mt-2">{{ $leave->contact_during_leave }}</p>
                        </div>
                        <div class="col-md-6">
                            <strong class="text-muted">Status</strong><br>
                            @switch($leave->status)
                                @case('pending')
                                    <span class="badge bg-warning text-dark fs-5 px-3 py-2">Menunggu Persetujuan</span>
                                    @break
                                @case('approved')
                                    <span class="badge bg-success fs-5 px-3 py-2">Disetujui</span>
                                    @break
                                @case('rejected')
                                    <span class="badge bg-danger fs-5 px-3 py-2">Ditolak</span>
                                    @break
                                @case('canceled')
                                    <span class="badge bg-secondary fs-5 px-3 py-2">Dibatalkan</span>
                                    @break
                                @default
                                    <span class="badge bg-info fs-5 px-3 py-2">{{ ucfirst($leave->status) }}</span>
                            @endswitch
                        </div>
                        <div class="col-md-6">
                            <strong class="text-muted">Diajukan Pada</strong>
                            <p class="mb-0 fs-5">{{ $leave->created_at->format('d F Y ') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Aksi Persetujuan -->
        <div class="col-lg-4">
            @php
                $user = session('user');
                $role = $user['role'] ?? 'employee';
                $isApprover = in_array($role, ['admin', 'manager']);
            @endphp

            @if($leave->status === 'pending' && $isApprover)
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="fas fa-gavel me-2"></i>Aksi Persetujuan</h6>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('leaves.approve', $leave->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-success btn-lg w-100 mb-3 shadow-sm"
                                onclick="return confirm('Yakin ingin MENYETUJUI pengajuan cuti ini?')">
                                <i class="fas fa-check-circle fa-lg me-2"></i>
                                Setujui Cuti
                            </button>
                        </form>

                        <button type="button" class="btn btn-danger btn-lg w-100 shadow-sm"
                            data-bs-toggle="modal" data-bs-target="#rejectModal">
                            <i class="fas fa-times-circle fa-lg me-2"></i>
                            Tolak Cuti
                        </button>
                    </div>
                </div>
            @elseif($leave->status !== 'pending')
                <div class="card shadow-sm border-0">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-check-double text-success fa-4x mb-4"></i>
                        <h5 class="text-success">Pengajuan Sudah Diproses</h5>
                        @if($leave->status === 'approved')
                            <p class="text-success"><strong>Disetujui oleh:</strong><br>{{ $leave->approved_by ?? '-' }}</p>
                        @elseif($leave->status === 'rejected')
                            <p class="text-danger"><strong>Ditolak karena:</strong><br>{{ $leave->rejection_reason ?? '-' }}</p>
                        @endif
                    </div>
                </div>
            @else
                <!-- Untuk karyawan biasa yang melihat cuti sendiri -->
                <div class="card shadow-sm border-0">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-hourglass-half text-warning fa-4x mb-4"></i>
                        <h5 class="text-warning">Menunggu Persetujuan</h5>
                        <p class="text-muted">Pengajuan cuti Anda sedang ditinjau oleh atasan.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal Tolak Cuti -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('leaves.reject', $leave->id) }}" method="POST">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="rejectModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i>Tolak Pengajuan Cuti
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <strong>Perhatian:</strong> Penolakan tidak dapat dibatalkan. Pastikan alasan jelas.
                    </div>
                    <p><strong>Karyawan:</strong> {{ $employee['name'] }}</p>
                    <p><strong>Periode Cuti:</strong> {{ $startDate->format('d F Y') }} s/d {{ $endDate->format('d F Y') }}</p>
                    <p><strong>Jenis Cuti:</strong> {{ $leaveTypeName }}</p>

                    <div class="mt-4">
                        <label for="rejection_reason" class="form-label fw-bold">
                            Alasan Penolakan <span class="text-danger">*</span>
                        </label>
                        <textarea name="rejection_reason" id="rejection_reason" class="form-control" rows="6" required
                            placeholder="Tuliskan alasan penolakan secara lengkap dan jelas agar karyawan memahami..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-lg" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Batal
                    </button>
                    <button type="submit" class="btn btn-danger btn-lg">
                        <i class="fas fa-ban me-2"></i>Tolak Pengajuan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Auto show & hide toast
        document.querySelectorAll('.toast').forEach(toast => {
            new bootstrap.Toast(toast, { delay: 5000 }).show();
        });
    });
</script>
@endpush

@push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
@endpush
