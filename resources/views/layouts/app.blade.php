<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'Employee Management System'))</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

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
            padding-top: 56px;
            margin: 0;
            overflow-x: hidden;
        }

        .navbar {
            background-color: var(--primary-color);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            z-index: 1060;
        }

        /* SIDEBAR - Gabungan Logic: Fixed di Desktop, Overlay di Mobile */
        .sidebar {
            position: fixed;
            top: 56px;
            bottom: 0;
            left: 0;
            width: 250px;
            background-color: #fff;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease-in-out;
            z-index: 1050;
            overflow-y: auto;
        }

        @media (max-width: 767.98px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
        }

        @media (min-width: 768px) {
            .sidebar { transform: translateX(0); }
            .main-content { margin-left: 250px; }
        }

        .main-content {
            padding: 20px;
            min-height: calc(100vh - 56px);
            transition: margin-left 0.3s ease-in-out;
        }

        /* Overlay Gelap (Punya Temen Lo) */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1040;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease-in-out;
        }

        .sidebar-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        /* Nav Styling (Punya Lo) */
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

        /* Component Styles */
        .card { border: none; box-shadow: 0 0 15px rgba(0,0,0,0.05); border-radius: 10px; margin-bottom: 20px; }
        .card-header { background-color: white; border-bottom: 1px solid #eee; font-weight: 600; padding: 15px 20px; border-radius: 10px 10px 0 0 !important; }
        .spinner-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.8); display: none; justify-content: center; align-items: center; z-index: 9999; }
        .toast-container { position: fixed; top: 70px; right: 20px; z-index: 1070; }
    </style>
    @stack('styles')
</head>
<body>

    <div class="spinner-overlay" id="loadingSpinner">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <div class="toast-container">
        @if(session('success'))
        <div class="toast align-items-center text-bg-success border-0 show" role="alert">
            <div class="d-flex">
                <div class="toast-body"><i class="fas fa-check-circle me-2"></i>{{ session('success') }}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
        @endif
        @if(session('error'))
        <div class="toast align-items-center text-bg-danger border-0 show" role="alert">
            <div class="d-flex">
                <div class="toast-body"><i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
        @endif
    </div>

    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" id="sidebarToggle">
                <span class="navbar-toggler-icon"></span>
            </button>
            <a class="navbar-brand" href="{{ url('/') }}">
                <i class="fas fa-users-cog me-2"></i><strong>EMS</strong>
            </a>

            <div class="navbar-collapse collapse justify-content-end">
                <ul class="navbar-nav">
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
                                    <button type="submit" class="dropdown-item"><i class="fas fa-sign-out-alt me-2"></i> Logout</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                    @else
                    <li class="nav-item"><a class="nav-link" href="{{ route('login') }}"><i class="fas fa-sign-in-alt me-1"></i> Login</a></li>
                    @endif
                </ul>
            </div>
        </div>
    </nav>

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

            <ul class="nav flex-column" id="sidebarMenu">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                        <i class="fas fa-home me-2"></i> Dashboard
                    </a>
                </li>

                @if($role === 'admin')
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('employees.*') ? 'active' : '' }}" href="{{ route('employees.index') }}">
                        <i class="fas fa-users me-2"></i> Employee Management
                    </a>
                </li>
                @endif

                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('attendance.*') ? 'active' : '' }}" href="{{ route('attendance.dashboard') }}">
                        <i class="fas fa-clock me-2"></i> Attendance
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('leaves.*') ? 'active' : '' }}" href="{{ route('leaves.index') }}">
                        <i class="fas fa-calendar-alt me-2"></i> Leave Management
                    </a>
                </li>

                <!-- Reports (Admin & Manager only) -->
                @if(in_array($role, ['admin', 'manager']))
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('reports.attendance') ? 'active' : '' }}" href="{{ route('reports.attendance') }}">
                        <i class="fas fa-chart-bar me-2"></i> Attendance Report
                    </a>
                </li>
                @endif

                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.index') }}">
                        <i class="fas fa-cog me-2"></i> Settings
                    </a>
                </li>

                @if($role === 'admin')
                <li class="nav-item mt-3"><small class="text-muted ms-3">ADMINISTRATION</small></li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.*') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                        <i class="fas fa-user-shield me-2"></i> Admin Panel
                    </a>
                </li>
                @endif
            </ul>

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

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <main class="main-content" id="mainContent">
        @yield('content')
    </main>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

        // Sidebar Toggle & Overlay Logic
        const sidebar = $('#sidebar');
        const overlay = $('#sidebarOverlay');
        const toggleBtn = $('#sidebarToggle');

        toggleBtn.on('click', function() {
            sidebar.toggleClass('active');
            overlay.toggleClass('active');
        });

        overlay.on('click', function() {
            sidebar.removeClass('active');
            overlay.removeClass('active');
        });

        // Tutup otomatis saat klik menu di mobile
        $('.sidebar .nav-link').on('click', function() {
            if (window.innerWidth < 768) {
                sidebar.removeClass('active');
                overlay.removeClass('active');
            }
        });

        // JavaScript Helpers (Punya Lo)
        function showLoading() { $('#loadingSpinner').css('display', 'flex'); }
        function hideLoading() { $('#loadingSpinner').hide(); }

        function showToast(type, message) {
            const toastHtml = `
                <div class="toast align-items-center text-bg-${type === 'success' ? 'success' : 'danger'} border-0" role="alert">
                    <div class="d-flex">
                        <div class="toast-body"><i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle me-2"></i>${message}</div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>`;
            $('.toast-container').append(toastHtml);
            const toastElement = $('.toast-container .toast').last()[0];
            const bsToast = new bootstrap.Toast(toastElement);
            bsToast.show();
            setTimeout(() => { $(toastElement).remove(); }, 6000);
        }

        $(document).ready(function() {
            // DataTable init
            $('.data-table').DataTable({
                responsive: true,
                language: { search: "_INPUT_", searchPlaceholder: "Search..." }
            });

            // Stats Fetcher
            if (document.getElementById('todayPresent')) {
                fetchTodayStats();
            }

            // Auto hide session toasts
            setTimeout(() => { $('.toast').toast('hide'); }, 5000);
        });

        async function fetchTodayStats() {
            try {
                const res = await fetch('/api/v1/stats');
                const data = await res.json();
                if (data.success) {
                    $('#todayPresent').text(data.data.present_today || 0);
                    $('#todayAbsent').text(data.data.absent_today || 0);
                }
            } catch (e) { console.error('Stats error'); }
        }

        // AJAX Error Handler (Punya Lo)
        $(document).ajaxError(function(event, jqxhr) {
            hideLoading();
            const msg = jqxhr.responseJSON?.message || 'An error occurred.';
            showToast('error', msg);
        });
    </script>
    @stack('scripts')
</body>
</html>
