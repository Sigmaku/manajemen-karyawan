<?php

namespace App\Http\Controllers;

use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

class AttendanceController extends Controller
{
    protected $firebase;

    public function __construct(FirebaseService $firebase)
    {
        $this->firebase = $firebase;
    }

    // ==================== WEB METHODS ====================

    public function dashboard()
    {
        $user = session('user');
        $role = $user['role'] ?? 'employee';
        $employeeId = $user['employee_id'] ?? null;

        try {
            if ($role === 'employee') {
                // Employee can only see their own attendance
                $today = date('Y-m-d');
                $attendanceData = [];

                // Get today's attendance for this employee only
                $allAttendance = $this->firebase->getTodayAttendance();
                if (isset($allAttendance[$employeeId])) {
                    $attendanceData[$employeeId] = $allAttendance[$employeeId];
                }

                // Get employee data
                $employee = $this->firebase->getEmployee($employeeId);
                $allEmployees = $employee ? [$employeeId => $employee] : [];
            } else {
                // Admin/Manager can see all
                $today = date('Y-m-d');
                $attendanceData = $this->firebase->getTodayAttendance();
                $allEmployees = $this->firebase->getCompanyEmployees();
            }

            // Format for display
            $attendanceList = [];
            foreach ($attendanceData as $empId => $record) {
                if (isset($allEmployees[$empId])) {
                    $attendanceList[] = [
                        'employee' => $allEmployees[$empId],
                        'attendance' => $record,
                        'employee_id' => $empId
                    ];
                }
            }

            // Sort by check-in time (latest first)
            usort($attendanceList, function ($a, $b) {
                $timeA = $a['attendance']['checkIn'] ?? '00:00';
                $timeB = $b['attendance']['checkIn'] ?? '00:00';
                return strcmp($timeB, $timeA);
            });

            return view('attendance.dashboard', [
                'attendanceList' => $attendanceList,
                'allEmployees' => $allEmployees,
                'totalEmployees' => count($allEmployees),
                'presentCount' => count($attendanceList),
                'today' => $today,
                'role' => $role,
                'currentEmployeeId' => $employeeId
            ]);
        } catch (\Exception $e) {
            return view('attendance.dashboard', [
                'attendanceList' => [],
                'allEmployees' => [],
                'totalEmployees' => 0,
                'presentCount' => 0,
                'today' => date('Y-m-d'),
                'role' => $role,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function checkIn(Request $request)
    {
        $user = session('user');
        $role = $user['role'] ?? 'employee';
        $employeeId = $user['employee_id'] ?? null;

        // Only employees can check in/out
        if ($role !== 'employee') {
            return redirect()->route('attendance.dashboard')
                ->with('error', 'Only employees can check in/out');
        }

        if (!$employeeId) {
            return back()->with('error', 'Employee ID not found in session');
        }

        // Auto use logged in employee ID
        $request->merge(['employee_id' => $employeeId]);

        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|string',
            'location'    => 'required|string',
            'notes'       => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $employee = $this->firebase->getEmployee($employeeId);
            if (!$employee) {
                return back()->with('error', 'Employee not found');
            }

            // Check if already checked in today
            $todayAttendance = $this->firebase->getTodayAttendance();
            if (isset($todayAttendance[$employeeId])) {
                return back()->with('error', 'Already checked in today');
            }

            // =========================
            // Time Rules
            // =========================
            $today = date('Y-m-d');
            $checkInTimeStr = now()->format('H:i');

            $officeStart   = \Carbon\Carbon::parse($today . ' 08:00');
            $checkInCarbon = \Carbon\Carbon::parse($today . ' ' . $checkInTimeStr);

            $isLate = $checkInCarbon->gt($officeStart);
            $lateMinutes = $isLate ? $officeStart->diffInMinutes($checkInCarbon) : 0;

            $attendanceData = [
                'checkIn'     => $checkInTimeStr,
                'location'    => $request->location ?? 'Office',
                'notes'       => $request->notes ?? '',
                'isLate'      => $isLate,
                'lateMinutes' => $lateMinutes,                 // ✅ for dashboard display
                'status'      => $isLate ? 'late' : 'present', // ✅ for badge
                'checkedInAt' => now()->toISOString(),         // optional audit field
            ];

            $record = $this->firebase->recordCheckIn($employeeId, $attendanceData);

            return redirect()->route('attendance.dashboard')
                ->with('success', 'Check-in recorded successfully!')
                ->with('checkin_data', [
                    'employee_id'   => $employeeId,
                    'employee_name' => $employee['name'] ?? 'Unknown',
                    'check_in'      => $record['checkIn'] ?? $checkInTimeStr,
                    'location'      => $record['location'] ?? ($request->location ?? 'Office'),
                    'late'          => $record['isLate'] ?? $isLate,
                    'late_minutes'  => $record['lateMinutes'] ?? $lateMinutes,
                ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Check-in failed: ' . $e->getMessage());
        }
    }


    public function checkOut(Request $request)
    {
        $user = session('user');
        $role = $user['role'] ?? 'employee';
        $employeeId = $user['employee_id'] ?? null;

        // (Opsional tapi disarankan) hanya employee boleh checkout
        if ($role !== 'employee') {
            return response()->json([
                'success' => false,
                'message' => 'Only employees can check out'
            ], 403);
        }

        $today = date('Y-m-d');
        $now = now();

        $attendance = $this->firebase->getTodayAttendance();
        if (!isset($attendance[$employeeId]['checkIn'])) {
            return response()->json([
                'success' => false,
                'message' => 'No check-in found today'
            ], 400);
        }

        $checkInTime  = \Carbon\Carbon::parse($today . ' ' . $attendance[$employeeId]['checkIn']);
        $checkOutTime = $now;

        // Jam kantor
        $officeStart   = \Carbon\Carbon::parse($today . ' 08:00');
        $officeEnd     = \Carbon\Carbon::parse($today . ' 16:00');
        $overtimeStart = \Carbon\Carbon::parse($today . ' 18:00');

        // 1) TELAT (menit)
        $lateMinutes = 0;
        if ($checkInTime->greaterThan($officeStart)) {
            $lateMinutes = $officeStart->diffInMinutes($checkInTime);
        }

        // 2) JAM KERJA NORMAL (08:00 - 16:00)
        // Mulai kerja normal = max(checkIn, 08:00)
        $normalStart = $checkInTime->lessThan($officeStart) ? $officeStart : $checkInTime;
        // Selesai kerja normal = min(checkOut, 16:00)
        $normalEnd = $checkOutTime->greaterThan($officeEnd) ? $officeEnd : $checkOutTime;

        $normalWorkedMinutes = 0;
        if ($normalEnd->greaterThan($normalStart)) {
            $normalWorkedMinutes = $normalStart->diffInMinutes($normalEnd);
        }

        $hoursWorked = round($normalWorkedMinutes / 60, 2);

        // 3) LEMBUR (mulai 18:00)
        $overtimeMinutes = 0;
        if ($checkOutTime->greaterThan($overtimeStart)) {
            $overtimeMinutes = $overtimeStart->diffInMinutes($checkOutTime);
        }

        $overtime = round($overtimeMinutes / 60, 2);

        // 4) SIMPAN
        $this->firebase->getDatabase()
            ->getReference("attendances/{$this->firebase->getCompanyId()}/$today/$employeeId")
            ->update([
                'checkOut'        => $checkOutTime->format('H:i'),
                'hoursWorked'     => $hoursWorked,     // hanya jam normal (08-16)
                'overtime'        => $overtime,        // hanya jam lembur (>=18)
                'lateMinutes'     => $lateMinutes,
                'checkedOutAt'    => $checkOutTime->toISOString(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Check-out successful',
            'data' => [
                'hours_worked'  => $hoursWorked,
                'overtime'      => $overtime,
                'late_minutes'  => $lateMinutes
            ]
        ]);
    }

    public function report(Request $request)
    {
        $user = session('user');
        $role = $user['role'] ?? 'employee';
        $employeeId = $user['employee_id'] ?? null;

        $month = $request->get('month', date('Y-m'));

        try {
            if ($role === 'employee') {
                // Employee can only see their own report
                $attendance = $this->firebase->getEmployeeAttendance($employeeId, $month);
                $employee = $this->firebase->getEmployee($employeeId);

                if (!$employee) {
                    return redirect()->route('attendance.report')
                        ->with('error', 'Employee not found');
                }

                return view('attendance.report-single', compact('attendance', 'employee', 'month', 'role'));
            } else {
                // Admin/Manager can see all
                $requestedEmployeeId = $request->get('employee_id');
                $allEmployees = $this->firebase->getCompanyEmployees();

                if ($requestedEmployeeId) {
                    // Single employee report
                    $attendance = $this->firebase->getEmployeeAttendance($requestedEmployeeId, $month);
                    $employee = $this->firebase->getEmployee($requestedEmployeeId);

                    if (!$employee) {
                        return redirect()->route('attendance.report')
                            ->with('error', 'Employee not found');
                    }

                    return view('attendance.report-single', compact('attendance', 'employee', 'month', 'role'));
                } else {
                    // All employees report
                    $monthAttendance = $this->firebase->getAttendanceByMonth($month);

                    // Calculate statistics
                    $stats = [];
                    foreach ($allEmployees as $empId => $employee) {
                        $presentDays = 0;
                        $totalHours = 0;

                        foreach ($monthAttendance as $date => $dayAttendance) {
                            if (isset($dayAttendance[$empId])) {
                                $presentDays++;
                                $record = $dayAttendance[$empId];
                                if (isset($record['hoursWorked'])) {
                                    $totalHours += $record['hoursWorked'];
                                }
                            }
                        }

                        $stats[$empId] = [
                            'employee' => $employee,
                            'present_days' => $presentDays,
                            'total_hours' => $totalHours,
                            'attendance_rate' => count($monthAttendance) > 0
                                ? round(($presentDays / count($monthAttendance)) * 100, 2)
                                : 0
                        ];
                    }

                    return view('attendance.report-all', compact('stats', 'month', 'allEmployees', 'role'));
                }
            }
        } catch (\Exception $e) {
            return view('attendance.report-all', [
                'stats' => [],
                'month' => $month,
                'allEmployees' => [],
                'role' => $role,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function history(Request $request)
    {
        $user = session('user');
        $role = $user['role'] ?? 'employee';
        $employeeId = $user['employee_id'] ?? null;

        $month = $request->get('month', date('Y-m'));

        try {
            if ($role === 'employee') {
                // Employee can only see their own history
                $attendance = $this->firebase->getEmployeeAttendance($employeeId, $month);
                $employee = $this->firebase->getEmployee($employeeId);

                return view('attendance.history-single', compact('attendance', 'employee', 'month', 'role'));
            }

            // Admin/Manager can see filtered history
            $requestedEmployeeId = $request->get('employee_id');
            $allEmployees = $this->firebase->getCompanyEmployees();

            if ($requestedEmployeeId) {
                $attendance = $this->firebase->getEmployeeAttendance($requestedEmployeeId, $month);
                $employee = $this->firebase->getEmployee($requestedEmployeeId);

                return view('attendance.history-single', compact('attendance', 'employee', 'month', 'role'));
            }

            $monthAttendance = $this->firebase->getAttendanceByMonth($month);

            return view('attendance.history', compact('monthAttendance', 'allEmployees', 'month', 'role'));
        } catch (\Exception $e) {
            return view('attendance.history', [
                'monthAttendance' => [],
                'allEmployees' => [],
                'month' => $month,
                'role' => $role,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function manualEntry(Request $request)
    {
        $user = session('user');
        $role = $user['role'] ?? 'employee';

        // Only admin can do manual entry
        if ($role !== 'admin') {
            return redirect()->route('attendance.dashboard')
                ->with('error', 'Only administrators can do manual entry');
        }

        $request->validate([
            'employee_id' => 'required|string',
            'date' => 'required|date',
            'check_in' => 'required|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i',
            'status' => 'required|in:present,absent,late,leave'
        ]);

        try {
            $date = $request->date;
            $employee = $this->firebase->getEmployee($request->employee_id);

            if (!$employee) {
                return back()->with('error', 'Employee not found');
            }

            $attendanceData = [
                'checkIn' => $request->check_in,
                'checkOut' => $request->check_out,
                'location' => $request->location ?? 'Manual Entry',
                'notes' => $request->notes ?? 'Manual entry by admin',
                'status' => $request->status,
                'timestamp' => date('Y-m-d\TH:i:s\Z'),
                'manualEntry' => true,
                'enteredBy' => session('user')['name'] ?? 'Admin'
            ];

            $this->firebase->getDatabase()
                ->getReference("attendances/{$this->firebase->getCompanyId()}/$date/{$request->employee_id}")
                ->set($attendanceData);

            return redirect()->route('attendance.dashboard')
                ->with('success', "Manual attendance recorded for {$employee['name']} on $date");
        } catch (\Exception $e) {
            return back()->with('error', 'Manual entry failed: ' . $e->getMessage());
        }
    }

    // ==================== API METHODS ====================

    public function apiToday(): JsonResponse
    {
        $user = session('user');
        $role = $user['role'] ?? 'employee';
        $employeeId = $user['employee_id'] ?? null;

        try {
            if ($role === 'employee') {
                // Employee can only see their own attendance
                $attendance = $this->firebase->getTodayAttendance();
                $employeeAttendance = isset($attendance[$employeeId]) ? [$employeeId => $attendance[$employeeId]] : [];

                $employee = $this->firebase->getEmployee($employeeId);
                $total = 1;
                $present = isset($attendance[$employeeId]) ? 1 : 0;
                $absent = $total - $present;

                return response()->json([
                    'success' => true,
                    'data' => [
                        'date' => date('Y-m-d'),
                        'present' => $present,
                        'absent' => $absent,
                        'total' => $total,
                        'attendance_rate' => $total > 0 ? round(($present / $total) * 100, 2) : 0,
                        'attendance_list' => $employeeAttendance,
                        'employee_data' => $employee
                    ],
                    'message' => 'Today\'s attendance retrieved'
                ]);
            } else {
                // Admin/Manager can see all
                $attendance = $this->firebase->getTodayAttendance();
                $allEmployees = $this->firebase->getCompanyEmployees();

                $present = count($attendance);
                $total = count($allEmployees);
                $absent = $total - $present;

                return response()->json([
                    'success' => true,
                    'data' => [
                        'date' => date('Y-m-d'),
                        'present' => $present,
                        'absent' => $absent,
                        'total' => $total,
                        'attendance_rate' => $total > 0 ? round(($present / $total) * 100, 2) : 0,
                        'attendance_list' => $attendance
                    ],
                    'message' => 'Today\'s attendance retrieved'
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get today\'s attendance',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function apiEmployeeAttendance($employeeId): JsonResponse
    {
        $user = session('user');
        $role = $user['role'] ?? 'employee';
        $currentEmployeeId = $user['employee_id'] ?? null;

        // Check if employee trying to access other employee's data
        if ($role === 'employee' && $employeeId !== $currentEmployeeId) {
            return response()->json([
                'success' => false,
                'message' => 'You can only view your own attendance'
            ], 403);
        }

        try {
            $employee = $this->firebase->getEmployee($employeeId);

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee not found'
                ], 404);
            }

            $attendance = $this->firebase->getEmployeeAttendance($employeeId, date('Y-m'));

            return response()->json([
                'success' => true,
                'data' => [
                    'employee' => $employee,
                    'attendance' => $attendance,
                    'total_days' => count($attendance),
                    'present_days' => count(array_filter($attendance, function ($record) {
                        return isset($record['checkIn']);
                    }))
                ],
                'message' => 'Employee attendance retrieved'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get employee attendance',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
