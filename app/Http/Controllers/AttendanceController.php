<?php

namespace App\Http\Controllers;

use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

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
                $todayAttendance = $this->firebase->getTodayAttendance(); // INI PERUBAHAN

                // Get employee's attendance data for the month
                $employeeAttendance = $this->firebase->getEmployeeAttendance($employeeId, date('Y-m'));

                if (isset($todayAttendance[$employeeId])) {
                    $attendanceData[$employeeId] = $todayAttendance[$employeeId];
                }

                // Get employee data
                $employee = $this->firebase->getEmployee($employeeId);
                $allEmployees = $employee ? [$employeeId => $employee] : [];

                // FOR TODAY'S ATTENDANCE VARIABLE (untuk view)
                $todayAttendanceData = $todayAttendance[$employeeId] ?? null; // INI PERUBAHAN

            } else {
                // Admin/Manager can see all
                $today = date('Y-m-d');
                $todayAttendance = $this->firebase->getTodayAttendance(); // INI PERUBAHAN
                $attendanceData = $todayAttendance;
                $allEmployees = $this->firebase->getCompanyEmployees();

                // Get employee attendance for stats (if needed)
                $employeeAttendance = $role === 'admin' ? [] :
                    $this->firebase->getEmployeeAttendance($employeeId, date('Y-m'));

                // For admin view, we don't need individual todayAttendanceData
                $todayAttendanceData = null;
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
                'today' => $today ?? date('Y-m-d'),
                'role' => $role,
                'currentEmployeeId' => $employeeId,
                // TAMBAHKAN VARIABLE INI:
                'todayAttendance' => $todayAttendanceData ?? null,
                'employeeAttendance' => $employeeAttendance ?? []
            ]);

        } catch (\Exception $e) {
            return view('attendance.dashboard', [
                'attendanceList' => [],
                'allEmployees' => [],
                'totalEmployees' => 0,
                'presentCount' => 0,
                'today' => date('Y-m-d'),
                'role' => $role,
                'todayAttendance' => null,
                'employeeAttendance' => [],
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

    /**
     * Show check-in page (for employees who haven't checked in)
     */
    public function checkinPage()
    {
        $user = session('user');
        $role = $user['role'] ?? 'employee';
        $employeeId = $user['employee_id'] ?? null;

        // Only employees can access
        if ($role !== 'employee') {
            return redirect()->route('attendance.dashboard')
                ->with('error', 'Only employees can check in');
        }

        // Check if already checked in today
        $todayAttendance = $this->firebase->getTodayAttendance();
        if (isset($todayAttendance[$employeeId])) {
            return redirect()->route('attendance.dashboard')
                ->with('info', 'You have already checked in today');
        }

        // Get employee data
        $employee = $this->firebase->getEmployee($employeeId);

        return view('attendance.checkin-page', [
            'employee' => $employee,
            'employeeId' => $employeeId
        ]);
    }

    /**
     * Generate barcode after check-in (for employee)
     */
    public function generateCheckInBarcode(Request $request)
    {
        $user = session('user');
        $role = $user['role'] ?? 'employee';
        $employeeId = $user['employee_id'] ?? null;

        // Only employees can generate barcode
        if ($role !== 'employee') {
            return response()->json([
                'success' => false,
                'message' => 'Only employees can generate check-in barcode'
            ], 403);
        }

        try {
            // Cek langsung di method ini (tanpa API call)
            $todayAttendance = $this->firebase->getTodayAttendance();

            if (!isset($todayAttendance[$employeeId])) {
                return response()->json([
                    'success' => false,
                    'message' => 'You must check in first before generating barcode',
                    'code' => 'NOT_CHECKED_IN'
                ], 400);
            }

            // Get employee data
            $employee = $this->firebase->getEmployee($employeeId);
            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee not found'
                ], 404);
            }

            $timestamp = time();
            $checkInData = $todayAttendance[$employeeId];
            $checkInTime = $checkInData['checkIn'] ?? date('H:i');

            // Simple format: employee_id:timestamp
            $dataString = $employeeId . ':' . $timestamp;
            $secret = md5($dataString . env('BARCODE_SECRET', 'attendance_secret_2024'));

            $barcodeData = $dataString . ':' . $secret;

            return response()->json([
                'success' => true,
                'message' => 'Check-in barcode generated',
                'data' => [
                    'barcode' => $barcodeData,
                    'employee_id' => $employeeId,
                    'employee_name' => $employee['name'],
                    'check_in_time' => $checkInTime,
                    'date' => date('Y-m-d'),
                    'timestamp' => $timestamp,
                    'valid_until' => date('Y-m-d H:i:s', $timestamp + 300),
                    'expires_in' => 300
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Generate barcode error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate barcode: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify check-in barcode (for admin scanning)
     */
    public function verifyCheckInBarcode(Request $request)
    {
        $user = session('user');
        $role = $user['role'] ?? 'employee';

        // Only admin/manager can verify barcodes
        if (!in_array($role, ['admin', 'manager'])) {
            return response()->json([
                'success' => false,
                'message' => 'Only administrators can verify barcodes'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'barcode_data' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid barcode data'
            ], 422);
        }

        try {
            $barcodeData = $request->barcode_data;
            $parts = explode(':', $barcodeData);

            if (count($parts) < 5) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid barcode format'
                ], 400);
            }

            $employeeId = $parts[0];
            $checkInTime = $parts[1];
            $date = $parts[2];
            $timestamp = $parts[3];
            $secret = $parts[4];

            // Verify barcode is not expired (valid for 5 minutes)
            $currentTime = time();
            if (($currentTime - intval($timestamp)) > 300) { // 5 minutes
                return response()->json([
                    'success' => false,
                    'message' => 'Barcode expired. Please generate a new one.',
                    'code' => 'BARCODE_EXPIRED'
                ], 400);
            }

            // Verify secret hash
            $dataString = $employeeId . ':' . $checkInTime . ':' . $date . ':' . $timestamp;
            $expectedSecret = md5($dataString . env('BARCODE_SECRET', 'attendance_secret_2024'));

            if ($secret !== $expectedSecret) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid barcode security code'
                ], 400);
            }

            // Check if employee exists
            $employee = $this->firebase->getEmployee($employeeId);
            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee not found'
                ], 404);
            }

            // Check if employee actually checked in on that date
            $attendance = $this->firebase->getDatabase()
                ->getReference("attendances/{$this->firebase->getCompanyId()}/{$date}/{$employeeId}")
                ->getValue();

            if (!$attendance || !isset($attendance['checkIn'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'No check-in record found for this date',
                    'code' => 'NO_CHECKIN_RECORD'
                ], 400);
            }

            // Verify check-in time matches (within 5 minute tolerance)
            $recordedTime = $attendance['checkIn'];
            if (abs(strtotime($recordedTime) - strtotime($checkInTime)) > 300) {
                return response()->json([
                    'success' => false,
                    'message' => 'Check-in time does not match records',
                    'code' => 'TIME_MISMATCH'
                ]);
            }

            // Mark barcode as verified
            $this->storeBarcodeVerification($employeeId, $barcodeData, 'verified_by_' . $user['name']);

            return response()->json([
                'success' => true,
                'message' => 'Check-in verified successfully',
                'data' => [
                    'employee_id' => $employeeId,
                    'employee_name' => $employee['name'],
                    'department' => $employee['department'] ?? 'Unknown',
                    'position' => $employee['position'] ?? 'Staff',
                    'check_in_time' => $attendance['checkIn'],
                    'check_out_time' => $attendance['checkOut'] ?? null,
                    'location' => $attendance['location'] ?? 'Unknown',
                    'date' => $date,
                    'verification_time' => date('Y-m-d H:i:s'),
                    'verified_by' => $user['name'] ?? 'Admin',
                    'status' => 'verified',
                    'attendance_data' => $attendance
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Verify barcode error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Verification failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store barcode in database
     */
    private function storeBarcode($employeeId, $barcodeData, $type = 'checkin_verification')
    {
        try {
            $logData = [
                'employee_id' => $employeeId,
                'barcode_data' => $barcodeData,
                'type' => $type,
                'generated_at' => now()->toISOString(),
                'expires_at' => now()->addMinutes(5)->toISOString(),
                'status' => 'active'
            ];

            $this->firebase->getDatabase()
                ->getReference("barcodes/{$this->firebase->getCompanyId()}")
                ->push()
                ->set($logData);

            return true;
        } catch (\Exception $e) {
            Log::error('Store barcode error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Store barcode verification
     */
    private function storeBarcodeVerification($employeeId, $barcodeData, $verifiedBy)
    {
        try {
            $logData = [
                'employee_id' => $employeeId,
                'barcode_data' => $barcodeData,
                'verified_by' => $verifiedBy,
                'verified_at' => now()->toISOString(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent()
            ];

            $this->firebase->getDatabase()
                ->getReference("barcode_verifications/{$this->firebase->getCompanyId()}")
                ->push()
                ->set($logData);

            return true;
        } catch (\Exception $e) {
            Log::error('Store verification error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get barcode verification history (for admin)
     */
    public function barcodeVerificationHistory(Request $request)
    {
        $user = session('user');
        $role = $user['role'] ?? 'employee';

        // Only admin can view verification history
        if (!in_array($role, ['admin', 'manager'])) {
            return redirect()->route('attendance.dashboard')
                ->with('error', 'Only administrators can view verification history');
        }

        try {
            $verifications = $this->firebase->getDatabase()
                ->getReference("barcode_verifications/{$this->firebase->getCompanyId()}")
                ->orderByChild('verified_at')
                ->limitToLast(100)
                ->getValue();

            $verifications = $verifications ? array_reverse($verifications) : [];

            // Get employee names
            $employees = $this->firebase->getCompanyEmployees();

            return view('attendance.barcode-verification-history', [
                'verifications' => $verifications,
                'employees' => $employees,
                'total' => count($verifications)
            ]);

        } catch (\Exception $e) {
            return view('attendance.barcode-verification-history', [
                'verifications' => [],
                'employees' => [],
                'total' => 0,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Show employee barcode page
     */
    public function showEmployeeBarcode()
    {
        $user = session('user');
        $employeeId = $user['employee_id'] ?? null;
        $role = $user['role'] ?? 'employee';

        // Only employees can access
        if ($role !== 'employee') {
            return redirect()->route('attendance.dashboard')
                ->with('error', 'Only employees can generate barcode');
        }

        try {
            // Cek langsung dari Firebase (TANPA API)
            $todayAttendance = $this->firebase->getTodayAttendance();
            $hasCheckedIn = isset($todayAttendance[$employeeId]);
            $todayAttendanceData = $hasCheckedIn ? $todayAttendance[$employeeId] : null;

            // Get employee data
            $employee = $this->firebase->getEmployee($employeeId);

            if (!$employee) {
                return redirect()->route('attendance.dashboard')
                    ->with('error', 'Employee not found');
            }

            return view('attendance.employee-barcode', [
                'hasCheckedIn' => $hasCheckedIn,
                'todayAttendance' => $todayAttendanceData,
                'employee' => $employee,
                'employeeId' => $employeeId,
                'currentEmployeeId' => $employeeId
            ]);

        } catch (\Exception $e) {
            Log::error('Show employee barcode error: ' . $e->getMessage());

            return redirect()->route('attendance.dashboard')
                ->with('error', 'Failed to load barcode page: ' . $e->getMessage());
        }
    }

    // AttendanceController.php (tambahkan sebelum method terakhir)

    /**
     * Show barcode scanner page for admin
     */
    public function showBarcodeScanner()
    {
        $user = session('user');
        $role = $user['role'] ?? 'employee';

        // Only admin/manager can access scanner
        if (!in_array($role, ['admin', 'manager'])) {
            return redirect()->route('attendance.dashboard')
                ->with('error', 'Only administrators can access the scanner');
        }

        return view('admin.scanner');
    }

    /**
     * Verify barcode scan from admin
     */
    public function verifyBarcodeScan(Request $request)
    {
        $user = session('user');
        $role = $user['role'] ?? 'employee';

        // Only admin/manager can verify scans
        if (!in_array($role, ['admin', 'manager'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'barcode_data' => 'required|string|min:10'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid barcode data'
            ], 422);
        }

        try {
            $barcodeData = $request->barcode_data;

            // Parse barcode data format: employee_id:timestamp:date:checkin_time:secret
            $parts = explode(':', $barcodeData);

            if (count($parts) < 5) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid barcode format'
                ], 400);
            }

            $employeeId = $parts[0];
            $timestamp = $parts[1];
            $date = $parts[2];
            $checkInTime = $parts[3];
            $secret = $parts[4];

            // Verify barcode is not expired (5 minutes validity)
            $currentTime = time();
            if (($currentTime - intval($timestamp)) > 300) {
                return response()->json([
                    'success' => false,
                    'message' => 'Barcode has expired. Please generate a new one.',
                    'code' => 'BARCODE_EXPIRED'
                ], 400);
            }

            // Verify secret hash
            $dataString = $employeeId . ':' . $timestamp . ':' . $date . ':' . $checkInTime;
            $expectedSecret = md5($dataString . env('BARCODE_SECRET', 'attendance_secret_2024'));

            if ($secret !== $expectedSecret) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid security code'
                ], 400);
            }

            // Get employee data
            $employee = $this->firebase->getEmployee($employeeId);
            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee not found'
                ], 404);
            }

            // Get attendance record for verification
            $attendanceRef = $this->firebase->getDatabase()
                ->getReference("attendances/{$this->firebase->getCompanyId()}/{$date}/{$employeeId}");

            $attendance = $attendanceRef->getValue();

            if (!$attendance || !isset($attendance['checkIn'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'No check-in record found for this date'
                ], 400);
            }

            // Mark as verified
            $verifiedData = [
                'verified' => true,
                'verified_at' => now()->toISOString(),
                'verified_by' => $user['name'] ?? 'Admin',
                'verification_method' => 'barcode_scan'
            ];

            $attendanceRef->update($verifiedData);

            // Log the verification
            $this->logBarcodeVerification([
                'employee_id' => $employeeId,
                'barcode_data' => $barcodeData,
                'verified_by' => $user['name'] ?? 'Admin',
                'verification_time' => now()->toISOString(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Barcode verified successfully',
                'data' => [
                    'employee' => [
                        'id' => $employeeId,
                        'name' => $employee['name'],
                        'department' => $employee['department'] ?? 'N/A',
                        'position' => $employee['position'] ?? 'Staff'
                    ],
                    'attendance' => array_merge($attendance, $verifiedData),
                    'verification' => [
                        'time' => now()->format('H:i:s'),
                        'date' => now()->format('Y-m-d'),
                        'scanned_by' => $user['name'] ?? 'Admin'
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Barcode verification error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Verification failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Log barcode verification
     */
    private function logBarcodeVerification($data)
    {
        try {
            $logRef = $this->firebase->getDatabase()
                ->getReference("barcode_verifications/{$this->firebase->getCompanyId()}")
                ->push();

            $logRef->set($data);

            return true;
        } catch (\Exception $e) {
            Log::error('Barcode verification logging error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get recent verifications
     */
    public function getRecentVerifications()
    {
        $user = session('user');
        $role = $user['role'] ?? 'employee';

        if (!in_array($role, ['admin', 'manager'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        try {
            $verifications = $this->firebase->getDatabase()
                ->getReference("barcode_verifications/{$this->firebase->getCompanyId()}")
                ->orderByChild('verification_time')
                ->limitToLast(50)
                ->getValue();

            $verifications = $verifications ? array_reverse($verifications) : [];

            // Get employee names
            $employees = $this->firebase->getCompanyEmployees();

            $result = [];
            foreach ($verifications as $key => $verification) {
                $employee = $employees[$verification['employee_id']] ?? null;

                $result[] = [
                    'id' => $key,
                    'employee_id' => $verification['employee_id'],
                    'employee_name' => $employee['name'] ?? $verification['employee_id'],
                    'verified_by' => $verification['verified_by'] ?? 'System',
                    'verification_time' => $verification['verification_time'] ?? now()->toISOString(),
                    'status' => 'verified'
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Get recent verifications error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to get verifications'
            ], 500);
        }
    }

    // ==================== API METHODS ====================

    public function apiToday(): JsonResponse
    {
        try {
            $user = session('user');
            $role = $user['role'] ?? 'employee';
            $employeeId = $user['employee_id'] ?? null;

            // Debug log
            Log::info('API Today accessed', ['role' => $role, 'employeeId' => $employeeId]);

            if ($role === 'employee') {
                // Employee can only see their own attendance
                $attendance = $this->firebase->getTodayAttendance();

                // Pastikan format benar
                $employeeAttendance = [];
                if (isset($attendance[$employeeId])) {
                    $employeeAttendance[$employeeId] = $attendance[$employeeId];
                }

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
                        'employee_data' => $employee ?: null
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
            Log::error('API Today error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to get today\'s attendance',
                'error' => $e->getMessage(),
                'trace' => env('APP_DEBUG') ? $e->getTraceAsString() : null
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
