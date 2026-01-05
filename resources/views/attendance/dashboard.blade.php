@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4">Attendance Dashboard</h1>

    <!-- Check In/Out Section
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card text-center h-100">
                <div class="card-body">
                    <h5 class="card-title">Check In</h5>
                    <div class="display-4 text-success mb-3">
                        <i class="fas fa-sign-in-alt"></i>
                    </div>
                    <button class="btn btn-success btn-lg w-100" data-bs-toggle="modal" data-bs-target="#checkInModal">
                        CHECK IN NOW
                    </button>
                    <p class="text-muted mt-3 mb-0" id="lastCheckIn">
                        Last check-in: Not yet
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card text-center h-100">
                <div class="card-body">
                    <h5 class="card-title">Check Out</h5>
                    <div class="display-4 text-danger mb-3">
                        <i class="fas fa-sign-out-alt"></i>
                    </div>
                    <button class="btn btn-danger btn-lg w-100" onclick="checkOut()">
                        CHECK OUT NOW
                    </button>
                    <p class="text-muted mt-3 mb-0" id="lastCheckOut">
                        Last check-out: Not yet
                    </p>
                </div>
            </div>
        </div>
    </div> -->

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
                        <tr>
                            <td>
                                <strong>{{ $item['employee']['name'] ?? 'Unknown' }}</strong><br>
                                <small class="text-muted">{{ $item['employee']['department'] ?? '-' }}</small>
                            </td>
                            <td>{{ $item['attendance']['check_in'] ?? '-' }}</td>
                            <td>{{ $item['attendance']['check_out'] ?? '-' }}</td>
                            <td>
                                @if(isset($item['attendance']['check_in']) && isset($item['attendance']['check_out']))
                                    @php
                                        try {
                                            $start = \Carbon\Carbon::parse($item['attendance']['check_in']);
                                            $end = \Carbon\Carbon::parse($item['attendance']['check_out']);
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
                            <td>
                                @if(isset($item['attendance']['check_in']))
                                    <span class="badge bg-success">Present</span>
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
                                    <small>Click CHECK IN to record first attendance</small>
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
                               placeholder="EMP001" required>
                        <small class="text-muted">Enter your employee ID</small>
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
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                $('#checkInModal').modal('hide');
                showToast('success', data.message);
                document.getElementById('lastCheckIn').textContent =
                    `Last check-in: ${data.time || 'Just now'}`;
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
        const employeeId = prompt('Enter your Employee ID:');
        if (!employeeId) return;

        fetch('{{ route("attendance.check-out") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ employee_id: employeeId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('success', data.message);
                document.getElementById('lastCheckOut').textContent =
                    `Last check-out: ${new Date().toLocaleTimeString()}`;
                // Refresh page setelah 1 detik
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

        $('.toast-container').append(toastHtml);
        $('.toast').toast('show');

        setTimeout(() => {
            $('.toast').toast('hide');
            setTimeout(() => $('.toast').remove(), 300);
        }, 3000);
    }
</script>
@endpush
@endsection
