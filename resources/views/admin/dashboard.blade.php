@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4">Admin Dashboard</h1>

    <!-- Admin Stats -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <h6 class="mb-0">Total Employees</h6>
                    <h2 class="mt-2">{{ $stats['total_employees'] ?? 0 }}</h2>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <h6 class="mb-0">Present Today</h6>
                    <h2 class="mt-2">{{ $stats['present_today'] ?? 0 }}</h2>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">
                    <h6 class="mb-0">Pending Leaves</h6>
                    <h2 class="mt-2">{{ $stats['pending_leaves'] ?? 0 }}</h2>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-info text-white mb-4">
                <div class="card-body">
                    <h6 class="mb-0">Active Employees</h6>
                    <h2 class="mt-2">{{ $stats['active_employees'] ?? 0 }}</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Two Column Layout -->
    <div class="row">
        <!-- Left Column -->
        <div class="col-md-8">
<!-- Today's Attendance -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Today's Attendance ({{ date('F d, Y') }})</h5>
        <div>
            <span class="badge bg-success">Present: {{ count($attendanceList) }}</span>
            <span class="badge bg-danger ms-2">Absent: {{ $stats['total_employees'] - count($attendanceList) }}</span>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="attendanceTable">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                        <th>Location</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attendanceList as $index => $item)
                    <tr class="attendance-row">
                        <td>
                            <strong>{{ $item['employee']['name'] ?? 'Unknown' }}</strong><br>
                            <small class="text-muted">{{ $item['employee']['position'] ?? '-' }}</small>
                        </td>
                        <td>{{ $item['employee']['department'] ?? '-' }}</td>
                        <td>{{ $item['attendance']['checkIn'] ?? '-' }}</td>
                        <td>{{ $item['attendance']['checkOut'] ?? '-' }}</td>
                        <td>{{ $item['attendance']['location'] ?? 'Office' }}</td>
                        <td>
                            @if(isset($item['attendance']['checkIn']))
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
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Manual dengan JS -->
        <div id="paginationControls" class="d-flex justify-content-between align-items-center mt-4" style="display: none;">
            <div>
                Showing <span id="showingStart">1</span> to <span id="showingEnd">10</span> of <span id="totalItems">{{ count($attendanceList) }}</span> entries
            </div>
            <nav>
                <ul class="pagination mb-0">
                    <li class="page-item"><button class="page-link" id="prevBtn">Previous</button></li>
                    <li class="page-item"><button class="page-link" id="nextBtn">Next</button></li>
                </ul>
            </nav>
        </div>
    </div>
</div>
@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const rows = document.querySelectorAll('#attendanceTable .attendance-row');
    const totalItems = rows.length;
    const itemsPerPage = 10;
    let currentPage = 1;

    if (totalItems <= itemsPerPage) {
        document.getElementById('paginationControls').style.display = 'none';
        return;
    }

    document.getElementById('paginationControls').style.display = 'flex';
    document.getElementById('totalItems').textContent = totalItems;

    function showPage(page) {
        const start = (page - 1) * itemsPerPage;
        const end = start + itemsPerPage;

        rows.forEach((row, index) => {
            row.style.display = (index >= start && index < end) ? '' : 'none';
        });

        document.getElementById('showingStart').textContent = start + 1;
        document.getElementById('showingEnd').textContent = Math.min(end, totalItems);

        // Update button state
        document.getElementById('prevBtn').parentElement.classList.toggle('disabled', page === 1);
        document.getElementById('nextBtn').parentElement.classList.toggle('disabled', page === Math.ceil(totalItems / itemsPerPage));
    }

    document.getElementById('prevBtn').addEventListener('click', () => {
        if (currentPage > 1) {
            currentPage--;
            showPage(currentPage);
        }
    });

    document.getElementById('nextBtn').addEventListener('click', () => {
        if (currentPage < Math.ceil(totalItems / itemsPerPage)) {
            currentPage++;
            showPage(currentPage);
        }
    });

    showPage(1); // Initial display
});
</script>
@endsection

            <!-- Department Stats -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Employees by Department</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($departmentStats as $dept => $count)
                        <div class="col-md-3 col-6 mb-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h6 class="text-muted">{{ $dept }}</h6>
                                    <h3>{{ $count }}</h3>
                                    <small>employees</small>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-md-4">
            <!-- Quick Actions -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('employees.create') }}" class="btn btn-primary">
                            <i class="fas fa-user-plus me-2"></i>Add Employee
                        </a>
                        <a href="{{ route('employees.index') }}" class="btn btn-success">
                            <i class="fas fa-users me-2"></i>Manage Employees
                        </a>
                        <a href="{{ route('attendance.dashboard') }}" class="btn btn-info">
                            <i class="fas fa-clock me-2"></i>Attendance
                        </a>
                    </div>
                </div>
            </div>

            <!-- System Info -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">System Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted">Company</small>
                        <p class="mb-1">{{ env('APP_NAME', 'Employee Management System') }}</p>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Database</small>
                        <p class="mb-1">Firebase Realtime</p>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Total Users</small>
                        <p class="mb-1">{{ count($employees) }} employees</p>
                    </div>
                    <div>
                        <small class="text-muted">Today's Date</small>
                        <p class="mb-0">{{ date('F d, Y') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
