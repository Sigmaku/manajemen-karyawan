<?php

namespace App\Http\Controllers;

use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

class ReportController extends Controller
{
    protected $firebase;

    public function __construct(FirebaseService $firebase)
    {
        $this->firebase = $firebase;
    }

    /**
     * Laporan Absensi Bulanan - SIMPLE VERSION
     */
    public function attendance(Request $request)
    {
        $month = $request->get('month', date('Y-m'));
        $department = $request->get('department');
        $employeeId = $request->get('employee_id');

        // Ambil data
        $employees = $this->firebase->getCompanyEmployees();
        $attendanceData = $this->firebase->getAttendanceByMonth($month);
        $allLeavesData = $this->firebase->getAllLeaves() ?? [];

        // Filter employees
        if ($department) {
            $employees = array_filter($employees, fn($emp) => ($emp['department'] ?? '') === $department);
        }

        if ($employeeId) {
            $employees = array_filter($employees, fn($emp) => ($emp['id'] ?? '') == $employeeId);
        }

        $reportData = [];

        foreach ($employees as $empId => $emp) {
            // 1. Data cuti approved untuk employee ini
            $leaveDates = $this->getEmployeeLeaveDates($empId, $allLeavesData);

            // 2. Data attendance untuk employee ini
            $attendanceDates = $this->getEmployeeAttendanceDates($empId, $attendanceData);

            // 3. Generate semua hari kerja dalam bulan
            $workingDays = $this->generateWorkingDays($month);

            // 4. Urutkan working days dari yang terbaru ke terlama
            rsort($workingDays);

            $details = [];
            $stats = [
                'present' => 0,
                'late' => 0,
                'leave' => 0,
                'absent' => 0,
            ];

            // 5. VARIABLE TRACKING: Simpan status terakhir yang diketahui
            $foundRecentStatus = false;

            // 6. Loop dari hari TERBARU ke TERLAMA
            foreach ($workingDays as $date) {
                $carbonDate = Carbon::parse($date);

                // Tentukan status untuk hari ini
                $status = $this->determineDayStatus(
                    $date,
                    $empId,
                    $attendanceDates,
                    $leaveDates
                );

                // Jika kita sudah menemukan status sebelumnya (present/late/leave)
                // DAN hari ini statusnya belum diketahui (unknown)
                // MAKA hari ini adalah ABSENT
                if ($foundRecentStatus && $status['type'] === 'unknown') {
                    $status['type'] = 'absent';
                    $status['reason'] = 'Tidak hadir';
                }

                // Jika hari ini statusnya diketahui (bukan unknown), update flag
                if ($status['type'] !== 'unknown') {
                    $foundRecentStatus = true;
                }

                // Hitung stats
                if (isset($stats[$status['type']])) {
                    $stats[$status['type']]++;
                }

                // Simpan detail
                $details[] = [
                    'date' => $date,
                    'status' => ucfirst($status['type']),
                    'reason' => $status['reason'],
                    'clock_in' => $status['clock_in'],
                    'clock_out' => $status['clock_out'],
                ];
            }

            // 7. Urutkan detail dari tanggal terlama ke terbaru untuk tampilan
            usort($details, function($a, $b) {
                return strcmp($a['date'], $b['date']);
            });

            // 8. Hitung attendance rate
            $totalWorkingDays = count($workingDays);
            $attendedDays = $stats['present'] + $stats['late'];
            $attendanceRate = $totalWorkingDays > 0 ? round(($attendedDays / $totalWorkingDays) * 100, 2) : 0;

            $reportData[$empId] = [
                'employee' => [
                    'name' => $emp['name'] ?? 'Unknown',
                    'department' => $emp['department'] ?? '-',
                    'id' => $empId,
                ],
                'stats' => $stats,
                'total_working_days' => $totalWorkingDays,
                'attendance_rate' => $attendanceRate,
                'details' => $details,
            ];
        }

        // Urutkan berdasarkan nama
        uasort($reportData, fn($a, $b) => $a['employee']['name'] <=> $b['employee']['name']);

        // Export PDF
        if ($request->get('export') == 'pdf') {
            $pdf = PDF::loadView('reports.attendance-pdf', compact('reportData', 'month'));
            return $pdf->download("attendance-report-{$month}.pdf");
        }

        return view('reports.attendance', compact('reportData', 'month', 'department', 'employeeId'));
    }

    /**
     * Tentukan status untuk satu hari
     */
    private function determineDayStatus($date, $empId, $attendanceDates, $leaveDates)
    {
        // 1. Cek apakah tanggal ini cuti
        if (in_array($date, $leaveDates)) {
            return [
                'type' => 'leave',
                'reason' => 'Cuti',
                'clock_in' => '-',
                'clock_out' => '-',
            ];
        }

        // 2. Cek apakah ada attendance data
        if (isset($attendanceDates[$date])) {
            $attendance = $attendanceDates[$date];
            $clockIn = $attendance['checkIn'] ?? $attendance['clock_in'] ?? null;
            $clockOut = $attendance['checkOut'] ?? $attendance['clock_out'] ?? null;

            $attendanceStatus = strtolower($attendance['status'] ?? 'present');

            // Jika di attendance sudah ada status sakit/izin
            if ($attendanceStatus !== 'present') {
                return [
                    'type' => 'leave',
                    'reason' => ucfirst($attendanceStatus) . ': ' . ($attendance['notes'] ?? ''),
                    'clock_in' => $clockIn ?? '-',
                    'clock_out' => $clockOut ?? '-',
                ];
            }

            // Jika present, cek apakah telat
            if ($clockIn && $this->isLate($clockIn)) {
                return [
                    'type' => 'late',
                    'reason' => $attendance['notes'] ?? 'Telat',
                    'clock_in' => $clockIn,
                    'clock_out' => $clockOut ?? '-',
                ];
            }

            return [
                'type' => 'present',
                'reason' => $attendance['notes'] ?? 'Hadir',
                'clock_in' => $clockIn ?? '-',
                'clock_out' => $clockOut ?? '-',
            ];
        }

        // 3. Default: unknown (akan diproses nanti)
        return [
            'type' => 'unknown',
            'reason' => 'Belum ada data',
            'clock_in' => '-',
            'clock_out' => '-',
        ];
    }

    /**
     * Helper: Ambil semua tanggal cuti untuk employee
     */
    private function getEmployeeLeaveDates($empId, $allLeavesData)
    {
        $leaveDates = [];

        foreach ($allLeavesData as $leave) {
            $leaveEmpId = $leave['employeeId'] ?? '';
            $leaveStatus = $leave['status'] ?? 'pending';

            if ($leaveEmpId === $empId && $leaveStatus === 'approved') {
                $startDate = $leave['startDate'] ?? null;
                $endDate = $leave['endDate'] ?? null;

                if ($startDate && $endDate) {
                    $start = Carbon::parse($startDate);
                    $end = Carbon::parse($endDate);

                    while ($start->lte($end)) {
                        // Hanya hari kerja (Senin-Jumat)
                        if ($start->dayOfWeek >= Carbon::MONDAY && $start->dayOfWeek <= Carbon::FRIDAY) {
                            $leaveDates[] = $start->format('Y-m-d');
                        }
                        $start->addDay();
                    }
                }
            }
        }

        return array_unique($leaveDates);
    }

    /**
     * Helper: Ambil semua tanggal attendance untuk employee
     */
    private function getEmployeeAttendanceDates($empId, $attendanceData)
    {
        $attendanceDates = [];

        foreach ($attendanceData as $date => $dayData) {
            if (isset($dayData[$empId])) {
                $attendanceDates[$date] = $dayData[$empId];
            }
        }

        return $attendanceDates;
    }

    /**
     * Generate HANYA hari kerja (Senin-Jumat) dalam bulan
     */
    private function generateWorkingDays($yearMonth)
    {
        $workingDays = [];
        $date = Carbon::parse($yearMonth . '-01');
        $endDate = $date->copy()->endOfMonth();

        while ($date->lte($endDate)) {
            if ($date->dayOfWeek >= Carbon::MONDAY && $date->dayOfWeek <= Carbon::FRIDAY) {
                $workingDays[] = $date->format('Y-m-d');
            }
            $date->addDay();
        }

        return $workingDays;
    }

    /**
     * Cek apakah telat
     */
    private function isLate($checkInTime)
    {
        try {
            $checkIn = Carbon::parse($checkInTime);
            return $checkIn->format('H:i') > '08:15';
        } catch (\Exception $e) {
            return false;
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
            Log::error('Analytics Report Error: ' . $e->getMessage());
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
