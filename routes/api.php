<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeaveController;

Route::prefix('v1')->group(function () {
    // Dashboard API
    Route::get('/stats', [DashboardController::class, 'apiStats']);

    // Employee API
    Route::get('/employees', [EmployeeController::class, 'apiIndex']);
    Route::get('/employees/{id}', [EmployeeController::class, 'apiShow']);
    Route::get('/employees/{id}/attendance', [EmployeeController::class, 'apiAttendance']);

    // Attendance API
    Route::get('/attendance/today', [AttendanceController::class, 'apiToday']);
    Route::get('/attendance/employee/{employeeId}', [AttendanceController::class, 'apiEmployeeAttendance']);

    // Realtime employee status
    Route::get('/employees/{employeeId}/status', [EmployeeController::class, 'apiStatus']);

    // Leave API
    Route::get('/leaves/employee/{employeeId}', [LeaveController::class, 'apiEmployeeLeaves']);
});
