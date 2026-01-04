<?php

namespace App\Http\Controllers;

use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    protected $firebase;

    public function __construct(FirebaseService $firebase)
    {
        $this->firebase = $firebase;
    }

    // ==================== WEB METHOD ====================

    public function index()
    {
        $user = session('user');
        $role = $user['role'] ?? 'employee';
        $employeeId = $user['employee_id'] ?? null;

        // Get dashboard stats
        $stats = $this->firebase->getDashboardStats();
        $employees = $this->firebase->getCompanyEmployees();
        $todayAttendance = $this->firebase->getTodayAttendance();

        // Format attendance list
        $attendanceList = [];
        foreach ($todayAttendance as $empId => $record) {
            if (isset($employees[$empId])) {
                $attendanceList[] = [
                    'employee' => $employees[$empId],
                    'attendance' => $record,
                    'employee_id' => $empId
                ];
            }
        }

        // Different views based on role
        if ($role === 'admin') {
            // Count employees by department for admin
            $departmentStats = [];
            foreach ($employees as $employee) {
                $dept = $employee['department'] ?? 'Other';
                if (!isset($departmentStats[$dept])) {
                    $departmentStats[$dept] = 0;
                }
                $departmentStats[$dept]++;
            }

            // Get recent leaves for admin
            $recentLeaves = $this->firebase->getAllLeaves();
            $recentLeaves = array_slice($recentLeaves, 0, 5, true);

            return view('admin.dashboard', compact('stats', 'attendanceList', 'employees', 'departmentStats', 'recentLeaves'));

        } elseif ($role === 'manager') {
            // Get department stats for manager
            $departmentStats = [];
            foreach ($employees as $employee) {
                $dept = $employee['department'] ?? 'Other';
                if (!isset($departmentStats[$dept])) {
                    $departmentStats[$dept] = 0;
                }
                $departmentStats[$dept]++;
            }

            return view('manager.dashboard', compact('stats', 'attendanceList', 'employees', 'departmentStats'));

        } else {
            // Employee dashboard
            $employeeAttendance = [];
            $employeeLeaves = [];

            if ($employeeId) {
                // Get current month attendance
                $employeeAttendance = $this->firebase->getEmployeeAttendance($employeeId, date('Y-m'));
                $employeeLeaves = $this->firebase->getEmployeeLeaves($employeeId);
            }

            return view('employee.dashboard', compact('stats', 'employeeAttendance', 'employeeLeaves'));
        }
    }

    // ==================== API METHODS ====================

    public function apiStats(): JsonResponse
    {
        try {
            $stats = $this->firebase->getDashboardStats();

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Dashboard stats retrieved'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get dashboard stats',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function apiActivities(): JsonResponse
    {
        try {
            // Placeholder - implement if you have activities collection in Firebase
            $activities = [];

            return response()->json([
                'success' => true,
                'data' => array_values($activities),
                'message' => 'Recent activities retrieved'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get activities',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
