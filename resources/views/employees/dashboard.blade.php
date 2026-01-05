@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4">My Dashboard</h1>

    @php
        $user = session('user');
        $employeeId = $user['employee_id'] ?? null;

        // Calculate stats
        $totalDays = count($employeeAttendance);
        $presentDays = 0;
        $totalHours = 0;
        $totalOvertime = 0;

        foreach ($employeeAttendance as $attendance) {
            if (isset($attendance['checkIn'])) {
                $presentDays++;
            }
            $totalHours += $attendance['hoursWorked'] ?? 0;
            $totalOvertime += $attendance['overtime'] ?? 0;
        }

        $attendanceRate = $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 1) : 0;
    @endphp

    <!-- Employee Stats -->
    <div class="row mb-4">
        <div class="col-md-3 col-6">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-calendar-check fa-2x text-primary mb-3"></i>
                    <h6>Work Days This Month</h6>
                    <h3>{{ $totalDays }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-clock fa-2x text-success mb-3"></i>
                    <h6>Present Days</h6>
                    <h3>{{ $presentDays }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-chart-line fa-2x text-info mb-3"></i>
                    <h6>Attendance Rate</h6>
                    <h3>{{ $attendanceRate }}%</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-business-time fa-2x text-warning mb-3"></i>
                    <h6>Overtime Hours</h6>
                    <h3>{{ $totalOvertime }}</h3>
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
                    @php
                        $today = date('Y-m-d');
                        $todayAttendance = null;

                        foreach ($employeeAttendance as $date => $attendance) {
                            if ($date == $today) {
                                $todayAttendance = $attendance;
                                break;
                            }
                        }
                    @endphp

                    <!-- Di bagian Today's Status Card -->
                    @if($todayAttendance && isset($todayAttendance['checkIn']))
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="alert alert-success mb-0">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-check-circle fa-2x me-3"></i>
                                    <div>
                                        <h6 class="mb-1">You're Present Today</h6>
                                        <p class="mb-0">
                                            Check-in: <strong>{{ $todayAttendance['checkIn'] ?? '-' }}</strong> |
                                            @if(isset($todayAttendance['checkOut']))
                                                Check-out: <strong>{{ $todayAttendance['checkOut'] }}</strong>
                                            @else
                                                <span class="text-warning">Not checked out yet</span>
                                            @endif
                                        </p>
                                        @if(isset($todayAttendance['location']))
                                        <small class="text-muted">Location: {{ $todayAttendance['location'] }}</small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            @if(!isset($todayAttendance['checkOut']))
                            <form action="{{ route('attendance.check-out') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-danger btn-lg">
                                    <i class="fas fa-sign-out-alt me-2"></i>Check Out
                                </button>
                            </form>
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

    <!-- Recent Attendance -->
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
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($employeeAttendance as $date => $record)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($date)->format('M d, Y') }}</td>
                                    <td>{{ \Carbon\Carbon::parse($date)->format('D') }}</td>
                                    <td>{{ $record['checkIn'] ?? '-' }}</td>
                                    <td>{{ $record['checkOut'] ?? '-' }}</td>
                                    <td>{{ $record['hoursWorked'] ?? 0 }}h</td>
                                    <td>
                                        @if(isset($record['checkIn']))
                                            <span class="badge bg-success">Present</span>
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

<!-- Check In Modal -->
<div class="modal fade" id="checkInModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Check In</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="checkInForm">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Employee ID *</label>
                        <input type="text" class="form-control" name="employee_id"
                               value="{{ $employeeId }}" readonly required>
                        <small class="text-muted">Your employee ID</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Location *</label>
                        <select class="form-select" name="location" required>
                            <option value="Office">Office</option>
                            <option value="Remote">Remote</option>
                            <option value="Client Site">Client Site</option>
                            <option value="Business Trip">Business Trip</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" name="notes" rows="2" placeholder="Optional notes..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitCheckIn()">
                    <i class="fas fa-check me-2"></i>Check In
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Check In Function
    function submitCheckIn() {
        const form = document.getElementById('checkInForm');
        const formData = new FormData(form);

fetch('{{ route("attendance.check-in") }}', {
    method: 'POST',
    body: formData,
    headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Accept': 'application/json' // 🔥 INI PENTING
    }
})

        .then(response => response.json())
        .then(data => {
            if (data.success) {
                $('#checkInModal').modal('hide');
                showToast('success', data.message);
                // Refresh page setelah 1 detik
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast('error', data.message || 'Check-in failed');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('error', 'Network error. Please try again.');
        });
    }

    // Check Out Function
    function checkOut() {
        const employeeId = '{{ $employeeId }}';

        if (!confirm('Are you sure you want to check out?')) {
            return;
        }

fetch('{{ route("attendance.check-out") }}', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Accept': 'application/json' // 🔥 INI PENTING
    },
    body: JSON.stringify({ employee_id: employeeId })
})

        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('success', data.message);
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast('error', data.message || 'Check-out failed');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('error', 'Network error. Please try again.');
        });
    }

    // Toast notification function
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

        const container = document.querySelector('.toast-container');
        if (container) {
            container.insertAdjacentHTML('beforeend', toastHtml);
            const toast = container.lastElementChild;
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();

            setTimeout(() => {
                bsToast.hide();
                setTimeout(() => toast.remove(), 300);
            }, 5000);
        }
    }
</script>
@endpush
@endsection
