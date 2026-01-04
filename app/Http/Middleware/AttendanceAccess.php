<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\FirebaseService;

class AttendanceAccess
{
    protected $firebase;

    public function __construct(FirebaseService $firebase)
    {
        $this->firebase = $firebase;
    }

    public function handle(Request $request, Closure $next)
    {
        $user = session('user');
        $role = $user['role'] ?? 'employee';

        // Admin can do everything
        if ($role === 'admin') {
            return $next($request);
        }

        // Manager can only VIEW attendance
        if ($role === 'manager') {
            // Allow GET requests (view only)
            if ($request->method() === 'GET') {
                return $next($request);
            }

            // Block POST/PUT/DELETE for manager
            if ($request->routeIs('attendance.check-in') || $request->routeIs('attendance.check-out')) {
                return redirect()->route('attendance.dashboard')
                    ->with('error', 'Managers cannot check in/out. Only view attendance.');
            }

            // Allow other GET routes
            return $next($request);
        }

        // Employee can only view their own attendance and check in/out for themselves
        if ($role === 'employee') {
            $employeeId = $user['employee_id'] ?? null;

            // For check-in/check-out
            if ($request->routeIs('attendance.check-in') || $request->routeIs('attendance.check-out')) {
                // Check if employee is trying to check in/out for themselves
                $requestEmployeeId = $request->employee_id ?? $request->input('employee_id');

                if (!$requestEmployeeId) {
                    return back()->with('error', 'Employee ID is required.');
                }

                if ($requestEmployeeId !== $employeeId) {
                    return back()->with('error', 'You can only record attendance for yourself.');
                }

                return $next($request);
            }

            // For viewing attendance (GET requests)
            if ($request->method() === 'GET') {
                // Check if trying to view other employee's attendance
                if ($request->get('employee_id') && $request->get('employee_id') !== $employeeId) {
                    return redirect()->route('attendance.dashboard')
                        ->with('error', 'You can only view your own attendance.');
                }

                // For single employee report route parameter
                if ($request->route('employeeId') && $request->route('employeeId') !== $employeeId) {
                    return redirect()->route('attendance.dashboard')
                        ->with('error', 'You can only view your own attendance report.');
                }

                return $next($request);
            }

            // Block other methods for employee
            return redirect()->route('dashboard')
                ->with('error', 'Access denied.');
        }

        return $next($request);
    }
}
