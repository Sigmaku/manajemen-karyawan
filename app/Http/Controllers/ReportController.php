<?php

namespace App\Http\Controllers;

use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    protected $firebase;

    public function __construct(FirebaseService $firebase)
    {
        $this->firebase = $firebase;
    }

    /**
     * Laporan Absensi Bulanan
     */
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

    /**
     * Laporan Data Karyawan
     */
    public function employees(Request $request)
    {
        try {
            $employees = $this->firebase->getCompanyEmployees();

            // Filter departemen
            $department = $request->get('department');
            if ($department) {
                $employees = array_filter($employees, fn($emp) => ($emp['department'] ?? '') === $department);
            }

            // Filter status
            $status = $request->get('status');
            if ($status) {
                $employees = array_filter($employees, fn($emp) => ($emp['status'] ?? 'active') === $status);
            }

            // Statistik
            $total = count($employees);
            $active = count(array_filter($employees, fn($emp) => ($emp['status'] ?? 'active') === 'active'));
            $inactive = $total - $active;

            // Group by department
            $byDepartment = [];
            foreach ($employees as $emp) {
                $dept = $emp['department'] ?? 'Tidak Ada Departemen';
                $byDepartment[$dept] = ($byDepartment[$dept] ?? 0) + 1;
            }

            if ($request->get('export') === 'pdf') {
                $pdf = PDF::loadView('reports.employees-pdf', compact(
                    'employees',
                    'total',
                    'active',
                    'inactive',
                    'byDepartment'
                ));
                return $pdf->download('laporan-karyawan-' . now()->format('Y-m-d') . '.pdf');
            }

            return view('reports.employees', compact(
                'employees',
                'total',
                'active',
                'inactive',
                'byDepartment',
                'department',
                'status'
            ));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memuat laporan karyawan.');
        }
    }

    /**
     * Laporan Pengajuan Cuti
     */
    public function leaves(Request $request)
    {
        try {
            $allLeaves = $this->firebase->getAllLeaves(); // array: leaveId => data
            $employees = $this->firebase->getCompanyEmployees();

            $leaves = $allLeaves ?? [];

            // Filter tanggal
            $startDate = $request->get('start_date');
            $endDate   = $request->get('end_date');

            if ($startDate && $endDate) {
                $leaves = array_filter($leaves, function ($leave) use ($startDate, $endDate) {
                    return ($leave['startDate'] ?? $leave['start_date']) >= $startDate &&
                        ($leave['endDate'] ?? $leave['end_date']) <= $endDate;
                });
            }

            // Filter status
            $status = $request->get('status');
            if ($status && $status !== 'all') {
                $leaves = array_filter($leaves, fn($leave) => ($leave['status'] ?? 'pending') === $status);
            }

            // Statistik
            $stats = [
                'total'    => count($leaves),
                'approved' => count(array_filter($leaves, fn($l) => $l['status'] ?? '' === 'approved')),
                'pending'  => count(array_filter($leaves, fn($l) => $l['status'] ?? '' === 'pending')),
                'rejected' => count(array_filter($leaves, fn($l) => $l['status'] ?? '' === 'rejected')),
            ];

            // Group by jenis cuti
            $byType = [];
            foreach ($leaves as $leave) {
                $type = $leave['type'] ?? $leave['leave_type'] ?? 'unknown';
                $byType[$type] = ($byType[$type] ?? 0) + 1;
            }

            if ($request->get('export') === 'pdf') {
                $pdf = PDF::loadView('reports.leaves-pdf', compact(
                    'leaves',
                    'employees',
                    'stats',
                    'byType'
                ));
                return $pdf->download('laporan-cuti-' . now()->format('Y-m-d') . '.pdf');
            }

            return view('reports.leaves', compact(
                'leaves',
                'employees',
                'stats',
                'byType',
                'startDate',
                'endDate',
                'status'
            ));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memuat laporan cuti.');
        }
    }

    /**
     * Dashboard Analytics (Grafik & Tren)
     */
    public function analytics()
    {
        try {
            // Tren absensi 6 bulan terakhir
            $months = [];
            $attendanceRates = [];

            for ($i = 5; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $yearMonth = $date->format('Y-m');
                $months[] = $date->format('M Y');

                $rawAttendance = $this->firebase->getAttendanceByMonth($yearMonth);
                $totalDays = 0;
                $presentDays = 0;

                if ($rawAttendance) {
                    foreach ($rawAttendance as $dayData) {
                        foreach ($dayData as $empData) {
                            $totalDays++;
                            if (($empData['status'] ?? 'absent') === 'present') {
                                $presentDays++;
                            }
                        }
                    }
                }

                $rate = $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 2) : 0;
                $attendanceRates[] = $rate;
            }

            // Distribusi departemen
            $employees = $this->firebase->getCompanyEmployees();
            $deptDistribution = [];
            foreach ($employees as $emp) {
                $dept = $emp['department'] ?? 'Lainnya';
                $deptDistribution[$dept] = ($deptDistribution[$dept] ?? 0) + 1;
            }

            // Distribusi jenis cuti
            $leaves = $this->firebase->getAllLeaves();
            $leaveDistribution = [];
            foreach ($leaves as $leave) {
                $type = $leave['type'] ?? 'unknown';
                $leaveDistribution[$type] = ($leaveDistribution[$type] ?? 0) + 1;
            }

            return view('reports.analytics', compact(
                'months',
                'attendanceRates',
                'deptDistribution',
                'leaveDistribution'
            ));
        } catch (\Exception $e) {
            \Log::error('Analytics Report Error: ' . $e->getMessage());
            return view('reports.analytics', [
                'months'            => [],
                'attendanceRates'   => [],
                'deptDistribution'  => [],
                'leaveDistribution' => [],
            ])->with('error', 'Gagal memuat data analytics.');
        }
    }

    // Export methods bisa ditambahkan terpisah jika perlu
}
