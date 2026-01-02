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
        try {
            $today = date('Y-m-d');
            $attendanceData = $this->firebase->getTodayAttendance();
            $allEmployees = $this->firebase->getCompanyEmployees();

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
            usort($attendanceList, function($a, $b) {
                $timeA = $a['attendance']['checkIn'] ?? '00:00';
                $timeB = $b['attendance']['checkIn'] ?? '00:00';
                return strcmp($timeB, $timeA);
            });

            return view('attendance.dashboard', [
                'attendanceList' => $attendanceList,
                'allEmployees' => $allEmployees,
                'totalEmployees' => count($allEmployees),
                'presentCount' => count($attendanceList),
                'today' => $today
            ]);

        } catch (\Exception $e) {
            return view('attendance.dashboard', [
                'attendanceList' => [],
                'allEmployees' => [],
                'totalEmployees' => 0,
                'presentCount' => 0,
                'today' => date('Y-m-d'),
                'error' => $e->getMessage()
            ]);
        }
    }

    public function checkIn(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|string',
            'location' => 'required|string',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            $employee = $this->firebase->getEmployee($request->employee_id);

            if (!$employee) {
                $message = 'Employee not found';
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => $message], 404);
                }
                return back()->with('error', $message);
            }

            // Check if already checked in today
            $todayAttendance = $this->firebase->getTodayAttendance();
            if (isset($todayAttendance[$request->employee_id])) {
                $message = 'Already checked in today';
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => $message], 400);
                }
                return back()->with('error', $message);
            }

            $attendanceData = [
                'location' => $request->location,
                'notes' => $request->notes ?? ''
            ];

            $record = $this->firebase->recordCheckIn($request->employee_id, $attendanceData);

            $response = [
                'success' => true,
                'message' => 'Check-in successful!',
                'data' => [
                    'employee_id' => $request->employee_id,
                    'employee_name' => $employee['name'] ?? 'Unknown',
                    'check_in' => $record['checkIn'],
                    'location' => $record['location'],
                    'time' => $record['checkIn']
                ]
            ];

            if ($request->expectsJson()) {
                return response()->json($response);
            }

            return redirect()->route('attendance.dashboard')
                ->with('success', 'Check-in recorded successfully!')
                ->with('checkin_data', $response['data']);

        } catch (\Exception $e) {
            $error = 'Check-in failed: ' . $e->getMessage();
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $error], 500);
            }
            return back()->with('error', $error);
        }
    }

    public function checkOut(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            $employee = $this->firebase->getEmployee($request->employee_id);

            if (!$employee) {
                $message = 'Employee not found';
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => $message], 404);
                }
                return back()->with('error', $message);
            }

            $record = $this->firebase->recordCheckOut($request->employee_id);

            if (!$record) {
                $message = 'No check-in record found for today';
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => $message], 404);
                }
                return back()->with('error', $message);
            }

            $response = [
                'success' => true,
                'message' => 'Check-out successful!',
                'data' => [
                    'employee_id' => $request->employee_id,
                    'employee_name' => $employee['name'] ?? 'Unknown',
                    'check_out' => $record['checkOut'],
                    'hours_worked' => $record['hoursWorked'] ?? 0,
                    'overtime' => $record['overtime'] ?? 0,
                    'time' => $record['checkOut']
                ]
            ];

            if ($request->expectsJson()) {
                return response()->json($response);
            }

            return redirect()->route('attendance.dashboard')
                ->with('success', 'Check-out recorded successfully!')
                ->with('checkout_data', $response['data']);

        } catch (\Exception $e) {
            $error = 'Check-out failed: ' . $e->getMessage();
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $error], 500);
            }
            return back()->with('error', $error);
        }
    }

    public function report(Request $request)
    {
        $month = $request->get('month', date('Y-m'));
        $employeeId = $request->get('employee_id');

        try {
            $allEmployees = $this->firebase->getCompanyEmployees();

            if ($employeeId) {
                // Single employee report
                $attendance = $this->firebase->getEmployeeAttendance($employeeId, $month);
                $employee = $this->firebase->getEmployee($employeeId);

                if (!$employee) {
                    return redirect()->route('attendance.report')
                        ->with('error', 'Employee not found');
                }

                return view('attendance.report-single', compact('attendance', 'employee', 'month'));
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

                return view('attendance.report-all', compact('stats', 'month', 'allEmployees'));
            }

        } catch (\Exception $e) {
            return view('attendance.report-all', [
                'stats' => [],
                'month' => $month,
                'allEmployees' => [],
                'error' => $e->getMessage()
            ]);
        }
    }

    public function history(Request $request)
    {
        $employeeId = $request->get('employee_id');
        $month = $request->get('month', date('Y-m'));

        try {
            $allEmployees = $this->firebase->getCompanyEmployees();

            if ($employeeId) {
                $attendance = $this->firebase->getEmployeeAttendance($employeeId, $month);
                $employee = $this->firebase->getEmployee($employeeId);

                return view('attendance.history-single', compact('attendance', 'employee', 'month'));
            }

            $monthAttendance = $this->firebase->getAttendanceByMonth($month);

            return view('attendance.history', compact('monthAttendance', 'allEmployees', 'month'));

        } catch (\Exception $e) {
            return view('attendance.history', [
                'monthAttendance' => [],
                'allEmployees' => [],
                'month' => $month,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function manualEntry(Request $request)
    {
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
        try {
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
                    'present_days' => count(array_filter($attendance, function($record) {
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
