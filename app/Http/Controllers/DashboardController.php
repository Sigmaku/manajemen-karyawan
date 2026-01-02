<?php

namespace App\Http\Controllers;

use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    protected $firebase;

    public function __construct(FirebaseService $firebase)
    {
        $this->firebase = $firebase;
    }

    // ==================== WEB METHOD ====================

    public function index()
    {
        $db = $this->firebase->getDatabase();

        // Get counts
        $employees = $db->getReference('employees')->getValue() ?: [];
        $today = date('Y-m-d');
        $month = date('Y-m');

        $present = 0;
        $absent = 0;
        $onLeave = 0;

        $attendance = $db->getReference("attendances/$month")->getValue() ?: [];
        foreach ($attendance as $empId => $days) {
            if (isset($days[$today])) {
                $present++;
            } else {
                $absent++;
            }
        }

        // Check leaves for today
        $leaves = $db->getReference('leaves')->getValue() ?: [];
        foreach ($leaves as $leave) {
            if ($leave['status'] == 'approved' &&
                $today >= $leave['start_date'] &&
                $today <= $leave['end_date']) {
                $onLeave++;
            }
        }

        // Recent activities
        $activities = $db->getReference('activities')
            ->orderByChild('timestamp')
            ->limitToLast(10)
            ->getValue() ?: [];
        $activities = array_reverse($activities);

        return view('dashboard', [
            'totalEmployees' => count($employees),
            'presentToday' => $present,
            'absentToday' => $absent,
            'onLeave' => $onLeave,
            'recentEmployees' => array_slice($employees, -5, 5, true),
            'activities' => $activities
        ]);
    }

    // ==================== API METHODS ====================

    public function apiStats(): JsonResponse
    {
        try {
            $db = $this->firebase->getDatabase();

            $employees = $db->getReference('employees')->getValue() ?: [];
            $today = date('Y-m-d');
            $month = date('Y-m');

            $present = 0;
            $absent = 0;
            $onLeave = 0;

            $attendance = $db->getReference("attendances/$month")->getValue() ?: [];
            foreach ($attendance as $empId => $days) {
                if (isset($days[$today])) {
                    $present++;
                } else {
                    $absent++;
                }
            }

            $leaves = $db->getReference('leaves')->getValue() ?: [];
            foreach ($leaves as $leave) {
                if ($leave['status'] == 'approved' &&
                    $today >= $leave['start_date'] &&
                    $today <= $leave['end_date']) {
                    $onLeave++;
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'total_employees' => count($employees),
                    'present_today' => $present,
                    'absent_today' => $absent,
                    'on_leave' => $onLeave,
                    'date' => $today
                ],
                'message' => 'Dashboard stats retrieved'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get dashboard stats',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function apiActivities(): JsonResponse
    {
        try {
            $db = $this->firebase->getDatabase();

            $activities = $db->getReference('activities')
                ->orderByChild('timestamp')
                ->limitToLast(20)
                ->getValue() ?: [];

            $activities = array_reverse($activities);

            return response()->json([
                'success' => true,
                'data' => array_values($activities),
                'message' => 'Recent activities retrieved'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get activities',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
