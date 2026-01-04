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
        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Sisa Cuti Tahunan</h6>
                            <h4 class="mb-0">{{ $remainingLeave ?? '12' }} Hari</h4>
                        </div>
                        <div class="icon-shape bg-primary text-white rounded p-3">
                            <i class="fas fa-calendar-days fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Pengajuan Tertunda</h6>
                            <h4 class="mb-0">{{ $pendingLeaves ?? '0' }}</h4>
                        </div>
                        <div class="icon-shape bg-warning text-white rounded p-3">
                            <i class="fas fa-hourglass-half fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm border-0">
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

        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm border-0">
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

    <!-- Tabel Pengajuan Cuti -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
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
                            <tr>
                                <td class="ps-4">{{ $leave->created_at->format('d M Y H:i') }}</td>
                                <td>{{ $leave->leave_type->name ?? $leave->leave_type ?? 'Cuti Tahunan' }}</td>
                                <td>
                                    {{ $leave->start_date->format('d M Y') }}
                                    <br>
                                    <small class="text-muted">s/d</small>
                                    <br>
                                    {{ $leave->end_date->format('d M Y') }}
                                </td>
                                <td>{{ $leave->days }} Hari</td>
                                <td>
                                    @if($leave->status === 'pending')
                                        <span class="badge bg-warning">Menunggu</span>
                                    @elseif($leave->status === 'approved')
                                        <span class="badge bg-success">Disetujui</span>
                                    @elseif($leave->status === 'rejected')
                                        <span class="badge bg-danger">Ditolak</span>
                                    @elseif($leave->status === 'canceled')
                                        <span class="badge bg-secondary">Dibatalkan</span>
                                    @else
                                        <span class="badge bg-info">Proses</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('leaves.show', $leave->id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if($leave->status === 'pending')
                                        <form action="{{ route('leaves.destroy', $leave->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Yakin ingin membatalkan pengajuan ini?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fas fa-calendar-xmark fa-3x mb-3"></i>
                                        <p>Belum ada pengajuan cuti</p>
                                        <a href="{{ route('leaves.create') }}" class="btn btn-outline-primary mt-2">
                                            Ajukan cuti pertama Anda sekarang
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white">
            {{ $leaves->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
@endpush
