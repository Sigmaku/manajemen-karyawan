@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4">Manager Dashboard</h1>

    <!-- Manager Stats -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <h6 class="mb-0">Team Members</h6>
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
                    <h6 class="mb-0">Attendance Rate</h6>
                    @php
                        $total = $stats['total_employees'] ?? 1;
                        $present = $stats['present_today'] ?? 0;
                        $rate = $total > 0 ? round(($present / $total) * 100, 1) : 0;
                    @endphp
                    <h2 class="mt-2">{{ $rate }}%</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Two Column Layout -->
    <div class="row">
        <!-- Left Column -->
        <div class="col-md-8">
            <!-- Team Attendance -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Team Attendance Today ({{ date('F d, Y') }})</h5>
                    <div>
                        <span class="badge bg-success">Present: {{ count($attendanceList) }}</span>
                        <span class="badge bg-danger ms-2">Absent: {{ $stats['total_employees'] - count($attendanceList) }}</span>
                    </div>
                </div>
                <div class="card-body">
                    @if(count($attendanceList) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Department</th>
                                    <th>Check In</th>
                                    <th>Check Out</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($attendanceList as $item)
                                <tr>
                                    <td>
                                        <strong>{{ $item['employee']['name'] ?? 'Unknown' }}</strong>
                                    </td>
                                    <td>{{ $item['employee']['department'] ?? '-' }}</td>
                                    <td>{{ $item['attendance']['checkIn'] ?? '-' }}</td>
                                    <td>{{ $item['attendance']['checkOut'] ?? '-' }}</td>
                                    <td>
                                        @if(isset($item['attendance']['checkIn']))
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
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No attendance data available for today</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Department Stats -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Team Distribution</h5>
                </div>
                <div class="card-body">
                    @if(count($departmentStats) > 0)
                    <div class="row">
                        @foreach($departmentStats as $dept => $count)
                        <div class="col-md-4 col-6 mb-3">
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
                    @else
                    <div class="text-center py-4">
                        <p class="text-muted">No department data available</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-md-4">
            <!-- Manager Actions (DIPERBAIKI: Hanya tombol yang diperlukan) -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Manager Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('leaves.index') }}" class="btn btn-warning">
                            <i class="fas fa-calendar-check me-2"></i>Approve Leaves
                        </a>
                        <a href="{{ route('attendance.report') }}" class="btn btn-info">
                            <i class="fas fa-chart-bar me-2"></i>View Reports
                        </a>
                    </div>
                </div>
            </div>

            <!-- Pending Approvals -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Pending Approvals</h5>
                </div>
                <div class="card-body">
                    @php
                        $pendingCount = $stats['pending_leaves'] ?? 0;
                    @endphp

                    @if($pendingCount > 0)
                    <div class="alert alert-warning">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                            <div>
                                <h6 class="mb-1">{{ $pendingCount }} Pending Leave Requests</h6>
                                <p class="mb-0">Need your approval</p>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('leaves.index') }}" class="btn btn-warning w-100">
                        <i class="fas fa-calendar-alt me-2"></i>Review Now
                    </a>
                    @else
                    <div class="text-center py-3">
                        <i class="fas fa-check-circle fa-2x text-success mb-3"></i>
                        <p>No pending approvals</p>
                        <small class="text-muted">All leave requests are processed</small>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Quick Team Stats -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Team Quick Stats</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted">Total Team Members</small>
                        <p class="mb-1">{{ $stats['total_employees'] ?? 0 }}</p>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Present Today</small>
                        <p class="mb-1">{{ $stats['present_today'] ?? 0 }} employees</p>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Departments</small>
                        <p class="mb-1">{{ count($departmentStats) }} departments</p>
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
