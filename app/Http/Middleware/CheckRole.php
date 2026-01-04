<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\FirebaseService;

class CheckRole
{
    protected $firebase;

    public function __construct(FirebaseService $firebase)
    {
        $this->firebase = $firebase;
    }

    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = session('user');

        if (!$user) {
            return redirect()->route('login')->with('error', 'Please login first.');
        }

        // Check role
        if (!in_array($user['role'], $roles)) {
            return redirect()->route('dashboard')
                ->with('error', 'Access denied. You do not have permission.');
        }

        // Additional checks for employee
        if ($user['role'] === 'employee') {
            // Employee can only view their own data
            $employeeId = $user['employee_id'] ?? null;

            // Check if trying to access other employee's data
            if ($request->route('employee') || $request->route('id')) {
                $requestedId = $request->route('employee') ?? $request->route('id');

                // If employee trying to access other employee's data
                if ($requestedId && $requestedId !== $employeeId) {
                    return redirect()->route('dashboard')
                        ->with('error', 'You can only view your own data.');
                }
            }

            // Check for leaves access
            if ($request->route('id') && str_contains($request->path(), 'leaves')) {
                $leaveId = $request->route('id');
                $leave = $this->firebase->getLeave($leaveId);

                if ($leave && ($leave['employeeId'] ?? '') !== $employeeId) {
                    return redirect()->route('leaves.my')
                        ->with('error', 'You can only view your own leaves.');
                }
            }
        }

        return $next($request);
    }
}
