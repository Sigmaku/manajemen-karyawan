{{-- resources/views/reports/attendance.blade.php --}}
@extends('layouts.app')

@section('title', 'Laporan Absensi Karyawan')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Laporan Absensi</h1>
            <p class="text-muted mb-0">Rekapitulasi kehadiran karyawan per bulan</p>
        </div>
        <div>
            <button class="btn btn-outline-primary me-2" onclick="window.print()">
                <i class="fas fa-print me-2"></i>Cetak
            </button>
            <a href="{{ route('reports.attendance', ['month' => $selectedMonth, 'year' => $selectedYear, 'export' => 'pdf']) }}"
   class="btn btn-success">
    <i class="fas fa-file-pdf me-2"></i>Export PDF
</a>
        </div>
    </div>

    <!-- Filter Bulan & Tahun -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.attendance') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Pilih Bulan</label>
                        <select name="month" class="form-select" onchange="this.form.submit()">
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ sprintf('%02d', $m) }}" {{ $selectedMonth == $m ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Pilih Tahun</label>
                        <select name="year" class="form-select" onchange="this.form.submit()">
                            @for($y = date('Y') - 2; $y <= date('Y') + 1; $y++)
                                <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-2"></i>Tampilkan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="mb-1">Total Karyawan</h5>
                            <h3 class="mb-0">{{ $summary['totalEmployees'] }}</h3>
                        </div>
                        <i class="fas fa-users fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="mb-1">Hadir</h5>
                            <h3 class="mb-0">{{ $summary['present'] }}</h3>
                        </div>
                        <i class="fas fa-check-circle fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="mb-1">Izin/Cuti</h5>
                            <h3 class="mb-0">{{ $summary['onLeave'] }}</h3>
                        </div>
                        <i class="fas fa-calendar-alt fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-danger shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="mb-1">Absen</h5>
                            <h3 class="mb-0">{{ $summary['absent'] }}</h3>
                        </div>
                        <i class="fas fa-times-circle fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Absensi -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0">
                Rekap Absensi Bulan {{ \Carbon\Carbon::create()->month($selectedMonth)->format('F') }} {{ $selectedYear }}
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">No</th>
                            <th>Karyawan</th>
                            <th>Departemen</th>
                            <th>Hadir</th>
                            <th>Izin/Cuti</th>
                            <th>Absen</th>
                            <th>Lembur (jam)</th>
                            <th>Terlambat</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attendanceReport as $index => $record)
                            <tr>
                                <td class="ps-4">{{ $loop->iteration }}</td>
                                <td><strong>{{ $record['name'] }}</strong></td>
                                <td>{{ $record['department'] ?? '-' }}</td>
                                <td><span class="badge bg-success">{{ $record['present'] }}</span></td>
                                <td><span class="badge bg-warning">{{ $record['on_leave'] }}</span></td>
                                <td><span class="badge bg-danger">{{ $record['absent'] }}</span></td>
                                <td><span class="badge bg-info">{{ $record['overtime'] }}</span></td>
                                <td>{{ $record['late'] > 0 ? $record['late'] . ' kali' : '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="fas fa-inbox fa-3x mb-3"></i>
                                    <p>Tidak ada data absensi untuk periode ini</p>
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

@push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        @media print {
            body { background: white; }
            .no-print { display: none !important; }
            .card { border: 1px solid #000; }
        }
    </style>
@endpush
