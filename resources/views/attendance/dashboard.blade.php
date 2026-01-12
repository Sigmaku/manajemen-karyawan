@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4">Attendance Dashboard</h1>

    @php
        $todayDate = date('Y-m-d');

        // Cek data attendance hari ini untuk employee
        $hasCheckedIn = false;
        $todayAttendanceData = null;

        if (($role ?? 'employee') === 'employee') {
            // cek dari employeeAttendance (bulanan)
            if (isset($employeeAttendance) && is_array($employeeAttendance)) {
                foreach ($employeeAttendance as $date => $record) {
                    if ($date == $todayDate && isset($record['checkIn'])) {
                        $hasCheckedIn = true;
                        $todayAttendanceData = $record;
                        break;
                    }
                }
            }

            // kalau controller kamu ngirim todayAttendance khusus hari ini
            if (isset($todayAttendance) && $todayAttendance) {
                // pastikan formatnya sama (checkIn/checkOut)
                if (isset($todayAttendance['checkIn'])) {
                    $hasCheckedIn = true;
                    $todayAttendanceData = $todayAttendance;
                }
            }
        }
    @endphp

    {{-- ==========================================================
        EMPLOYEE: HANYA BOLEH LIHAT CHECK-IN VERIFICATION
    ========================================================== --}}
    @if(($role ?? 'employee') === 'employee')

        {{-- Kalau belum check-in: tampil form check-in saja --}}
        @if(!$hasCheckedIn)
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-warning text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-exclamation-circle me-2"></i>Daily Check-in Required
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <i class="fas fa-clock fa-4x text-warning mb-3"></i>
                                <h4>You need to check-in first</h4>
                                <p class="text-muted">Please check-in to generate your barcode for verification</p>
                            </div>

                            <form action="{{ route('attendance.check-in') }}" method="POST">
                                @csrf
                                <input type="hidden" name="employee_id" value="{{ $currentEmployeeId }}">

                                <div class="mb-3">
                                    <label class="form-label">Location *</label>
                                    <select class="form-select" name="location" required>
                                        <option value="Office">Office</option>
                                        <option value="Remote">Remote</option>
                                        <option value="Client Site">Client Site</option>
                                        <option value="Field">Field</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Notes (Optional)</label>
                                    <textarea class="form-control" name="notes" rows="2"
                                              placeholder="Any notes for today..."></textarea>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-sign-in-alt me-2"></i>Check In Now
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="text-center mt-3">
                        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Main Dashboard
                        </a>
                    </div>
                </div>
            </div>

        {{-- Kalau sudah check-in: tampil Check-in Verification saja --}}
        @else
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-qrcode me-2"></i>Check-in Verification
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <div class="alert alert-success mb-0">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-check-circle fa-2x me-3"></i>
                                            <div>
                                                <h6 class="mb-1">You're Checked In</h6>
                                                <p class="mb-0">
                                                    Check-in: <strong>{{ $todayAttendanceData['checkIn'] ?? '-' }}</strong>
                                                    @if(isset($todayAttendanceData['checkOut']))
                                                        | Check-out: <strong>{{ $todayAttendanceData['checkOut'] }}</strong>
                                                    @endif
                                                </p>
                                                @if(isset($todayAttendanceData['location']))
                                                    <small class="text-muted">
                                                        Location: {{ is_array($todayAttendanceData['location']) ? 'Location Saved' : $todayAttendanceData['location'] }}
                                                    </small>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4 text-end">
                                    <div class="btn-group" role="group">
                                        @if(!isset($todayAttendanceData['checkOut']))
                                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#checkOutModal">
                                                <i class="fas fa-sign-out-alt me-2"></i>Check Out
                                            </button>
                                        @endif

                                        <a href="{{ route('barcode.employee.view') }}" class="btn btn-primary">
                                            <i class="fas fa-qrcode me-2"></i>Get Check-in Barcode
                                        </a>
                                    </div>

                                    <div class="mt-2">
                                        <small class="text-muted">Show barcode to supervisor for verification</small>
                                    </div>
                                </div>
                            </div>

                            <div class="text-center mt-4">
                                <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Main Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

    {{-- ==========================================================
        ADMIN / MANAGER: TAMPILKAN FULL DASHBOARD (kodenya kamu)
    ========================================================== --}}
    @else

        <!-- Today's Attendance Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Today's Attendance ({{ date('F d, Y') }})</h5>
                <button class="btn btn-sm btn-primary" onclick="location.reload()">
                    <i class="fas fa-sync-alt me-1"></i> Refresh
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Hours</th>
                            <th>Location</th>
                            <th>Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($attendanceList as $item)
                            @php
                                // Perbaikan key: gunakan checkIn/checkOut
                                $ci = $item['attendance']['checkIn'] ?? null;
                                $co = $item['attendance']['checkOut'] ?? null;
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $item['employee']['name'] ?? 'Unknown' }}</strong><br>
                                    <small class="text-muted">{{ $item['employee']['department'] ?? '-' }}</small>
                                </td>
                                <td>{{ $item['attendance']['checkIn'] ?? $item['attendance']['check_in'] ?? '-' }}</td>
                                <td>{{ $item['attendance']['checkOut'] ?? $item['attendance']['check_out'] ?? '-' }}</td>
                                <td>
                                    @if($ci && $co)
                                        @php
                                            try {
                                                $start = \Carbon\Carbon::parse($todayDate.' '.$ci);
                                                $end = \Carbon\Carbon::parse($todayDate.' '.$co);
                                                echo $start->diff($end)->format('%H:%I');
                                            } catch (\Exception $e) {
                                                echo '-';
                                            }
                                        @endphp
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $item['attendance']['location'] ?? 'Office' }}</td>
                                @php
    $today = date('Y-m-d');

    // support dua kemungkinan key: checkIn/checkOut atau check_in/check_out
    $checkInStr = $item['attendance']['checkIn']
        ?? $item['attendance']['check_in']
        ?? null;

    $isLate = false;
    $lateMinutes = 0;

    if ($checkInStr) {
        // buang detik kalau ada 08:02:10 -> 08:02
        $t = trim((string)$checkInStr);
        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $t)) {
            $t = substr($t, 0, 5);
        }

        try {
            $checkIn = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $today.' '.$t);
        } catch (\Exception $e) {
            $checkIn = \Carbon\Carbon::parse($today.' '.$checkInStr);
        }

        $officeStart = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $today.' 08:00');

        if ($checkIn->gt($officeStart)) {
            $isLate = true;
            $lateMinutes = $officeStart->diffInMinutes($checkIn);
        }
    }
@endphp

<td>
    @if($checkInStr)
        @if($isLate)
            <span class="badge bg-warning text-dark">Late {{ $lateMinutes }}m</span>
        @else
            <span class="badge bg-success">Present</span>
        @endif
    @else
        <span class="badge bg-danger">Absent</span>
    @endif
</td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fas fa-clock fa-2x mb-3"></i>
                                        <p>No attendance recorded yet for today</p>
                                        <small>Check-in to record attendance</small>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <h6>Today's Summary</h6>
                        <div class="row mt-3">
                            <div class="col-6">
                                <div class="text-success">
                                    <h2>{{ count($attendanceList) }}</h2>
                                    <small>Present</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-danger">
                                    <h2>{{ $totalEmployees - count($attendanceList) }}</h2>
                                    <small>Absent</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Quick Report</h6>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('attendance.report') }}" method="GET" class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Select Month</label>
                                <input type="month" class="form-control" name="month"
                                       value="{{ date('Y-m') }}" max="{{ date('Y-m') }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Select Employee</label>
                                <select class="form-select" name="employee_id">
                                    <option value="">All Employees</option>
                                    @foreach($allEmployees as $id => $emp)
                                        <option value="{{ $id }}">{{ $emp['name'] }} ({{ $emp['department'] }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-chart-bar me-2"></i>Generate Report
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    @endif
</div>

<!-- Check Out Modal (dipakai employee saat verified section) -->
<div class="modal fade" id="checkOutModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-danger">
                    <i class="fas fa-sign-out-alt me-2"></i>Confirm Check Out
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <i class="fas fa-clock fa-4x text-warning mb-4"></i>
                <h6>Are you sure you want to check out now?</h6>
                <p class="text-muted">
                    Once checked out, you cannot check in again today.
                </p>
            </div>
            <div class="modal-footer border-0 justify-content-center">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancel
                </button>
                <form action="{{ route('attendance.check-out') }}" method="POST" class="d-inline">
                    @csrf
                    <input type="hidden" name="employee_id" value="{{ $currentEmployeeId }}">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-sign-out-alt me-2"></i>Yes, Check Out
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
