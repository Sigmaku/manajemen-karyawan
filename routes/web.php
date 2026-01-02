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

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Profile
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');

    // Employees
    Route::resource('employees', EmployeeController::class);

    // Attendance
    Route::prefix('attendance')->group(function () {
        Route::get('/dashboard', [AttendanceController::class, 'dashboard'])->name('attendance.dashboard');
        Route::post('/check-in', [AttendanceController::class, 'checkIn'])->name('attendance.check-in');
        Route::post('/check-out', [AttendanceController::class, 'checkOut'])->name('attendance.check-out');
        Route::get('/report', [AttendanceController::class, 'report'])->name('attendance.report');
        Route::get('/report/{employeeId}', [AttendanceController::class, 'employeeReport'])->name('attendance.report.employee');
        Route::get('/report/{employeeId}/{month}', [AttendanceController::class, 'employeeMonthlyReport'])->name('attendance.report.employee.monthly');
        Route::post('/manual', [AttendanceController::class, 'manualEntry'])->name('attendance.manual');
        Route::get('/history', [AttendanceController::class, 'history'])->name('attendance.history');
    });

    // Leaves
    Route::resource('leaves', LeaveController::class)->except(['update']);
    Route::prefix('leaves')->group(function () {
        Route::get('/calendar', [LeaveController::class, 'calendar'])->name('leaves.calendar');
        Route::get('/my-leaves', [LeaveController::class, 'myLeaves'])->name('leaves.my');
        Route::post('/{id}/approve', [LeaveController::class, 'approve'])->name('leaves.approve');
        Route::post('/{id}/reject', [LeaveController::class, 'reject'])->name('leaves.reject');
        Route::post('/{id}/cancel', [LeaveController::class, 'cancel'])->name('leaves.cancel');
    });

    // Reports
    Route::prefix('reports')->group(function () {
        Route::get('/attendance', [ReportController::class, 'attendance'])->name('reports.attendance');
        Route::get('/employees', [ReportController::class, 'employees'])->name('reports.employees');
        Route::get('/leaves', [ReportController::class, 'leaves'])->name('reports.leaves');
        Route::get('/analytics', [ReportController::class, 'analytics'])->name('reports.analytics');
        Route::get('/export/attendance', [ReportController::class, 'exportAttendance'])->name('reports.export.attendance');
        Route::get('/export/employees', [ReportController::class, 'exportEmployees'])->name('reports.export.employees');
    });

    // Admin Routes
    Route::prefix('admin')->name('admin.')->group(function () {
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
    Route::post('/employees/bulk-delete', [EmployeeController::class, 'bulkDelete'])->name('employees.bulk.delete');
    Route::post('/employees/bulk-status', [EmployeeController::class, 'bulkStatus'])->name('employees.bulk.status');
    Route::post('/attendance/bulk-entry', [AttendanceController::class, 'bulkEntry'])->name('attendance.bulk.entry');
    Route::post('/leaves/bulk-approve', [LeaveController::class, 'bulkApprove'])->name('leaves.bulk.approve');
});
});

// ==================== FALLBACK ROUTES ====================
Route::fallback(function () {
    return redirect()->route('dashboard')->with('error', 'Page not found');
});
