<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\DashboardController;

Route::prefix('v1')->group(function () {
    // Employee API
    Route::get('/employees', [EmployeeController::class, 'apiIndex']);
    Route::get('/employees/{id}', [EmployeeController::class, 'apiShow']);
    Route::get('/employees/{id}/attendance', [EmployeeController::class, 'apiAttendance']);

    // Attendance API
    Route::get('/attendance/today', [AttendanceController::class, 'apiToday']);
    Route::get('/attendance/monthly/{month?}', [AttendanceController::class, 'apiMonthly']);
    Route::post('/attendance/check-in', [AttendanceController::class, 'apiMobileCheckIn']);
    Route::post('/attendance/check-out', [AttendanceController::class, 'apiMobileCheckOut']);

    // Dashboard API
    Route::get('/stats', [DashboardController::class, 'apiStats']);
    Route::get('/activities', [DashboardController::class, 'apiActivities']);

});
