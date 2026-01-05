<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'Employee Management System'))</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

    <!-- Custom Styles -->
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            padding-top: 56px; /* Untuk fixed navbar */
        }

        .navbar {
            background-color: var(--primary-color);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .sidebar {
            min-height: calc(100vh - 56px);
            background-color: #fff;
            box-shadow: 1px 0 2px rgba(0,0,0,0.1);
            position: fixed;
            width: 250px;
            transition: all 0.3s;
            z-index: 1000;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s;
        }

        @media (max-width: 768px) {
            .sidebar {
                margin-left: -250px;
            }
            .main-content {
                margin-left: 0;
            }
            .sidebar.active {
                margin-left: 0;
            }
            .main-content.active {
                margin-left: 250px;
            }
        }

        .sidebar .nav-link {
            color: #333;
            padding: 12px 20px;
            border-left: 4px solid transparent;
        }

        .sidebar .nav-link:hover {
            background-color: #f8f9fa;
            border-left-color: var(--secondary-color);
        }

        .sidebar .nav-link.active {
            background-color: #e3f2fd;
            border-left-color: var(--secondary-color);
            color: var(--secondary-color);
        }

        .card {
            border: none;
            box-shadow: 0 0 15px rgba(0,0,0,0.05);
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .card-header {
            background-color: white;
            border-bottom: 1px solid #eee;
            font-weight: 600;
            padding: 15px 20px;
            border-radius: 10px 10px 0 0 !important;
        }

        .stat-card {
            border-left: 4px solid var(--secondary-color);
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .btn-primary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }

        .table th {
            border-top: none;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            color: var(--primary-color);
        }

        .badge {
            padding: 6px 12px;
            font-weight: 500;
        }

        .status-present {
            background-color: #d4edda;
            color: #155724;
        }

        .status-absent {
            background-color: #f8d7da;
            color: #721c24;
        }

        .status-leave {
            background-color: #fff3cd;
            color: #856404;
        }

        /* Loading spinner */
        .spinner-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255,255,255,0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            display: none;
        }

        .toast-container {
            position: fixed;
            top: 70px;
            right: 20px;
            z-index: 1050;
        }
    </style>

    @stack('styles')
</head>
<body>
    <!-- Loading Spinner -->
    <div class="spinner-overlay" id="loadingSpinner">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <!-- Toast Notification -->
    <div class="toast-container">
        @if(session('success'))
        <div class="toast align-items-center text-bg-success border-0 show" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-check-circle me-2"></i>
                    {{ session('success') }}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
        @endif

        @if(session('error'))
        <div class="toast align-items-center text-bg-danger border-0 show" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    {{ session('error') }}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
        @endif
    </div>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container-fluid">
            <!-- Sidebar Toggle Button -->
            <button class="navbar-toggler" type="button" id="sidebarToggle">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Brand -->
            <a class="navbar-brand" href="{{ url('/') }}">
                <i class="fas fa-users-cog me-2"></i>
                <strong>EMS</strong>
            </a>

            <!-- Right Side Navbar -->
            <div class="navbar-collapse collapse justify-content-end">
                <ul class="navbar-nav">
                    <!-- User Dropdown -->
                    @if(session('user'))
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i>
                            {{ session('user')['name'] }}
                            <small class="badge bg-light text-dark ms-1">{{ ucfirst(session('user')['role']) }}</small>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('profile') }}"><i class="fas fa-user me-2"></i> Profile</a></li>
                            <li><a class="dropdown-item" href="{{ route('settings.index') }}"><i class="fas fa-cog me-2"></i> Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item">
                                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                    @else
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('login') }}">
                            <i class="fas fa-sign-in-alt me-1"></i> Login
                        </a>
                    </li>
                    @endif
                </ul>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="p-3">
            @php
                $user = session('user');
                $role = $user['role'] ?? 'employee';
                $roleName = ucfirst($role);
                $userName = $user['name'] ?? 'User';
            @endphp

            <div class="text-center mb-4">
                <div class="avatar bg-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 60px; height: 60px;">
                    <i class="fas fa-user text-white fa-2x"></i>
                </div>
                <h6 class="mb-1">{{ $userName }}</h6>
                <small class="text-muted">{{ $roleName }}</small>
            </div>

            <!-- Navigation Menu -->
            <ul class="nav flex-column" id="sidebarMenu">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                        <i class="fas fa-home me-2"></i> Dashboard
                    </a>
                </li>

                <!-- Employee Management (Admin only) -->
                @if($role === 'admin')
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('employees.*') ? 'active' : '' }}" href="{{ route('employees.index') }}">
                        <i class="fas fa-users me-2"></i> Employee Management
                    </a>
                </li>
                @endif

                <!-- Attendance -->
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('attendance.*') ? 'active' : '' }}" href="{{ route('attendance.dashboard') }}">
                        <i class="fas fa-clock me-2"></i> Attendance
                    </a>
                </li>

                <!-- Leave Management -->
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('leaves.*') ? 'active' : '' }}" href="{{ route('leaves.index') }}">
                        <i class="fas fa-calendar-alt me-2"></i> Leave Management
                    </a>
                </li>

                <!-- Reports (Admin & Manager only) -->
                @if(in_array($role, ['admin', 'manager']))
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-chart-bar me-2"></i> Reports
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="{{ route('reports.attendance') }}">Attendance Report</a></li>
                        <li><a class="dropdown-item" href="{{ route('reports.employees') }}">Employee Report</a></li>
                        <li><a class="dropdown-item" href="{{ route('reports.leaves') }}">Leave Report</a></li>
                        <li><a class="dropdown-item" href="{{ route('reports.analytics') }}">Analytics</a></li>
                    </ul>
                </li>
                @endif

                <!-- Admin Only Section -->
                @if($role === 'admin')
                <li class="nav-item mt-3">
                    <small class="text-muted ms-3">ADMINISTRATION</small>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.*') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                        <i class="fas fa-user-shield me-2"></i> Admin Panel
                    </a>
                </li>
                @endif
            </ul>

            <!-- Quick Stats -->
            <div class="mt-5 p-3 bg-light rounded">
                <small class="text-muted">TODAY'S SUMMARY</small>
                <div class="row mt-2">
                    <div class="col-6">
                        <small>Present</small>
                        <h6 class="mb-0 text-success" id="todayPresent">0</h6>
                    </div>
                    <div class="col-6">
                        <small>Absent</small>
                        <h6 class="mb-0 text-danger" id="todayAbsent">0</h6>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        @yield('content')
    </main>

    <!-- Scripts -->
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <!-- Custom JavaScript -->
    <script>
        // CSRF Token setup for AJAX
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Sidebar Toggle
        document.getElementById('sidebarToggle')?.addEventListener('click', function() {
            document.getElementById('sidebar')?.classList.toggle('active');
            document.getElementById('mainContent')?.classList.toggle('active');
        });

        // Loading Spinner
        function showLoading() {
            const spinner = document.getElementById('loadingSpinner');
            if (spinner) spinner.style.display = 'flex';
        }

        function hideLoading() {
            const spinner = document.getElementById('loadingSpinner');
            if (spinner) spinner.style.display = 'none';
        }

        // Auto-hide toasts after 5 seconds
        $(document).ready(function() {
            setTimeout(() => {
                $('.toast').toast('hide');
            }, 5000);

            // Initialize DataTables
            $('.data-table').DataTable({
                responsive: true,
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search..."
                }
            });

            // Fetch today's attendance stats - ONLY if elements exist
            const todayPresentEl = document.getElementById('todayPresent');
            const todayAbsentEl = document.getElementById('todayAbsent');

            if (todayPresentEl && todayAbsentEl) {
                fetchTodayStats();
            }
        });

        // Fetch today's attendance statistics - SAFE VERSION
        async function fetchTodayStats() {
            try {
                const todayPresentEl = document.getElementById('todayPresent');
                const todayAbsentEl = document.getElementById('todayAbsent');

                if (!todayPresentEl || !todayAbsentEl) {
                    return; // Skip if elements don't exist
                }

                // Try to fetch from API
                const response = await fetch('/api/v1/stats');

                if (!response.ok) {
                    throw new Error('Failed to fetch stats');
                }

                const data = await response.json();

                if (data.success) {
                    const stats = data.data;
                    todayPresentEl.textContent = stats.present_today || 0;
                    todayAbsentEl.textContent = stats.absent_today || 0;
                }
            } catch (error) {
                console.error('Error fetching stats:', error);
                // Set default values
                const todayPresentEl = document.getElementById('todayPresent');
                const todayAbsentEl = document.getElementById('todayAbsent');

                if (todayPresentEl) todayPresentEl.textContent = '0';
                if (todayAbsentEl) todayAbsentEl.textContent = '0';
            }
        }

        // Global error handler for AJAX
        $(document).ajaxError(function(event, jqxhr, settings, thrownError) {
            hideLoading();
            console.error('AJAX Error:', thrownError);

            if (jqxhr.responseJSON && jqxhr.responseJSON.message) {
                showToast('error', jqxhr.responseJSON.message);
            } else {
                showToast('error', 'An error occurred. Please try again.');
            }
        });

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

        // Form validation helper
        function validateForm(formId) {
            const form = document.getElementById(formId);
            if (form && !form.checkValidity()) {
                form.classList.add('was-validated');
                return false;
            }
            return true;
        }
    </script>

    @stack('scripts')
</body>
</html>
