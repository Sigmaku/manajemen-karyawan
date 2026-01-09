@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4">My Dashboard</h1>

    @php
        $user = session('user');
        $employeeId = $user['employee_id'] ?? null;

        // =========================
        // Monthly Stats
        // =========================
        $totalDays = count($employeeAttendance);
        $presentDays = 0;
        $totalHours = 0;
        $totalOvertime = 0;

        foreach ($employeeAttendance as $attendance) {
            if (isset($attendance['checkIn'])) {
                $presentDays++;
            }

            // Count hours & overtime only if already checked out
            if (isset($attendance['checkOut'])) {
                $totalHours += $attendance['hoursWorked'] ?? 0;
                $totalOvertime += $attendance['overtime'] ?? 0;
            }
        }

        $attendanceRate = $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 1) : 0;

        // =========================
        // Today's Attendance
        // =========================
        $today = date('Y-m-d');
        $todayAttendance = null;

        foreach ($employeeAttendance as $date => $attendance) {
            if ($date == $today) {
                $todayAttendance = $attendance;
                break;
            }
        }

        // For modal usage
        $todayCheckIn = $todayAttendance['checkIn'] ?? null;

        // =========================
        // Late calculation (NO DB CHANGE)
        // =========================
        $isLateToday = false;
        $lateMinutesToday = 0;

        if ($todayAttendance && isset($todayAttendance['checkIn'])) {
            $checkInCarbon = \Carbon\Carbon::parse($today . ' ' . $todayAttendance['checkIn']);
            $officeStart = \Carbon\Carbon::parse($today . ' 08:00');

            if ($checkInCarbon->gt($officeStart)) {
                $isLateToday = true;
                $lateMinutesToday = $officeStart->diffInMinutes($checkInCarbon);
            }
        }
    @endphp

    <!-- Employee Stats -->
    <div class="row mb-4">
        <div class="col-md-3 col-6">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-calendar-check fa-2x text-primary mb-3"></i>
                    <h6>Work Days This Month</h6>
                    <h3>{{ $totalDays }}</h3>
                    <br>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-6">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-clock fa-2x text-success mb-3"></i>
                    <h6>Present Days</h6>
                    <h3>{{ $presentDays }}</h3>
                    <br>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-6">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-chart-line fa-2x text-info mb-3"></i>
                    <h6>Attendance Rate</h6>
                    <h3>{{ $attendanceRate }}%</h3>
                    <br>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-6">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-business-time fa-2x text-warning mb-3"></i>
                    <h6>Overtime Hours</h6>
                    <h3>{{ number_format($totalOvertime, 2) }}</h3>
                    <small class="text-muted">hours</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Today's Status Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Today's Status ({{ date('F d, Y') }})</h5>
                </div>
                <div class="card-body">

                    @if($todayAttendance && isset($todayAttendance['checkIn']))
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div class="alert alert-success mb-0">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-check-circle fa-2x me-3"></i>
                                        <div>
                                            <h6 class="mb-1">
                                                You're Present Today
                                                @if($isLateToday)
                                                    <span class="badge bg-warning text-dark ms-2">Late</span>
                                                @endif
                                            </h6>

                                            <p class="mb-0">
                                                Check-in: <strong>{{ $todayAttendance['checkIn'] ?? '-' }}</strong>

                                                @if($isLateToday)
                                                    <small class="text-danger ms-2">({{ $lateMinutesToday }} min late)</small>
                                                @endif

                                                |
                                                @if(isset($todayAttendance['checkOut']))
                                                    Check-out: <strong>{{ $todayAttendance['checkOut'] }}</strong>
                                                @else
                                                    <span class="text-warning">Not checked out yet</span>
                                                @endif
                                            </p>

                                            @if(isset($todayAttendance['location']))
                                                <small class="text-muted">Location: {{ is_array($todayAttendance['location']) ? 'Location Saved' : $todayAttendance['location'] }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4 text-end">
                                @if(!isset($todayAttendance['checkOut']))
                                    <button type="button" class="btn btn-danger btn-lg" data-bs-toggle="modal" data-bs-target="#checkOutModal">
                                        <i class="fas fa-sign-out-alt me-2"></i>Check Out
                                    </button>
                                @else
                                    <button class="btn btn-secondary btn-lg" disabled>
                                        <i class="fas fa-check me-2"></i>Completed
                                    </button>
                                @endif
                            </div>
                        </div>

                    @else
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div class="alert alert-warning mb-0">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-clock fa-2x me-3"></i>
                                        <div>
                                            <h6 class="mb-1">Not Checked In Today</h6>
                                            <p class="mb-0">You haven't checked in yet for today</p>
                                            <small class="text-muted">Please check in to start your work day</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4 text-end">
                                <form action="{{ route('attendance.check-in') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="location" value="Office">
                                    <input type="hidden" name="notes" value="">
                                    <button type="submit" class="btn btn-success btn-lg">
                                        <i class="fas fa-sign-in-alt me-2"></i>Check In
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>

    <!-- Attendance Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">My Attendance This Month ({{ date('F Y') }})</h5>
                </div>

                <div class="card-body">
                    @if(count($employeeAttendance) > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Day</th>
                                        <th>Check In</th>
                                        <th>Check Out</th>
                                        <th>Hours</th>
                                        <th>Overtime</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach($employeeAttendance as $date => $record)
                                        @php
                                            // Late calculation per row (NO DB CHANGE)
                                            $isLateRow = false;
                                            $lateMinRow = 0;

                                            if (isset($record['checkIn'])) {
                                                $ci = \Carbon\Carbon::parse($date . ' ' . $record['checkIn']);
                                                $os = \Carbon\Carbon::parse($date . ' 08:00');

                                                if ($ci->gt($os)) {
                                                    $isLateRow = true;
                                                    $lateMinRow = $os->diffInMinutes($ci);
                                                }
                                            }
                                        @endphp

                                        <tr>
                                            <td>{{ \Carbon\Carbon::parse($date)->format('M d, Y') }}</td>
                                            <td>{{ \Carbon\Carbon::parse($date)->format('D') }}</td>
                                            <td>{{ $record['checkIn'] ?? '-' }}</td>
                                            <td>{{ $record['checkOut'] ?? '-' }}</td>
                                            <td>{{ number_format($record['hoursWorked'] ?? 0, 2) }}h</td>
                                            <td>{{ number_format($record['overtime'] ?? 0, 2) }}h</td>
                                            <td>
                                                @if(isset($record['checkIn']))
                                                    @if($isLateRow)
                                                        <span class="badge bg-warning text-dark">Late</span>
                                                        <small class="text-danger ms-1">{{ $lateMinRow }}m</small>
                                                    @else
                                                        <span class="badge bg-success">Present</span>
                                                    @endif
                                                @else
                                                    <span class="badge bg-danger">Absent</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>

                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-calendar fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No attendance records for this month</p>
                            <small>Start by checking in for today!</small>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Check Out Confirmation Modal -->
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
                    Check-in time: <strong>{{ $todayCheckIn ?? '-' }}</strong><br>
                    Current time: <strong><span id="currentTime">--:--:--</span></strong>
                </p>
                <p class="text-muted mb-0">
                    Once checked out, you cannot check in again today.
                </p>
            </div>

            <div class="modal-footer border-0 justify-content-center">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancel
                </button>
                <button type="button" class="btn btn-danger" onclick="submitCheckOut()">
                    <i class="fas fa-sign-out-alt me-2"></i>Yes, Check Out
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function submitCheckOut() {
    const employeeId = '{{ $employeeId }}';

    const formData = new FormData();
    formData.append('employee_id', employeeId);
    formData.append('_token', '{{ csrf_token() }}');

    fetch('{{ route("attendance.check-out") }}', {
        method: 'POST',
        body: formData,
        headers: { 'Accept': 'application/json' }
    })
    .then(response => {
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        return response.json();
    })
    .then(data => {
        if (data.success) {
            $('#checkOutModal').modal('hide');
            showToast('success', data.message || 'Successfully checked out!');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast('error', data.message || 'Check-out failed');
        }
    })
    .catch(error => {
        console.error('Check-out error:', error);
        showToast('error', 'Failed to check out. Please try again.');
    });
}

function showToast(type, message) {
    const toastHtml = `
        <div class="toast align-items-center text-bg-${type} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle me-2"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;

    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(container);
    }

    container.insertAdjacentHTML('beforeend', toastHtml);
    const toastEl = container.lastElementChild;
    const bsToast = new bootstrap.Toast(toastEl);
    bsToast.show();

    setTimeout(() => {
        bsToast.hide();
        setTimeout(() => toastEl.remove(), 300);
    }, 5000);
}
</script>
<script>
(function () {
    function pad2(n) {
        return String(n).padStart(2, '0');
    }

    function getTimeString() {
        const now = new Date();
        return `${pad2(now.getHours())}:${pad2(now.getMinutes())}:${pad2(now.getSeconds())}`;
    }

    function updateRealtimeClock() {
        const t = getTimeString();

        // Modal clock
        const modalClock = document.getElementById('currentTime');
        if (modalClock) modalClock.textContent = t;

        // Optional: live clock on page (if you add #liveClock)
        const pageClock = document.getElementById('liveClock');
        if (pageClock) pageClock.textContent = t;
    }

    // Update immediately + every 1s
    updateRealtimeClock();
    setInterval(updateRealtimeClock, 1000);

    // Ensure clock updates right when modal opens
    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('checkOutModal');
        if (modal) {
            modal.addEventListener('shown.bs.modal', updateRealtimeClock);
        }
    });
})();
</script>

@endpush
@endsection
