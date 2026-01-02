<?php

namespace App\Http\Controllers;

use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AdminController extends Controller
{
    protected $firebase;

    public function __construct(FirebaseService $firebase)
    {
        $this->firebase = $firebase;
    }

    // ==================== DASHBOARD ====================
    public function dashboard()
    {
        $db = $this->firebase->getDatabase();

        $stats = [
            'total_employees' => count($db->getReference('employees')->getValue() ?: []),
            'active_employees' => count(array_filter($db->getReference('employees')->getValue() ?: [],
                fn($emp) => $emp['status'] === 'active'
            )),
            'pending_leaves' => count(array_filter($db->getReference('leaves')->getValue() ?: [],
                fn($leave) => $leave['status'] === 'pending'
            )),
            'today_attendance' => $this->getTodayAttendanceCount(),
            'department_stats' => $this->getDepartmentStats(),
            'recent_activities' => array_slice($db->getReference('activities')->getValue() ?: [], -10, 10, true)
        ];

        return view('admin.dashboard', compact('stats'));
    }

    // ==================== USER MANAGEMENT ====================
    public function users()
    {
        $db = $this->firebase->getDatabase();
        $users = $db->getReference('users')->getValue() ?: [];

        return view('admin.users', compact('users'));
    }

    public function createUser(Request $request)
    {
        // Create new system user
    }

    public function resetPassword($userId)
    {
        // Reset user password
    }

    // ==================== SYSTEM LOGS ====================
    public function logs(Request $request)
    {
        $db = $this->firebase->getDatabase();

        $type = $request->get('type', 'all');
        $date = $request->get('date', date('Y-m-d'));

        $logs = $db->getReference('system_logs')->getValue() ?: [];

        // Filter logs
        if ($type !== 'all') {
            $logs = array_filter($logs, fn($log) => $log['type'] === $type);
        }

        return view('admin.logs', compact('logs', 'type', 'date'));
    }

    // ==================== BACKUP & RESTORE ====================
    public function backup()
    {
        $db = $this->firebase->getDatabase();
        $backups = $db->getReference('backups')
            ->orderByChild('timestamp')
            ->limitToLast(10)
            ->getValue() ?: [];

        return view('admin.backup', compact('backups'));
    }

    public function createBackup(Request $request)
    {
        $db = $this->firebase->getDatabase();

        $backupData = [
            'employees' => $db->getReference('employees')->getValue() ?: [],
            'attendances' => $db->getReference('attendances')->getValue() ?: [],
            'leaves' => $db->getReference('leaves')->getValue() ?: [],
            'settings' => $db->getReference('settings')->getValue() ?: [],
            'timestamp' => now()->toISOString(),
            'created_by' => session('user')['name'] ?? 'Admin'
        ];

        $backupId = 'backup_' . date('Ymd_His');
        $db->getReference("backups/$backupId")->set($backupData);

        return redirect()->route('admin.backup')
            ->with('success', "Backup created: $backupId");
    }

    public function restoreBackup($backupId)
    {
        // Restore from backup
    }

    // ==================== SYSTEM SETTINGS ====================
    public function systemSettings()
    {
        $db = $this->firebase->getDatabase();
        $settings = $db->getReference('system_settings')->getValue() ?: [
            'company_name' => 'Your Company',
            'work_days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
            'work_hours' => ['start' => '08:00', 'end' => '17:00'],
            'holidays' => [],
            'leave_types' => [
                'annual' => 'Annual Leave',
                'sick' => 'Sick Leave',
                'personal' => 'Personal Leave'
            ]
        ];

        return view('admin.settings', compact('settings'));
    }

    public function updateSystemSettings(Request $request)
    {
        // Update system settings
    }

    // ==================== HELPER METHODS ====================
    private function getTodayAttendanceCount()
    {
        $db = $this->firebase->getDatabase();
        $today = date('Y-m-d');
        $month = date('Y-m');

        $attendance = $db->getReference("attendances/$month")->getValue() ?: [];

        $present = 0;
        foreach ($attendance as $empAttendance) {
            if (isset($empAttendance[$today])) {
                $present++;
            }
        }

        return $present;
    }

    private function getDepartmentStats()
    {
        $db = $this->firebase->getDatabase();
        $employees = $db->getReference('employees')->getValue() ?: [];

        $stats = [];
        foreach ($employees as $employee) {
            $dept = $employee['department'] ?? 'Other';
            if (!isset($stats[$dept])) {
                $stats[$dept] = 0;
            }
            $stats[$dept]++;
        }

        return $stats;
    }
}
