<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\AdminController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Semua route aplikasi didefinisikan di sini.
| Route dilindungi middleware auth.check dan role-based access.
|
*/

// ==================== TEST & DEVELOPMENT ROUTES ====================
Route::get('/test-route', function () {
    return '✅ Route is working! Laravel version: ' . app()->version();
});

Route::get('/test-firebase-connection', function () {
    try {
        $firebase = app(\App\Services\FirebaseService::class);
        $db = $firebase->getDatabase();
        $db->getReference('test')->set(['status' => 'connected', 'time' => now()->toISOString()]);
        return '✅ Firebase connected successfully!';
    } catch (\Exception $e) {
        return '❌ Firebase error: ' . $e->getMessage();
    }
});

// ==================== AUTHENTICATION ROUTES (Publik) ====================
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ==================== PROTECTED ROUTES (Harus Login) ====================
Route::middleware(['auth.check'])->group(function () {

    // Dashboard Utama
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Profile & Settings - Akses semua role
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');

    // ==================== EMPLOYEE MANAGEMENT (Admin & Manager Only) ====================
    Route::middleware(['role:admin,manager'])->prefix('employees')->name('employees.')->group(function () {
        // CRUD Dasar Karyawan
        Route::get('/', [EmployeeController::class, 'index'])->name('index');
        Route::get('/create', [EmployeeController::class, 'create'])->name('create');
        Route::post('/', [EmployeeController::class, 'store'])->name('store');
        Route::get('/{id}', [EmployeeController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [EmployeeController::class, 'edit'])->name('edit');
        Route::put('/{id}', [EmployeeController::class, 'update'])->name('update');
        Route::delete('/{id}', [EmployeeController::class, 'destroy'])->name('destroy');

        // Account & Password Management (Fitur Baru Lo)
        Route::post('/{id}/create-account', [EmployeeController::class, 'createAccount'])->name('create-account');
        Route::post('/{id}/reset-password', [EmployeeController::class, 'resetPassword'])->name('reset-password');
        Route::post('/{id}/update-password', [EmployeeController::class, 'updatePassword'])->name('update-password');
        Route::post('/{id}/force-password-change', [EmployeeController::class, 'forcePasswordChange'])->name('force-password-change');
        Route::get('/{id}/password-history', [EmployeeController::class, 'getPasswordHistory'])->name('password-history');

        // Password policy check
        Route::post('/check-password-policy', [EmployeeController::class, 'checkPasswordPolicy'])->name('password.policy.check');
    });

    // ==================== ATTENDANCE ROUTES ====================
    Route::prefix('attendance')->name('attendance.')->group(function () {
        Route::get('/dashboard', [AttendanceController::class, 'dashboard'])->name('dashboard');
        Route::get('/report', [AttendanceController::class, 'report'])->name('report');
        Route::get('/history', [AttendanceController::class, 'history'])->name('history');

        // Check-in & Check-out khusus karyawan
        Route::middleware(['role:employee'])->group(function () {
            Route::post('/check-in', [AttendanceController::class, 'checkIn'])->name('check-in');
            Route::post('/check-out', [AttendanceController::class, 'checkOut'])->name('check-out');
        });

        // Manual entry hanya untuk admin
        Route::middleware(['role:admin'])->post('/manual', [AttendanceController::class, 'manualEntry'])->name('manual');
    });

    // ==================== LEAVE MANAGEMENT ROUTES ====================
    Route::prefix('leaves')->name('leaves.')->group(function () {


        // Halaman utama cuti
        // Admin & Manager: lihat semua cuti
        Route::middleware(['role:admin,manager'])->get('/', [LeaveController::class, 'index'])->name('index');

        // Employee: lihat cuti sendiri
        Route::middleware(['role:employee'])->get('/my-leaves', [LeaveController::class, 'myLeaves'])->name('my');
        Route::middleware(['role:employee'])
            ->get('/api/my', [LeaveController::class, 'apiMyLeaves'])
            ->name('api.my');

        Route::middleware(['role:admin,manager'])
            ->get('/api/all', [LeaveController::class, 'apiAllLeaves'])
            ->name('api.all');



        // Ajukan cuti baru - semua role
        Route::get('/create', [LeaveController::class, 'create'])->name('create');
        Route::post('/', [LeaveController::class, 'store'])->name('store');

        // Lihat detail cuti - semua role
        Route::get('/{id}', [LeaveController::class, 'show'])->name('show');

        // ==================== EMPLOYEE ONLY ====================
        Route::middleware(['role:employee'])->group(function () {
            Route::get('/{id}/edit', [LeaveController::class, 'edit'])->name('edit');
            Route::put('/{id}', [LeaveController::class, 'update'])->name('update');
            Route::delete('/{id}', [LeaveController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/cancel', [LeaveController::class, 'cancel'])->name('cancel');
        });

        // ==================== ADMIN & MANAGER ONLY ====================
        Route::middleware(['role:admin,manager'])->group(function () {
            Route::get('/calendar', [LeaveController::class, 'calendar'])->name('calendar');
            Route::post('/{id}/approve', [LeaveController::class, 'approve'])->name('approve');
            Route::post('/{id}/reject', [LeaveController::class, 'reject'])->name('reject');
        });
    });


    // ==================== REPORTS (Admin & Manager Only) ====================
    Route::middleware(['role:admin,manager'])->prefix('reports')->name('reports.')->group(function () {
        Route::get('/attendance', [ReportController::class, 'attendance'])->name('attendance');
        Route::get('/employees', [ReportController::class, 'employees'])->name('employees');
        Route::get('/leaves', [ReportController::class, 'leaves'])->name('leaves');
        Route::get('/analytics', [ReportController::class, 'analytics'])->name('analytics');

        Route::get('/export/attendance', [ReportController::class, 'exportAttendance'])->name('export.attendance');
        Route::get('/export/employees', [ReportController::class, 'exportEmployees'])->name('export.employees');
        Route::get('/export/leaves', [ReportController::class, 'exportLeaves'])->name('export.leaves');
    });

    // ==================== ADMIN PANEL (Admin Only) ====================
    Route::middleware(['role:admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/users', [AdminController::class, 'users'])->name('users');
        Route::post('/users', [AdminController::class, 'createUser'])->name('users.create');
        Route::post('/users/{id}/reset-password', [AdminController::class, 'resetPassword'])->name('users.reset');
        Route::get('/logs', [AdminController::class, 'logs'])->name('logs');
        Route::get('/backup', [AdminController::class, 'backup'])->name('backup');
        Route::post('/backup', [AdminController::class, 'createBackup'])->name('backup.create');
        Route::post('/backup/{id}/restore', [AdminController::class, 'restoreBackup'])->name('backup.restore');
        Route::get('/settings', [AdminController::class, 'systemSettings'])->name('settings');
        Route::put('/settings', [AdminController::class, 'updateSystemSettings'])->name('settings.update');

        // Bulk Operations
        Route::post('/employees/bulk-delete', [AdminController::class, 'bulkDelete'])->name('employees.bulk.delete');
        Route::post('/employees/bulk-status', [AdminController::class, 'bulkStatus'])->name('employees.bulk.status');
        Route::post('/attendance/bulk-entry', [AdminController::class, 'bulkEntry'])->name('attendance.bulk.entry');
        Route::post('/leaves/bulk-approve', [AdminController::class, 'bulkApprove'])->name('leaves.bulk.approve');
    });
});

// ==================== FALLBACK ROUTE ====================
Route::fallback(function () {
    return redirect()->route('dashboard')->with('error', 'Halaman tidak ditemukan.');
});
