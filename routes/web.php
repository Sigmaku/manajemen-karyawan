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
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// ==================== PUBLIC ROUTES ====================
Route::get('/test-route', function () {
    return '✅ Route is working! Laravel version: ' . app()->version();
});

Route::get('/test-firebase-connection', function () {
    try {
        $firebase = app(App\Services\FirebaseService::class);
        $db = $firebase->getDatabase();
        $db->getReference('test')->set(['test' => 'success', 'time' => now()]);
        return '✅ Firebase connected successfully!';
    } catch (\Exception $e) {
        return '❌ Firebase error: ' . $e->getMessage();
    }
});

// Authentication
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ==================== PROTECTED ROUTES ====================
Route::middleware(['auth.check'])->group(function () {

    // Dashboard - Role based
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Profile - All roles
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Settings - All roles
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');

    // ==================== EMPLOYEE ROUTES ====================
    // Employee Management - Admin & Manager only
    Route::middleware(['role:admin,manager'])->prefix('employees')->name('employees.')->group(function () {
        Route::get('/', [EmployeeController::class, 'index'])->name('index');
        Route::get('/create', [EmployeeController::class, 'create'])->name('create');
        Route::post('/', [EmployeeController::class, 'store'])->name('store');
        Route::get('/{id}', [EmployeeController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [EmployeeController::class, 'edit'])->name('edit');
        Route::put('/{id}', [EmployeeController::class, 'update'])->name('update');
        Route::delete('/{id}', [EmployeeController::class, 'destroy'])->name('destroy');

        // Tambahkan routes untuk account management
        Route::post('/{id}/create-account', [EmployeeController::class, 'createAccount'])->name('create-account');
        Route::post('/{id}/reset-password', [EmployeeController::class, 'resetPassword'])->name('reset-password');

        Route::post('/{id}/update-password', [EmployeeController::class, 'updatePassword'])
            ->name('update-password');

        Route::post('/{id}/force-password-change', [EmployeeController::class, 'forcePasswordChange'])
            ->name('force-password-change');

        Route::get('/{id}/password-history', [EmployeeController::class, 'getPasswordHistory'])
            ->name('password-history');

        // Password policy check (public)
        Route::post('/check-password-policy', [EmployeeController::class, 'checkPasswordPolicy'])
            ->name('password.policy.check');
    });

    // ==================== ATTENDANCE ROUTES ====================
    // Attendance Routes - All roles with different access
    Route::prefix('attendance')->name('attendance.')->group(function () {
        // Dashboard - All roles (different views)
        Route::get('/dashboard', [AttendanceController::class, 'dashboard'])->name('dashboard');

        // Reports - All roles (employee sees only their own)
        Route::get('/report', [AttendanceController::class, 'report'])->name('report');

        // History - All roles (employee sees only their own)
        Route::get('/history', [AttendanceController::class, 'history'])->name('history');

        // Check-in/out - Employees only
        Route::middleware(['role:employee'])->group(function () {
            Route::post('/check-in', [AttendanceController::class, 'checkIn'])->name('check-in');
            Route::post('/check-out', [AttendanceController::class, 'checkOut'])->name('check-out');
        });

        // Manual entry - Admin only
        Route::middleware(['role:admin'])->post('/manual', [AttendanceController::class, 'manualEntry'])->name('manual');
    });

    // ==================== LEAVE ROUTES ====================
    // Leaves - All roles with different access
    Route::prefix('leaves')->name('leaves.')->group(function () {
        // Index - All roles see different data
        Route::get('/', [LeaveController::class, 'index'])->name('index');

        // Create - All employees can apply
        Route::get('/create', [LeaveController::class, 'create'])->name('create');
        Route::post('/', [LeaveController::class, 'store'])->name('store');

        // Show - All roles (employee sees only their own)
        Route::get('/{id}', [LeaveController::class, 'show'])->name('show');

        // Edit - All employees can edit their own pending leaves
        Route::get('/{id}/edit', [LeaveController::class, 'edit'])->name('edit');

        // Destroy - All employees can delete their own pending leaves
        Route::delete('/{id}', [LeaveController::class, 'destroy'])->name('destroy');

        // My leaves - Employees only
        Route::get('/my-leaves', [LeaveController::class, 'myLeaves'])->name('my');

        // Calendar - Admin & Manager only
        Route::middleware(['role:admin,manager'])->get('/calendar', [LeaveController::class, 'calendar'])->name('calendar');

        // Approve/Reject - Admin & Manager only
        Route::middleware(['role:admin,manager'])->group(function () {
            Route::post('/{id}/approve', [LeaveController::class, 'approve'])->name('approve');
            Route::post('/{id}/reject', [LeaveController::class, 'reject'])->name('reject');
        });

        // Cancel - Employees can cancel their own pending leaves
        Route::post('/{id}/cancel', [LeaveController::class, 'cancel'])->name('cancel');
    });

    // ==================== REPORT ROUTES ====================
    // Reports - Admin & Manager only (READ ONLY)
    Route::middleware(['role:admin,manager'])->prefix('reports')->name('reports.')->group(function () {
        Route::get('/attendance', [ReportController::class, 'attendance'])->name('attendance');
        Route::get('/employees', [ReportController::class, 'employees'])->name('employees');
        Route::get('/leaves', [ReportController::class, 'leaves'])->name('leaves');
        Route::get('/analytics', [ReportController::class, 'analytics'])->name('analytics');

        // Export routes
        Route::get('/export/attendance', [ReportController::class, 'exportAttendance'])->name('export.attendance');
        Route::get('/export/employees', [ReportController::class, 'exportEmployees'])->name('export.employees');
        Route::get('/export/leaves', [ReportController::class, 'exportLeaves'])->name('export.leaves');
    });

    // ==================== ADMIN ROUTES ====================
    // Admin Panel - Admin only
    Route::middleware(['role:admin'])->prefix('admin')->name('admin.')->group(function () {
        // Dashboard
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

        // User Management
        Route::get('/users', [AdminController::class, 'users'])->name('users');
        Route::post('/users', [AdminController::class, 'createUser'])->name('users.create');
        Route::post('/users/{id}/reset-password', [AdminController::class, 'resetPassword'])->name('users.reset');

        // System Logs
        Route::get('/logs', [AdminController::class, 'logs'])->name('logs');

        // Backup & Restore
        Route::get('/backup', [AdminController::class, 'backup'])->name('backup');
        Route::post('/backup', [AdminController::class, 'createBackup'])->name('backup.create');
        Route::post('/backup/{id}/restore', [AdminController::class, 'restoreBackup'])->name('backup.restore');

        // System Settings
        Route::get('/settings', [AdminController::class, 'systemSettings'])->name('settings');
        Route::put('/settings', [AdminController::class, 'updateSystemSettings'])->name('settings.update');

        // Bulk Operations
        Route::post('/employees/bulk-delete', [AdminController::class, 'bulkDelete'])->name('employees.bulk.delete');
        Route::post('/employees/bulk-status', [AdminController::class, 'bulkStatus'])->name('employees.bulk.status');
        Route::post('/attendance/bulk-entry', [AdminController::class, 'bulkEntry'])->name('attendance.bulk.entry');
        Route::post('/leaves/bulk-approve', [AdminController::class, 'bulkApprove'])->name('leaves.bulk.approve');
    });
});

// ==================== FALLBACK ROUTES ====================
Route::fallback(function () {
    return redirect()->route('dashboard')->with('error', 'Page not found');
});
