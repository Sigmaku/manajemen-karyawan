<?php

namespace App\Http\Controllers;

use App\Services\FirebaseService;
use Illuminate\Http\Request;
use PDF;

class ReportController extends Controller
{
    protected $firebase;

    public function __construct(FirebaseService $firebase)
    {
        $this->firebase = $firebase;
    }

    // Attendance report - VERSI IMPROVED
    public function attendance(Request $request)
    {
        $month = $request->get('month', date('Y-m')); // contoh: 2026-01
        $department = $request->get('department');
        $employeeId = $request->get('employee_id'); // filter per karyawan

        $db = $this->firebase->getDatabase();

        // Ambil data employees
        $employeesRef = $db->getReference('employees')->getValue() ?: [];

        // Ambil data attendance bulan tersebut
        $attendanceRef = $db->getReference("attendances/$month")->getValue() ?: [];

        // Filter employees berdasarkan department
        $employees = $employeesRef;
        if ($department) {
            $employees = array_filter($employees, fn($emp) => ($emp['department'] ?? '') === $department);
        }

        $reportData = [];

        foreach ($employees as $empId => $emp) {
            // Kalau ada filter employee_id, skip yang bukan target
            if ($employeeId && $empId !== $employeeId) {
                continue;
            }

            $days = $attendanceRef[$empId] ?? [];
            $details = []; // untuk detail harian di view
            $stats = [
                'present' => 0,
                'late' => 0,
                'leave' => 0,
                'sick' => 0,
                'permission' => 0,
                'absent' => 0,
            ];

            foreach ($days as $date => $day) {
                $status = $day['status'] ?? 'absent'; // fallback absent
                $stats[$status] = ($stats[$status] ?? 0) + 1;

                $details[] = [
                    'date' => $date, // format YYYY-MM-DD
                    'status' => ucfirst(str_replace('_', ' ', $status)), // Present, Late, Leave, dll
                    'reason' => $day['reason'] ?? '-',
                    'clock_in' => $day['clock_in'] ?? '-',
                    'clock_out' => $day['clock_out'] ?? '-',
                ];
            }

            $totalDays = count($days);
            $attendedDays = ($stats['present'] ?? 0) + ($stats['late'] ?? 0); // hadir + telat = masuk
            $attendanceRate = $totalDays > 0 ? round(($attendedDays / $totalDays) * 100, 2) : 0;

            $reportData[$empId] = [
                'employee' => $emp,
                'stats' => $stats,
                'total_days' => $totalDays,
                'attendance_rate' => $attendanceRate,
                'details' => $details, // ini yang dipakai di collapse table
            ];
        }

        // Urutkan reportData berdasarkan nama karyawan (opsional, biar rapi)
        uasort($reportData, fn($a, $b) => $a['employee']['name'] <=> $b['employee']['name']);

        // Export PDF
        if ($request->get('export') == 'pdf') {
            $pdf = PDF::loadView('reports.attendance-pdf', compact('reportData', 'month', 'department'));
            return $pdf->download("attendance-report-{$month}.pdf");
        }

        // Return view
        return view('reports.attendance', compact('reportData', 'month', 'department', 'employeeId'));
    }

    // Employee report
    public function employees(Request $request)
    {
        $db = $this->firebase->getDatabase();
        $employees = $db->getReference('employees')->getValue() ?: [];

        // Department filter
        $department = $request->get('department');
        if ($department) {
            $employees = array_filter($employees, function ($emp) use ($department) {
                return $emp['department'] == $department;
            });
        }

        // Status filter
        $status = $request->get('status');
        if ($status) {
            $employees = array_filter($employees, function ($emp) use ($status) {
                return $emp['status'] == $status;
            });
        }

        // Statistics
        $total = count($employees);
        $active = count(array_filter($employees, function ($emp) {
            return $emp['status'] == 'active';
        }));
        $inactive = $total - $active;

        // Group by department
        $byDepartment = [];
        foreach ($employees as $emp) {
            $dept = $emp['department'];
            if (!isset($byDepartment[$dept])) {
                $byDepartment[$dept] = 0;
            }
            $byDepartment[$dept]++;
        }

        if ($request->get('export') == 'pdf') {
            $pdf = PDF::loadView('reports.employees-pdf', compact('employees', 'total', 'active', 'inactive', 'byDepartment'));
            return $pdf->download("employee-report-" . date('Y-m-d') . ".pdf");
        }

        return view('reports.employees', compact('employees', 'total', 'active', 'inactive', 'byDepartment'));
    }

    // Leaves report
    public function leaves(Request $request)
    {
        $db = $this->firebase->getDatabase();
        $leaves = $db->getReference('leaves')->getValue() ?: [];
        $employees = $db->getReference('employees')->getValue() ?: [];

        // Date range filter
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        if ($startDate && $endDate) {
            $leaves = array_filter($leaves, function ($leave) use ($startDate, $endDate) {
                return $leave['start_date'] >= $startDate && $leave['end_date'] <= $endDate;
            });
        }

        // Status filter
        $status = $request->get('status');
        if ($status) {
            $leaves = array_filter($leaves, function ($leave) use ($status) {
                return $leave['status'] == $status;
            });
        }

        // Calculate statistics
        $stats = [
            'total' => count($leaves),
            'approved' => count(array_filter($leaves, function ($leave) {
                return $leave['status'] == 'approved';
            })),
            'pending' => count(array_filter($leaves, function ($leave) {
                return $leave['status'] == 'pending';
            })),
            'rejected' => count(array_filter($leaves, function ($leave) {
                return $leave['status'] == 'rejected';
            }))
        ];

        // Group by leave type
        $byType = [];
        foreach ($leaves as $leave) {
            $type = $leave['leave_type'];
            if (!isset($byType[$type])) {
                $byType[$type] = 0;
            }
            $byType[$type]++;
        }

        if ($request->get('export') == 'pdf') {
            $pdf = PDF::loadView('reports.leaves-pdf', compact('leaves', 'employees', 'stats', 'byType'));
            return $pdf->download("leaves-report-" . date('Y-m-d') . ".pdf");
        }

        return view('reports.leaves', compact('leaves', 'employees', 'stats', 'byType'));
    }

    // Dashboard analytics
    public function analytics()
    {
        $db = $this->firebase->getDatabase();

        // Monthly attendance trend
        $months = [];
        $attendanceData = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-$i months"));
            $months[] = date('M Y', strtotime($month));

            $attendance = $db->getReference("attendances/$month")->getValue() ?: [];
            $totalDays = 0;
            $presentDays = 0;

            foreach ($attendance as $empDays) {
                foreach ($empDays as $day) {
                    $totalDays++;
                    if ($day['status'] == 'present') {
                        $presentDays++;
                    }
                }
            }

            $attendanceData[] = $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 2) : 0;
        }

        // Department distribution
        $employees = $db->getReference('employees')->getValue() ?: [];
        $deptDistribution = [];
        foreach ($employees as $emp) {
            $dept = $emp['department'];
            if (!isset($deptDistribution[$dept])) {
                $deptDistribution[$dept] = 0;
            }
            $deptDistribution[$dept]++;
        }

        // Leave type distribution
        $leaves = $db->getReference('leaves')->getValue() ?: [];
        $leaveDistribution = [];
        foreach ($leaves as $leave) {
            $type = $leave['leave_type'];
            if (!isset($leaveDistribution[$type])) {
                $leaveDistribution[$type] = 0;
            }
            $leaveDistribution[$type]++;
        }

        return view('reports.analytics', compact(
            'months',
            'attendanceData',
            'deptDistribution',
            'leaveDistribution'
        ));
    }
}
