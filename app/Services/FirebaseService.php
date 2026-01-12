<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Database;
use Kreait\Firebase\Auth;

class FirebaseService
{
    protected $database;
    protected $auth;
    protected $firebase;
    protected $companyId;

    public function __construct()
    {
        // Path credentials
        $credentialsPath = storage_path('app/firebase/credentials.json');

        if (!file_exists($credentialsPath)) {
            throw new \Exception("Firebase credentials file not found at: $credentialsPath");
        }

        // Load credentials
        $serviceAccount = json_decode(file_get_contents($credentialsPath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Invalid JSON in credentials file: " . json_last_error_msg());
        }

        // Initialize Firebase
        $this->firebase = (new Factory)
            ->withServiceAccount($serviceAccount)
            ->withDatabaseUri('https://manajemen-karyawan-d0f43-default-rtdb.asia-southeast1.firebasedatabase.app');

        $this->database = $this->firebase->createDatabase();
        $this->auth = $this->firebase->createAuth();

        // Set company ID
        $this->companyId = env('FIREBASE_COMPANY_ID', 'company_abc123');
    }

    public function getDatabase(): Database
    {
        return $this->database;
    }

    public function getAuth()
    {
        return $this->auth;
    }

    public function getCompanyId()
    {
        return $this->companyId;
    }

    // ==================== AUTH METHODS ====================

    /**
     * Create authentication user and database record
     */
    public function createAuthUser($email, $password, $userData)
    {
        try {
            // 1. Create user in Firebase Authentication
            $authUser = $this->auth->createUser([
                'email' => $email,
                'password' => $password,
                'displayName' => $userData['name'],
                'emailVerified' => true
            ]);

            $uid = $authUser->uid;

            // 2. Prepare user data for database
            $dbUserData = [
                'uid' => $uid,
                'email' => $email,
                'name' => $userData['name'],
                'role' => 'employee',
                'employee_id' => $userData['employee_id'],
                'companyId' => $this->companyId,
                'created_at' => now()->toISOString()
            ];

            // 3. Save to users collection
            $this->database->getReference('users/' . $uid)->set($dbUserData);

            Log::info("Auth user created: $uid for employee: " . $userData['employee_id']);

            return $uid;
        } catch (\Exception $e) {
            Log::error('Create auth user error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate random password
     */
    public function generateRandomPassword($length = 8)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&*';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $password;
    }

    // ==================== EMPLOYEE METHODS ====================

    public function getAllEmployees()
    {
        try {
            $employees = $this->database->getReference('employees')->getValue();
            return $employees ?: [];
        } catch (\Exception $e) {
            Log::error('Firebase getEmployees error: ' . $e->getMessage());
            return [];
        }
    }

    public function getCompanyEmployees()
    {
        try {
            $allEmployees = $this->getAllEmployees();

            if (empty($this->companyId)) {
                return $allEmployees;
            }

            // Filter employees by companyId
            $filteredEmployees = [];
            foreach ($allEmployees as $id => $employee) {
                if (isset($employee['companyId']) && $employee['companyId'] === $this->companyId) {
                    $filteredEmployees[$id] = $employee;
                } else if (!isset($employee['companyId']) && $this->companyId === 'company_abc123') {
                    // Include employees without companyId for default company
                    $filteredEmployees[$id] = $employee;
                }
            }

            return $filteredEmployees;
        } catch (\Exception $e) {
            Log::error('Firebase getCompanyEmployees error: ' . $e->getMessage());
            return [];
        }
    }

    public function getEmployee(string $employeeId)
    {
        try {
            $employee = $this->database->getReference('employees/' . $employeeId)->getValue();
            return $employee ?: null;
        } catch (\Exception $e) {
            Log::error('Firebase getEmployee error: ' . $e->getMessage());
            return null;
        }
    }

    public function createEmployee(array $data)
    {
        try {
            // Generate employee ID
            $employeeId = 'emp_' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

            // Prepare employee data
            $employeeData = [
                'id' => $employeeId,
                'companyId' => $this->companyId,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? '',
                'department' => $data['department'],
                'position' => $data['position'],
                'joinDate' => $data['hire_date'] ?? date('Y-m-d'),
                'address' => $data['address'] ?? '',
                'salary' => $data['salary'] ?? 0,
                'status' => 'active',
                'role' => 'employee',
                'createdAt' => now()->toISOString(),
                'updatedAt' => now()->toISOString()
            ];

            // Save to Firebase
            $this->database->getReference('employees/' . $employeeId)->set($employeeData);

            Log::info("Employee created: $employeeId");
            return $employeeId;
        } catch (\Exception $e) {
            Log::error('Firebase createEmployee error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function updateEmployee($employeeId, array $data)
    {
        try {
            $updateData = [
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'department' => $data['department'],
                'position' => $data['position'],
                'address' => $data['address'] ?? '',
                'status' => $data['status'] ?? 'active',
                'updatedAt' => now()->toISOString()
            ];

            $this->database->getReference('employees/' . $employeeId)->update($updateData);

            Log::info("Employee updated: $employeeId");
            return true;
        } catch (\Exception $e) {
            Log::error('Firebase updateEmployee error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function deleteEmployee($employeeId)
    {
        try {
            // First get employee data to check if there's a user account
            $employee = $this->getEmployee($employeeId);

            if ($employee && isset($employee['uid'])) {
                try {
                    // Delete from Firebase Auth
                    $this->auth->deleteUser($employee['uid']);

                    // Delete from users collection
                    $this->database->getReference('users/' . $employee['uid'])->remove();

                    Log::info("Auth user deleted: " . $employee['uid']);
                } catch (\Exception $authError) {
                    Log::warning("Failed to delete auth user: " . $authError->getMessage());
                }
            }

            // Delete employee record
            $this->database->getReference('employees/' . $employeeId)->remove();

            Log::info("Employee deleted: $employeeId");
            return true;
        } catch (\Exception $e) {
            Log::error('Firebase deleteEmployee error: ' . $e->getMessage());
            throw $e;
        }
    }

    // ==================== USER METHODS ====================

    public function getAllUsers()
    {
        try {
            $users = $this->database->getReference('users')->getValue();
            return $users ?: [];
        } catch (\Exception $e) {
            Log::error('Firebase getAllUsers error: ' . $e->getMessage());
            return [];
        }
    }

    public function getUserByEmail($email)
    {
        try {
            $users = $this->getAllUsers();
            foreach ($users as $uid => $user) {
                if (isset($user['email']) && $user['email'] === $email) {
                    return $user;
                }
            }
            return null;
        } catch (\Exception $e) {
            Log::error('Firebase getUserByEmail error: ' . $e->getMessage());
            return null;
        }
    }

    // ==================== ATTENDANCE METHODS ====================

    public function getTodayAttendance()
    {
        try {
            $date = date('Y-m-d');
            $attendance = $this->database
                ->getReference("attendances/{$this->companyId}/{$date}")
                ->getValue();

            return $attendance ?: [];
        } catch (\Exception $e) {
            Log::error('Firebase getTodayAttendance error: ' . $e->getMessage());
            return [];
        }
    }

    public function recordCheckIn($employeeId, array $data)
    {
        try {
            $date = date('Y-m-d');

            // pakai waktu dari controller kalau dikirim, kalau tidak pakai server time
            $time = $data['checkIn'] ?? date('H:i');

            $officeStart = \Carbon\Carbon::parse($date . ' 08:00');
            $checkInTime = \Carbon\Carbon::parse($date . ' ' . $time);

            $isLate = $checkInTime->gt($officeStart);
            $lateMinutes = $isLate ? $officeStart->diffInMinutes($checkInTime) : 0;

            // Struktur tetap sama + boleh tambah hoursWorked/lateMinutes (tidak mengubah struktur inti)
            $attendanceData = [
                'checkIn'     => $time,
                'checkOut'    => null,
                'location'    => $data['location'] ?? 'Office',
                'notes'       => $data['notes'] ?? '',
                'hoursWorked' => 0,
                'overtime'    => 0,
                'lateMinutes' => $lateMinutes,
                'status'      => $isLate ? 'late' : 'present',
                'timestamp'   => $data['checkedInAt'] ?? now()->toISOString(),
            ];

            $this->database
                ->getReference("attendances/{$this->companyId}/{$date}/{$employeeId}")
                ->set($attendanceData);

            $this->database
                ->getReference("liveStatus/{$this->companyId}/{$employeeId}")
                ->set([
                    'status' => 'checked_in',
                    'lastUpdate' => date('Y-m-d H:i:s')
                ]);

            Log::info("Check-in recorded for employee: $employeeId");
            return $attendanceData;
        } catch (\Exception $e) {
            Log::error('Firebase recordCheckIn error: ' . $e->getMessage());
            throw $e;
        }
    }



    public function recordCheckOut($employeeId)
    {
        try {
            $date = date('Y-m-d');
            $time = date('H:i');

            $attendanceRef = $this->database
                ->getReference("attendances/{$this->companyId}/{$date}/{$employeeId}");

            $attendance = $attendanceRef->getValue();

            if (!$attendance || empty($attendance['checkIn'])) {
                Log::warning("No check-in found for employee: $employeeId on $date");
                return false;
            }

            $checkInTime  = \Carbon\Carbon::parse($date . ' ' . $attendance['checkIn']);
            $checkOutTime = \Carbon\Carbon::parse($date . ' ' . $time);

            // Guard: kalau checkout < checkin
            if ($checkOutTime->lt($checkInTime)) {
                Log::warning("Invalid checkout (before checkin) for employee: $employeeId on $date");
                return false;
            }

            // Jam kantor
            $officeStart   = \Carbon\Carbon::parse($date . ' 08:00');
            $officeEnd     = \Carbon\Carbon::parse($date . ' 16:00');
            $overtimeStart = \Carbon\Carbon::parse($date . ' 18:00');

            // 1) late minutes
            $lateMinutes = 0;
            if ($checkInTime->gt($officeStart)) {
                $lateMinutes = $officeStart->diffInMinutes($checkInTime);
            }

            // 2) hoursWorked normal (08:00-16:00)
            $normalStart = $checkInTime->lt($officeStart) ? $officeStart : $checkInTime;
            $normalEnd   = $checkOutTime->gt($officeEnd) ? $officeEnd : $checkOutTime;

            $normalWorkedMinutes = 0;
            if ($normalEnd->gt($normalStart)) {
                $normalWorkedMinutes = $normalStart->diffInMinutes($normalEnd);
            }

            $hoursWorked = round($normalWorkedMinutes / 60, 2);

            // 3) overtime hanya mulai 18:00
            $overtimeMinutes = 0;
            if ($checkOutTime->gt($overtimeStart)) {
                $overtimeMinutes = $overtimeStart->diffInMinutes($checkOutTime);
            }

            $overtime = round($overtimeMinutes / 60, 2);

            // 4) update attendance
            $updateData = [
                'checkOut'     => $time,
                'hoursWorked'  => $hoursWorked,
                'overtime'     => $overtime,
                'lateMinutes'  => $lateMinutes,
                'updatedAt'    => now()->toISOString(),
            ];

            // Optional: status tetap late/present dari check-in, tapi kalau belum ada kita set
            if (!isset($attendance['status'])) {
                $updateData['status'] = ($lateMinutes > 0) ? 'late' : 'present';
            }

            $attendanceRef->update($updateData);

            // Update live status
            $this->database
                ->getReference("liveStatus/{$this->companyId}/{$employeeId}")
                ->set([
                    'status' => 'checked_out',
                    'lastUpdate' => date('Y-m-d H:i:s')
                ]);

            // Return merged result
            return array_merge($attendance, $updateData);
        } catch (\Exception $e) {
            Log::error('Firebase recordCheckOut error: ' . $e->getMessage());
            throw $e;
        }
    }


    // Tambahkan method ini di FirebaseService.php

    /**
     * Get attendance by month with proper structure
     */
    public function getAttendanceByMonth($yearMonth)
    {
        try {
            $attendance = $this->database
                ->getReference("attendances/{$this->companyId}")
                ->getValue();

            $monthAttendance = [];
            if ($attendance) {
                foreach ($attendance as $date => $dayAttendance) {
                    if (str_starts_with($date, $yearMonth)) {
                        $monthAttendance[$date] = $dayAttendance;
                    }
                }
            }

            return $monthAttendance;
        } catch (\Exception $e) {
            Log::error('Firebase getAttendanceByMonth error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all attendance data for reporting
     */
    public function getAllAttendance()
    {
        try {
            $attendance = $this->database
                ->getReference("attendances/{$this->companyId}")
                ->getValue();

            return $attendance ?: [];
        } catch (\Exception $e) {
            Log::error('Firebase getAllAttendance error: ' . $e->getMessage());
            return [];
        }
    }

    public function getEmployeeAttendance($employeeId, $month = null)
    {
        try {
            $month = $month ?: date('Y-m');

            $attendance = $this->database
                ->getReference("attendances/{$this->companyId}")
                ->getValue();

            $employeeAttendance = [];
            if ($attendance) {
                foreach ($attendance as $date => $dayAttendance) {
                    if (str_starts_with($date, $month) && isset($dayAttendance[$employeeId])) {
                        $employeeAttendance[$date] = $dayAttendance[$employeeId];
                    }
                }
            }

            // Sort by date descending
            krsort($employeeAttendance);

            return $employeeAttendance;
        } catch (\Exception $e) {
            Log::error('Firebase getEmployeeAttendance error: ' . $e->getMessage());
            return [];
        }
    }

    // ==================== LEAVE METHODS ====================

    public function getAllLeaves()
    {
        try {
            $leaves = $this->database
                ->getReference("leaveRequests/{$this->companyId}")
                ->getValue();

            return $leaves ?: [];
        } catch (\Exception $e) {
            Log::error('Firebase getAllLeaves error: ' . $e->getMessage());
            return [];
        }
    }

    public function getLeave($leaveId)
    {
        try {
            $leave = $this->database
                ->getReference("leaveRequests/{$this->companyId}/{$leaveId}")
                ->getValue();

            return $leave ?: null;
        } catch (\Exception $e) {
            Log::error('Firebase getLeave error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create leave with proof (NEW METHOD)
     * @param array $data Leave data
     * @param string|null $proofUrl Cloudinary URL (optional)
     * @param string|null $proofFilename Original filename (optional)
     * @return string Leave ID
     */
    public function createLeaveWithProof(array $data, ?string $proofUrl = null, ?string $proofFilename = null)
    {
        try {
            $leaveId = 'leave_' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);

            $leaveData = [
                'employeeId' => $data['employee_id'],
                'type' => $data['leave_type'],
                'startDate' => $data['start_date'],
                'endDate' => $data['end_date'],
                'reason' => $data['reason'],
                'contactDuringLeave' => $data['contact_during_leave'] ?? '',
                'status' => 'pending',
                'createdAt' => now()->toISOString(),
                'appliedDate' => now()->toISOString(),
                'approvedBy' => null
            ];

            // Add proof data if provided
            if ($proofUrl) {
                $leaveData['proof_url'] = $proofUrl;
                $leaveData['proof_filename'] = $proofFilename;
            }

            $this->database
                ->getReference("leaveRequests/{$this->companyId}/{$leaveId}")
                ->set($leaveData);

            Log::info("Leave created with proof: $leaveId" . ($proofUrl ? " (with proof)" : ""));
            return $leaveId;
        } catch (\Exception $e) {
            Log::error('Firebase createLeaveWithProof error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Original createLeave method - KEEP FOR BACKWARD COMPATIBILITY
     */
    public function createLeave(array $data)
    {
        // Reuse the new method but without proof
        return $this->createLeaveWithProof($data, null, null);
    }

    public function getEmployeeLeaves($employeeId)
    {
        try {
            $allLeaves = $this->getAllLeaves();

            $employeeLeaves = [];
            foreach ($allLeaves as $leaveId => $leave) {
                if (($leave['employeeId'] ?? '') === $employeeId) {
                    $employeeLeaves[$leaveId] = $leave;
                }
            }

            return $employeeLeaves;
        } catch (\Exception $e) {
            Log::error('Firebase getEmployeeLeaves error: ' . $e->getMessage());
            return [];
        }
    }

    public function approveLeave($leaveId, $approvedBy)
    {
        try {
            $this->database
                ->getReference("leaveRequests/{$this->companyId}/{$leaveId}")
                ->update([
                    'status' => 'approved',
                    'approvedBy' => $approvedBy,
                    'approvedAt' => now()->toISOString()
                ]);

            Log::info("Leave approved: $leaveId by $approvedBy");
            return true;
        } catch (\Exception $e) {
            Log::error('Firebase approveLeave error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function rejectLeave($leaveId, $rejectedBy, $reason)
    {
        try {
            $this->database
                ->getReference("leaveRequests/{$this->companyId}/{$leaveId}")
                ->update([
                    'status' => 'rejected',
                    'rejectedBy' => $rejectedBy,
                    'rejectionReason' => $reason,
                    'rejectedAt' => now()->toISOString()
                ]);

            Log::info("Leave rejected: $leaveId by $rejectedBy");
            return true;
        } catch (\Exception $e) {
            Log::error('Firebase rejectLeave error: ' . $e->getMessage());
            throw $e;
        }
    }

    // ==================== DASHBOARD METHODS ====================

    public function getDashboardStats()
    {
        try {
            $employees = $this->getCompanyEmployees();
            $totalEmployees = count($employees);

            // Count active employees
            $activeEmployees = 0;
            foreach ($employees as $employee) {
                if (($employee['status'] ?? 'active') === 'active') {
                    $activeEmployees++;
                }
            }

            // Today's attendance
            $todayAttendance = $this->getTodayAttendance();
            $presentToday = count($todayAttendance);

            // Pending leaves
            $leaves = $this->getAllLeaves();
            $pendingLeaves = 0;
            foreach ($leaves as $leave) {
                if (($leave['status'] ?? 'pending') === 'pending') {
                    $pendingLeaves++;
                }
            }

            return [
                'total_employees' => $totalEmployees,
                'active_employees' => $activeEmployees,
                'present_today' => $presentToday,
                'pending_leaves' => $pendingLeaves
            ];
        } catch (\Exception $e) {
            Log::error('Firebase getDashboardStats error: ' . $e->getMessage());
            return [
                'total_employees' => 0,
                'active_employees' => 0,
                'present_today' => 0,
                'pending_leaves' => 0
            ];
        }
    }

    // ==================== AUTHENTICATION METHODS ====================

    public function authenticateUser($email, $password)
    {
        try {
            // For demo - in real app, use Firebase Auth
            $users = $this->getAllUsers();

            foreach ($users as $uid => $user) {
                if ($user['email'] === $email) {
                    // In real app, verify password with Firebase Auth
                    // For demo, we'll just return user
                    return [
                        'uid' => $uid,
                        'email' => $user['email'],
                        'name' => $user['name'],
                        'role' => $user['role'],
                        'employee_id' => $user['employee_id'] ?? null
                    ];
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Authentication error: ' . $e->getMessage());
            return null;
        }
    }

    public function createUser($uid, $userData)
    {
        $this->database->getReference('users/' . $uid)->set($userData);
        return true;
    }

    // ==================== UTILITY METHODS ====================

    /**
     * Reset user password
     */
    public function resetUserPassword($uid)
    {
        try {
            $newPassword = $this->generateRandomPassword();

            // Update password in Firebase Auth
            $this->auth->changeUserPassword($uid, $newPassword);

            Log::info("Password reset for user: $uid");

            return $newPassword;
        } catch (\Exception $e) {
            Log::error('Reset password error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update user email
     */
    public function updateUserEmail($uid, $newEmail)
    {
        try {
            // Update in Firebase Auth
            $this->auth->changeUserEmail($uid, $newEmail);

            // Update in database
            $this->database->getReference('users/' . $uid)->update([
                'email' => $newEmail,
                'updated_at' => now()->toISOString()
            ]);

            Log::info("Email updated for user: $uid");

            return true;
        } catch (\Exception $e) {
            Log::error('Update email error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getEmployeeLiveStatus($employeeId)
    {
        try {
            $status = $this->database
                ->getReference("liveStatus/{$this->companyId}/{$employeeId}")
                ->getValue();

            if ($status) {
                // Get today's attendance for more details
                $todayAttendance = $this->getTodayAttendance();
                $todayData = $todayAttendance[$employeeId] ?? null;

                return array_merge($status, [
                    'today_attendance' => $todayData,
                    'last_update' => $status['lastUpdate'] ?? null,
                    'current_status' => $status['status'] ?? 'unknown'
                ]);
            }

            return ['status' => 'offline', 'last_update' => null];
            Log::error('Get employee live status error: ' . $e->getMessage());
            return ['status' => 'error', 'last_update' => null];
        } catch (\Exception $e) {
            Log::error('Get employee live status error: ' . $e->getMessage());
            return ['status' => 'error', 'last_update' => null];
        }
    }

    public function updateLiveStatus($employeeId, $status, $data = [])
    {
        try {
            $updateData = [
                'status' => $status,
                'lastUpdate' => date('Y-m-d H:i:s'),
                'updated_at' => now()->toISOString()
            ];

            if (!empty($data)) {
                $updateData = array_merge($updateData, $data);
            }

            $this->database
                ->getReference("liveStatus/{$this->companyId}/{$employeeId}")
                ->set($updateData);

            return true;
        } catch (\Exception $e) {
            Log::error('Update live status error: ' . $e->getMessage());
            return false;
        }
    }

    public function updateAttendance($employeeId, array $data)
    {
        $date = date('Y-m-d');

        $this->database
            ->getReference("attendances/{$this->companyId}/$date/$employeeId")
            ->update($data);
    }

        // FirebaseService.php - Tambahkan method:

    /**
     * Generate barcode data for employee
     */
    public function generateEmployeeBarcode($employeeId)
    {
        try {
            $employee = $this->getEmployee($employeeId);
            if (!$employee) {
                return null;
            }

            $timestamp = time();
            $secret = md5($employeeId . $timestamp . env('BARCODE_SECRET', 'attendance_secret'));

            return [
                'barcode' => $employeeId . ':' . $timestamp . ':' . $secret,
                'employee_id' => $employeeId,
                'employee_name' => $employee['name'],
                'timestamp' => $timestamp,
                'valid_until' => date('Y-m-d H:i:s', $timestamp + 30),
                'qr_data' => $employeeId . '|' . $employee['name'] . '|' . date('YmdHis')
            ];
        } catch (\Exception $e) {
            Log::error('Generate barcode error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get barcode scan logs
     */
    public function getBarcodeLogs($limit = 100)
    {
        try {
            $logs = $this->database
                ->getReference('barcode_logs/' . $this->companyId)
                ->orderByChild('timestamp')
                ->limitToLast($limit)
                ->getValue();

            return $logs ? array_reverse($logs) : [];
        } catch (\Exception $e) {
            Log::error('Get barcode logs error: ' . $e->getMessage());
            return [];
        }
    }
}
