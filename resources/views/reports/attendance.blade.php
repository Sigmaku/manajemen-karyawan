@extends('layouts.app')

@section('title', 'Attendance Report')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4">Laporan Kehadiran Bulanan</h1>

    <div class="card">
        <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <h5 class="mb-0">Periode: {{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') }}</h5>
            <div class="d-flex flex-column flex-sm-row gap-2 align-items-center">
                <span class="badge bg-info fs-6">Total Karyawan: {{ count($reportData) }}</span>
                <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" class="btn btn-success btn-sm">
                    <i class="fas fa-file-pdf me-2"></i>Export PDF
                </a>
            </div>
        </div>

        <div class="card-body">
            <!-- Filter Form - Responsive Grid -->
            <form method="GET" class="row g-3 mb-4">
                <div class="col-12 col-md-4 col-lg-3">
                    <label class="form-label">Bulan</label>
                    <input type="month" name="month" value="{{ $month }}" class="form-control">
                </div>
                <div class="col-12 col-md-4 col-lg-3">
                    <label class="form-label">Departemen</label>
                    <select name="department" class="form-select">
                        <option value="">Semua Departemen</option>
                        <option value="IT" {{ $department == 'IT' ? 'selected' : '' }}>IT</option>
                        <option value="HR" {{ $department == 'HR' ? 'selected' : '' }}>HR</option>
                        <!-- tambah sesuai data kalian -->
                    </select>
                </div>
                <div class="col-12 col-md-4 col-lg-3">
                    <label class="form-label">Karyawan</label>
                    <select name="employee_id" class="form-select">
                        <option value="">Semua Karyawan</option>
                        @foreach($reportData as $empId => $data)
                            <option value="{{ $empId }}" {{ request('employee_id') == $empId ? 'selected' : '' }}>
                                {{ $data['employee']['name'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-12 col-lg-3 d-flex gap-2">
                    <label class="form-label invisible">Aksi</label>
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                    <a href="{{ route('reports.attendance') }}" class="btn btn-outline-secondary w-100">Reset</a>
                </div>
            </form>

            <!-- Table Responsive -->
            <div class="table-responsive">
                <table class="table table-hover align-middle table-sm">
                    <thead class="table-light">
                        <tr>
                            <th class="text-nowrap">Nama</th>
                            <th class="text-nowrap d-none d-md-table-cell">Dept</th>
                            <th class="text-center">Hadir</th>
                            <th class="text-center">Telat</th>
                            <th class="text-center d-none d-lg-table-cell">Cuti</th>
                            <th class="text-center d-none d-lg-table-cell">Sakit</th>
                            <th class="text-center d-none d-lg-table-cell">Izin</th>
                            <th class="text-center">Absent</th>
                            <th class="text-center d-none d-md-table-cell">Rate</th>
                            <th class="text-center">Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reportData as $empId => $data)
                        <tr>
                            <td>
                                <div class="d-flex flex-column">
                                    <strong>{{ $data['employee']['name'] }}</strong>
                                    <small class="text-muted d-md-none">{{ $data['employee']['department'] ?? '-' }}</small>
                                </div>
                            </td>
                            <td class="d-none d-md-table-cell">{{ $data['employee']['department'] ?? '-' }}</td>
                            <td class="text-center"><span class="badge bg-success">{{ $data['stats']['present'] ?? 0 }}</span></td>
                            <td class="text-center"><span class="badge bg-info">{{ $data['stats']['late'] ?? 0 }}</span></td>
                            <td class="text-center d-none d-lg-table-cell"><span class="badge bg-warning">{{ $data['stats']['leave'] ?? 0 }}</span></td>
                            <td class="text-center d-none d-lg-table-cell"><span class="badge bg-warning">{{ $data['stats']['sick'] ?? 0 }}</span></td>
                            <td class="text-center d-none d-lg-table-cell"><span class="badge bg-warning">{{ $data['stats']['permission'] ?? 0 }}</span></td>
                            <td class="text-center"><span class="badge bg-danger">{{ $data['stats']['absent'] ?? 0 }}</span></td>
                            <td class="text-center d-none d-md-table-cell"><strong>{{ $data['attendance_rate'] }}%</strong></td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#details-{{ $empId }}">
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                            </td>
                        </tr>
                        <tr class="collapse bg-light" id="details-{{ $empId }}">
                            <td colspan="10" class="p-3">
                                <div class="small">
                                    <strong>Rate: {{ $data['attendance_rate'] }}%</strong>
                                    <span class="d-md-none ms-3"><strong>Dept: {{ $data['employee']['department'] }}</strong></span>
                                </div>
                                <div class="table-responsive mt-3">
                                    <table class="table table-sm table-bordered mb-0">
                                        <thead class="table-secondary">
                                            <tr>
                                                <th>Tanggal</th>
                                                <th>Status</th>
                                                <th>Alasan</th>
                                                <th class="d-none d-sm-table-cell">In</th>
                                                <th class="d-none d-sm-table-cell">Out</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($data['details'] as $detail)
                                            <tr>
                                                <td class="text-nowrap">{{ \Carbon\Carbon::parse($detail['date'])->format('d M') }}</td>
                                                <td>
                                                    @php
                                                        $badgeClass = match($detail['status']) {
                                                            'Present', 'Late' => 'bg-success',
                                                            'Leave', 'Sick', 'Permission' => 'bg-warning',
                                                            default => 'bg-danger'
                                                        };
                                                    @endphp
                                                    <span class="badge {{ $badgeClass }} fs-6">{{ $detail['status'] }}</span>
                                                </td>
                                                <td class="small">{{ $detail['reason'] }}</td>
                                                <td class="small d-none d-sm-table-cell">{{ $detail['clock_in'] }}</td>
                                                <td class="small d-none d-sm-table-cell">{{ $detail['clock_out'] }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <i class="fas fa-calendar-times fa-3x mb-3"></i>
                                <p>Tidak ada data kehadiran untuk periode ini</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection