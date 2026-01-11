@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Dashboard</h1>
        <div>
            <span class="badge bg-info">
                <i class="fas fa-calendar-day me-1"></i>
                {{ now()->format('l, F d, Y') }}
            </span>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-primary text-uppercase mb-1">
                                Total Employees
                            </div>
                            <div class="h5 mb-0 fw-bold" id="totalEmployees">0</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-success text-uppercase mb-1">
                                Present Today
                            </div>
                            <div class="h5 mb-0 fw-bold" id="presentToday">0</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-check fa-2x text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-warning text-uppercase mb-1">
                                On Leave
                            </div>
                            <div class="h5 mb-0 fw-bold" id="onLeave">0</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-alt fa-2x text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-danger text-uppercase mb-1">
                                Absent Today
                            </div>
                            <div class="h5 mb-0 fw-bold" id="absentToday">0</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-times fa-2x text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts and Recent Activity -->
    <div class="row">
        <!-- Attendance Chart -->
        <div class="col-xl-8 col-lg-7 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0">Attendance This Month</h6>
                </div>
                <div class="card-body">
                    <canvas id="attendanceChart" height="250"></canvas>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="col-xl-4 col-lg-5 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Recent Activity</h6>
                    <a href="#" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush" id="recentActivity">
                        <!-- Activities will be loaded here -->
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Attendance -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Today's Attendance</h6>
                    <button class="btn btn-sm btn-primary" onclick="refreshAttendance()">
                        <i class="fas fa-sync-alt me-1"></i> Refresh
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="todayAttendanceTable">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Check In</th>
                                    <th>Check Out</th>
                                    <th>Status</th>
                                    <th>Location</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be loaded via JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Initialize chart
    let attendanceChart;

    $(document).ready(function() {
        loadDashboardData();

        // Listen for real-time updates
        document.addEventListener('attendanceUpdated', function(e) {
            loadDashboardData();
        });
    });

    async function loadDashboardData() {
        try {
            showLoading();

            // Fetch statistics
            const [employeesRes, attendanceRes, leavesRes] = await Promise.all([
                fetch('/api/v1/stats'),  // Ganti ini
                fetch('/api/v1/attendance/today'),  // Ganti ini
                fetch('/api/leaves/today')
            ]);

            const employeesData = await employeesRes.json();
            const attendanceData = await attendanceRes.json();
            const leavesData = await leavesRes.json();

            // Update statistics
            document.getElementById('totalEmployees').textContent = employeesData.total || 0;
            document.getElementById('presentToday').textContent = attendanceData.present || 0;
            document.getElementById('absentToday').textContent = attendanceData.absent || 0;
            document.getElementById('onLeave').textContent = leavesData.on_leave || 0;

            // Load chart
            loadAttendanceChart();

            // Load recent activity
            loadRecentActivity();

            // Load today's attendance
            loadTodayAttendance();

        } catch (error) {
            console.error('Error loading dashboard:', error);
            showToast('error', 'Failed to load dashboard data');
        } finally {
            hideLoading();
        }
    }

    function loadAttendanceChart() {
        // This is a sample chart - replace with real data
        const ctx = document.getElementById('attendanceChart').getContext('2d');

        if (attendanceChart) {
            attendanceChart.destroy();
        }

        attendanceChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                datasets: [{
                    label: 'Present',
                    data: [65, 59, 80, 81],
                    borderColor: '#27ae60',
                    backgroundColor: 'rgba(39, 174, 96, 0.1)',
                    tension: 0.3
                }, {
                    label: 'Absent',
                    data: [28, 48, 40, 19],
                    borderColor: '#e74c3c',
                    backgroundColor: 'rgba(231, 76, 60, 0.1)',
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });
    }

    async function loadRecentActivity() {
        try {
            const response = await fetch('/api/activities/recent');
            const activities = await response.json();

            const container = document.getElementById('recentActivity');
            container.innerHTML = '';

            activities.forEach(activity => {
                const activityEl = document.createElement('div');
                activityEl.className = 'list-group-item list-group-item-action';
                activityEl.innerHTML = `
                    <div class="d-flex w-100 justify-content-between">
                        <small>${activity.type}</small>
                        <small class="text-muted">${activity.time}</small>
                    </div>
                    <p class="mb-1">${activity.message}</p>
                `;
                container.appendChild(activityEl);
            });

        } catch (error) {
            console.error('Error loading activities:', error);
        }
    }

    async function loadTodayAttendance() {
        try {
            const response = await fetch('/api/attendance/today/list');
            const attendance = await response.json();

            const tbody = document.querySelector('#todayAttendanceTable tbody');
            tbody.innerHTML = '';

            attendance.forEach(record => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="avatar-sm bg-light rounded-circle me-2">
                                <i class="fas fa-user text-primary p-2"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">${record.employee_name}</h6>
                                <small class="text-muted">${record.department}</small>
                            </div>
                        </div>
                    </td>
                    <td>${record.check_in || '-'}</td>
                    <td>${record.check_out || '-'}</td>
                    <td>
                        <span class="badge status-${record.status}">${record.status}</span>
                    </td>
                    <td>${record.location || 'Office'}</td>
                `;
                tbody.appendChild(row);
            });

        } catch (error) {
            console.error('Error loading attendance:', error);
        }
    }

    function refreshAttendance() {
        loadDashboardData();
        showToast('success', 'Attendance data refreshed');
    }
</script>
@endpush
