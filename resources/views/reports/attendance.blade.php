@extends('layouts.app')

@section('title', 'Attendance Report')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4">Laporan Kehadiran Bulanan</h1>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Periode: {{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') }}</h5>
            <div>
                <span class="badge bg-info me-3">Total Karyawan: {{ count($reportData) }}</span>
                <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" class="btn btn-success btn-sm">
                    <i class="fas fa-file-pdf me-2"></i>Export PDF
                </a>
            </div>
        </div>

        <div class="card-body">
            <!-- Filter Form -->
            <form method="GET" class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">Bulan</label>
                    <input type="month" name="month" value="{{ $month }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Departemen</label>
                    <select name="department" class="form-select">
                        <option value="">Semua Departemen</option>
                        <!-- Ganti dengan loop real departments kalau ada -->
                        <option value="IT" {{ $department == 'IT' ? 'selected' : '' }}>IT</option>
                        <option value="HR" {{ $department == 'HR' ? 'selected' : '' }}>HR</option>
                        <!-- tambah lain -->
                    </select>
                </div>
                <div class="col-md-3">
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
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Filter</button>
                    <a href="{{ route('reports.attendance') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>

            <!-- Summary Table -->
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Nama Karyawan</th>
                            <th>Departemen</th>
                            <th>Hadir</th>
                            <th>Telat</th>
                            <th>Cuti</th>
                            <th>Sakit</th>
                            <th>Izin</th>
                            <th>Absent</th>
                            <th>Rate Hadir (%)</th>
                            <th>Detail Harian</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reportData as $empId => $data)
                        <tr>
                            <td><strong>{{ $data['employee']['name'] ?? 'Unknown' }}</strong></td>
                            <td>{{ $data['employee']['department'] ?? '-' }}</td>
                            <td><span class="badge bg-success">{{ $data['stats']['present'] ?? 0 }}</span></td>
                            <td><span class="badge bg-info">{{ $data['stats']['late'] ?? 0 }}</span></td>
                            <td><span class="badge bg-warning">{{ $data['stats']['leave'] ?? 0 }}</span></td>
                            <td><span class="badge bg-warning">{{ $data['stats']['sick'] ?? 0 }}</span></td>
                            <td><span class="badge bg-warning">{{ $data['stats']['permission'] ?? 0 }}</span></td>
                            <td><span class="badge bg-danger">{{ $data['stats']['absent'] ?? 0 }}</span></td>
                            <td><strong>{{ $data['attendance_rate'] }}%</strong></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#details-{{ $empId }}">
                                    <i class="fas fa-chevron-down"></i> Lihat
                                </button>
                            </td>
                        </tr>
                        <tr class="collapse" id="details-{{ $empId }}">
                            <td colspan="10" class="p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0">
                                        <thead class="table-secondary">
                                            <tr>
                                                <th>Tanggal</th>
                                                <th>Status</th>
                                                <th>Alasan</th>
                                                <th>Clock In</th>
                                                <th>Clock Out</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($data['details'] as $detail)
                                            <tr>
                                                <td>{{ \Carbon\Carbon::parse($detail['date'])->format('d M Y') }}</td>
                                                <td>
                                                    @php
                                                        $badgeClass = match($detail['status']) {
                                                            'Present', 'Late' => 'bg-success',
                                                            'Leave', 'Sick', 'Permission' => 'bg-warning',
                                                            default => 'bg-danger'
                                                        };
                                                    @endphp
                                                    <span class="badge {{ $badgeClass }}">{{ $detail['status'] }}</span>
                                                </td>
                                                <td>{{ $detail['reason'] }}</td>
                                                <td>{{ $detail['clock_in'] }}</td>
                                                <td>{{ $detail['clock_out'] }}</td>
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
