<?php

namespace App\Http\Controllers;

use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use PDF; // Pastikan package PDF sudah terinstall (contoh: barryvdh/laravel-dompdf)

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
    public function attendance(Request $request)
    {
        try {
            // Ambil bulan dan tahun dari request, default: bulan & tahun sekarang
            $selectedMonth = (int) $request->get('month', now()->month);
            $selectedYear  = (int) $request->get('year', now()->year);

            $yearMonth = sprintf('%04d-%02d', $selectedYear, $selectedMonth); // Format: 2026-01

            // Ambil data absensi untuk bulan tersebut dari Firebase
            $rawAttendance = $this->firebase->getAttendanceByMonth($yearMonth); // method ini sudah ada di FirebaseService

            // Ambil semua karyawan perusahaan
            $employees = $this->firebase->getCompanyEmployees();

            // Proses data untuk laporan
            $attendanceReport = [];
            $summary = [
                'totalEmployees' => count($employees),
                'present'        => 0,
                'onLeave'        => 0,
                'absent'         => 0,
                'late'           => 0,
            ];

            foreach ($employees as $empId => $employee) {
                $record = [
                    'id'         => $empId,
                    'name'       => $employee['name'] ?? 'Unknown',
                    'department' => $employee['department'] ?? '-',
                    'present'    => 0,
                    'on_leave'   => 0,
                    'absent'     => 0,
                    'late'       => 0,
                    'overtime'   => 0,
                ];

                $empAttendance = $rawAttendance ?? [];

                foreach ($empAttendance as $date => $dayData) {
                    if (isset($dayData[$empId])) {
                        $status = $dayData[$empId]['status'] ?? 'absent';

                        switch ($status) {
                            case 'present':
                                $record['present']++;
                                $summary['present']++;
                                $record['overtime'] += $dayData[$empId]['overtime'] ?? 0;
                                break;
                            case 'late':
                                $record['late']++;
                                $summary['late']++;
                                break;
                            case 'leave':
                            case 'sick':
                            case 'on_leave':
                                $record['on_leave']++;
                                $summary['onLeave']++;
                                break;
                            default:
                                $record['absent']++;
                                $summary['absent']++;
                        }
                    } else {
                        // Jika tidak ada record di hari itu → dianggap absent
                        $record['absent']++;
                        $summary['absent']++;
                    }
                }

                $attendanceReport[] = $record;
            }

            // Urutkan berdasarkan nama karyawan
            usort($attendanceReport, fn($a, $b) => strcmp($a['name'], $b['name']));

            // Export PDF jika diminta
            if ($request->get('export') === 'pdf') {
                $pdf = PDF::loadView('reports.attendance-pdf', [
                    'attendanceReport' => $attendanceReport,
                    'summary'          => $summary,
                    'monthName'        => Carbon::create()->month($selectedMonth)->format('F'),
                    'year'             => $selectedYear,
                ]);
                return $pdf->download("laporan-absensi-{$selectedYear}-{$selectedMonth}.pdf");
            }

            return view('reports.attendance', compact(
                'attendanceReport',
                'summary',
                'selectedMonth',
                'selectedYear'
            ));
        } catch (\Exception $e) {
            \Log::error('Report Attendance Error: ' . $e->getMessage());

            return view('reports.attendance', [
                'attendanceReport' => [],
                'summary'          => ['totalEmployees' => 0, 'present' => 0, 'onLeave' => 0, 'absent' => 0, 'late' => 0],
                'selectedMonth'    => now()->month,
                'selectedYear'     => now()->year,
            ])->with('error', 'Gagal memuat laporan absensi.');
        }
    }

    /**
     * Laporan Data Karyawan
     */
    public function employees(Request $request)
    {
        try {
            // Ambil semua karyawan perusahaan
            $rawEmployees = $this->firebase->getCompanyEmployees();

            // Konversi ke Collection agar lebih mudah diolah
            $employeesCollection = collect($rawEmployees);

            // Filter departemen
            $department = $request->get('department');
            if ($department) {
                $employeesCollection = $employeesCollection->where('department', $department);
            }

            // Filter status
            $status = $request->get('status');
            if ($status) {
                $employeesCollection = $employeesCollection->where('status', $status);
            }

            // Konversi kembali ke array indexed (karena view mengharapkan array biasa dengan key numerik)
            $employees = $employeesCollection->values()->all();

            // Daftar departemen unik untuk dropdown filter
            $departments = collect($rawEmployees)
                ->pluck('department')
                ->filter() // hilangkan null
                ->unique()
                ->sort()
                ->values()
                ->all();

            // Statistik summary
            $summary = [
                'total'     => count($employees),
                'active'    => $employeesCollection->where('status', 'active')->count(),
                'inactive'  => $employeesCollection->where('status', '!=', 'active')->count(),
                'on_leave'  => 0, // bisa ditambah nanti dari data cuti jika diperlukan
            ];

            // Group by department untuk statistik (jika ingin ditampilkan di view)
            $byDepartment = $employeesCollection
                ->groupBy(fn($emp) => $emp['department'] ?? 'Tidak Ada Departemen')
                ->map->count()
                ->sortDesc()
                ->all();

            // Export PDF (jika diminta)
            if ($request->get('export') === 'pdf') {
                // Pastikan Anda sudah install barryvdh/laravel-dompdf
                $pdf = \PDF::loadView('reports.employees-pdf', [
                    'employees'     => $employees,
                    'summary'       => $summary,
                    'byDepartment'  => $byDepartment,
                    'filteredDept'  => $department,
                    'filteredStatus' => $status,
                    'generatedAt'   => now()->format('d F Y H:i')
                ]);

                return $pdf->download('Laporan_Karyawan_' . now()->format('Y-m-d') . '.pdf');
            }

            // Export Excel (jika Anda punya route ini)
            if ($request->get('export') === 'excel') {
                return Excel::download(new EmployeesExport($employees), 'Laporan_Karyawan_' . now()->format('Y-m-d') . '.xlsx');
            }

            // Tampilkan view normal
            return view('reports.employees', compact(
                'employees',
                'departments',     // untuk dropdown filter
                'summary',
                'byDepartment',
                'department',      // nilai filter saat ini
                'status'           // nilai filter saat ini
            ));
        } catch (\Exception $e) {
            \Log::error('Error Report Employees: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Gagal memuat laporan karyawan. Silakan coba lagi.');
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
