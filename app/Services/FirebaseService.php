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
        // Cari file credentials di beberapa lokasi
        $possiblePaths = [
            storage_path('app/firebase/credentials.json'),
            base_path('storage/app/firebase/credentials.json'),
            base_path('credentials.json'),
            __DIR__ . '/../../storage/app/firebase/credentials.json',
            __DIR__ . '/../../credentials.json',
        ];

        $credentialsPath = null;

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $credentialsPath = $path;
                break;  
            }
        }

        if (!$credentialsPath) {
            throw new \Exception("Firebase credentials file not found. Tried paths: " . implode(', ', $possiblePaths));
        }

        Log::info('Using Firebase credentials from: ' . $credentialsPath);

        // Load credentials
        $serviceAccount = json_decode(file_get_contents($credentialsPath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Invalid JSON in credentials file: " . json_last_error_msg());
        }

        // Initialize Firebase
        $this->firebase = (new Factory)
            ->withServiceAccount($serviceAccount)
            ->withDatabaseUri(config('firebase.database.url') ?? env('FIREBASE_DATABASE_URL'));

        $this->database = $this->firebase->createDatabase();
        $this->auth = $this->firebase->createAuth();

        // Set company ID from env
        $this->companyId = env('FIREBASE_COMPANY_ID', 'company_abc123');

        Log::info('Firebase initialized successfully. Company ID: ' . $this->companyId);
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

    // ==================== EXISTING METHODS (Tetap sama) ====================

    public function createEmployee(array $data)
    {
        $employeeId = 'EMP' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $data['id'] = $employeeId;
        $data['created_at'] = now()->toISOString();
        $data['updated_at'] = now()->toISOString();

        $this->database->getReference('employees/' . $employeeId)
            ->set($data);

        return $employeeId;
    }

    public function getEmployee(string $employeeId)
    {
        return $this->database->getReference('employees/' . $employeeId)
            ->getValue();
    }

    public function getAllEmployees()
    {
        return $this->database->getReference('employees')
            ->getValue();
    }

    public function recordAttendance(string $employeeId, array $data)
    {
        $date = date('Y-m-d');
        $month = date('Y-m');

        $data['date'] = $date;
        $data['timestamp'] = now()->toISOString();

        return $this->database->getReference("attendances/$month/$employeeId/$date")
            ->set($data);
    }

    public function getAttendance(string $employeeId, string $month = null)
    {
        $month = $month ?: date('Y-m');

        return $this->database->getReference("attendances/$month/$employeeId")
            ->getValue();
    }

    public function applyLeave(array $data)
    {
        $leaveId = 'LEAVE' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $data['id'] = $leaveId;
        $data['created_at'] = now()->toISOString();
        $data['status'] = 'pending';

        $this->database->getReference('leaves/' . $leaveId)
            ->set($data);

        return $leaveId;
    }

    // ==================== NEW METHODS (Untuk structure baru) ====================

    /**
     * Get employees filtered by companyId
     */
    public function getCompanyEmployees()
    {
        $allEmployees = $this->getAllEmployees();

        // Jika companyId kosong, return semua (backward compatibility)
        if (empty($this->companyId)) {
            return $allEmployees ?: [];
        }

        // Filter by companyId
        return array_filter($allEmployees ?: [], function($emp) {
            return ($emp['companyId'] ?? '') === $this->companyId;
        });
    }

    /**
     * Get today's attendance according to new structure
     */
    public function getTodayAttendance()
    {
        $date = date('Y-m-d');
        return $this->getAttendanceByDate($date);
    }

    /**
     * Get attendance by date (new structure)
     */
    public function getAttendanceByDate($date)
    {
        $attendance = $this->database
            ->getReference("attendances/{$this->companyId}/$date")
            ->getValue();

        return $attendance ?: [];
    }

    /**
     * Get attendance by month (new structure)
     */
    public function getAttendanceByMonth($yearMonth)
    {
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
    }

    /**
     * Record check-in (new structure)
     */
    public function recordCheckIn($employeeId, array $data)
    {
        $date = date('Y-m-d');
        $time = date('H:i');

        $attendanceData = [
            'checkIn' => $time,
            'checkOut' => null,
            'location' => $data['location'] ?? 'Office',
            'notes' => $data['notes'] ?? '',
            'overtime' => 0,
            'status' => 'present',
            'timestamp' => now()->toISOString()
        ];

        // Save to attendances (new structure)
        $this->database
            ->getReference("attendances/{$this->companyId}/$date/$employeeId")
            ->set($attendanceData);

        return $attendanceData;
    }

    /**
     * Record check-out (new structure)
     */
    public function recordCheckOut($employeeId)
    {
        $date = date('Y-m-d');
        $time = date('H:i');

        $ref = $this->database
            ->getReference("attendances/{$this->companyId}/$date/$employeeId");

        if ($attendance = $ref->getValue()) {
            $attendance['checkOut'] = $time;
            $attendance['updatedAt'] = now()->toISOString();

            $ref->update($attendance);

            return $attendance;
        }

        return false;
    }

    /**
     * Get employee attendance (new structure)
     */
    public function getEmployeeAttendance($employeeId, $month = null)
    {
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

        return $employeeAttendance;
    }

    /**
     * Get all leaves (new structure)
     */
    public function getAllLeaves()
    {
        $leaves = $this->database
            ->getReference("leaveRequests/{$this->companyId}")
            ->getValue();

        return $leaves ?: [];
    }

    /**
     * Get single leave (new structure)
     */
    public function getLeave($leaveId)
    {
        return $this->database
            ->getReference("leaveRequests/{$this->companyId}/$leaveId")
            ->getValue();
    }

    /**
     * Create leave (new structure)
     */
    public function createLeave(array $data)
    {
        $leaveId = 'leave_' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);

        $leaveData = [
            'id' => $leaveId,
            'employeeId' => $data['employee_id'],
            'type' => $data['leave_type'],
            'startDate' => $data['start_date'],
            'endDate' => $data['end_date'],
            'reason' => $data['reason'],
            'contactDuringLeave' => $data['contact_during_leave'] ?? '',
            'status' => 'pending',
            'createdAt' => date('Y-m-d'),
            'appliedDate' => now()->toISOString(),
            'approvedBy' => null
        ];

        $this->database
            ->getReference("leaveRequests/{$this->companyId}/$leaveId")
            ->set($leaveData);

        return $leaveId;
    }

    /**
     * Approve leave (new structure)
     */
    public function approveLeave($leaveId, $approvedBy)
    {
        $this->database
            ->getReference("leaveRequests/{$this->companyId}/$leaveId")
            ->update([
                'status' => 'approved',
                'approvedBy' => $approvedBy,
                'approvedAt' => now()->toISOString()
            ]);
    }

    /**
     * Reject leave (new structure)
     */
    public function rejectLeave($leaveId, $rejectedBy, $reason)
    {
        $this->database
            ->getReference("leaveRequests/{$this->companyId}/$leaveId")
            ->update([
                'status' => 'rejected',
                'rejectedBy' => $rejectedBy,
                'rejectionReason' => $reason,
                'rejectedAt' => now()->toISOString()
            ]);
    }

    /**
     * Cancel leave (new structure)
     */
    public function cancelLeave($leaveId)
    {
        $this->database
            ->getReference("leaveRequests/{$this->companyId}/$leaveId")
            ->update([
                'status' => 'cancelled',
                'cancelledAt' => now()->toISOString()
            ]);
    }

    /**
     * Get employee leaves (new structure)
     */
    public function getEmployeeLeaves($employeeId)
    {
        $allLeaves = $this->getAllLeaves();

        return array_filter($allLeaves, function($leave) use ($employeeId) {
            return ($leave['employeeId'] ?? '') === $employeeId;
        });
    }

    /**
     * Get recent activities
     */
    public function getRecentActivities($limit = 10)
    {
        $activities = $this->database
            ->getReference('activities')
            ->getValue();

        if ($activities) {
            $activities = array_slice($activities, -$limit, $limit, true);
            return array_reverse($activities);
        }

        return [];
    }
}
