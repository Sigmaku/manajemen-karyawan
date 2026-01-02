<?php

namespace App\Http\Controllers;

use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LeaveController extends Controller
{
    protected $firebase;

    public function __construct(FirebaseService $firebase)
    {
        $this->firebase = $firebase;
    }

    // ==================== WEB METHODS ====================

    public function index(Request $request)
    {
        try {
            $leaves = $this->firebase->getAllLeaves();
            $employees = $this->firebase->getCompanyEmployees();

            // Filter by status
            $status = $request->get('status');
            if ($status && $status !== 'all') {
                $leaves = array_filter($leaves, function($leave) use ($status) {
                    return ($leave['status'] ?? 'pending') === $status;
                });
            }

            // Filter by employee
            $employeeId = $request->get('employee_id');
            if ($employeeId) {
                $leaves = array_filter($leaves, function($leave) use ($employeeId) {
                    return $leave['employeeId'] === $employeeId;
                });
            }

            return view('leaves.index', compact('leaves', 'employees', 'status', 'employeeId'));

        } catch (\Exception $e) {
            return view('leaves.index', [
                'leaves' => [],
                'employees' => [],
                'error' => $e->getMessage()
            ]);
        }
    }

    public function create()
    {
        $employees = $this->firebase->getCompanyEmployees();

        $leaveTypes = [
            'annual' => 'Annual Leave',
            'sick' => 'Sick Leave',
            'personal' => 'Personal Leave',
            'maternity' => 'Maternity Leave',
            'paternity' => 'Paternity Leave',
            'unpaid' => 'Unpaid Leave'
        ];

        return view('leaves.create', compact('employees', 'leaveTypes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|string',
            'leave_type' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:500',
            'contact_during_leave' => 'required|string'
        ]);

        try {
            $employee = $this->firebase->getEmployee($request->employee_id);

            if (!$employee) {
                return back()->with('error', 'Employee not found')->withInput();
            }

            $leaveData = [
                'employee_id' => $request->employee_id,
                'leave_type' => $request->leave_type,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'reason' => $request->reason,
                'contact_during_leave' => $request->contact_during_leave
            ];

            $leaveId = $this->firebase->createLeave($leaveData);

            return redirect()->route('leaves.index')
                ->with('success', "Leave application submitted successfully! ID: $leaveId");

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to submit leave: ' . $e->getMessage())->withInput();
        }
    }

    public function show($id)
    {
        try {
            $leave = $this->firebase->getLeave($id);

            if (!$leave) {
                abort(404, 'Leave not found');
            }

            $employee = $this->firebase->getEmployee($leave['employeeId']);

            return view('leaves.show', compact('leave', 'employee'));

        } catch (\Exception $e) {
            return redirect()->route('leaves.index')
                ->with('error', 'Failed to load leave details: ' . $e->getMessage());
        }
    }

    public function approve($id)
    {
        try {
            $leave = $this->firebase->getLeave($id);

            if (!$leave) {
                return back()->with('error', 'Leave not found');
            }

            $approvedBy = session('user')['name'] ?? 'Admin';
            $this->firebase->approveLeave($id, $approvedBy);

            return redirect()->back()
                ->with('success', "Leave approved by $approvedBy");

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to approve leave: ' . $e->getMessage());
        }
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500'
        ]);

        try {
            $leave = $this->firebase->getLeave($id);

            if (!$leave) {
                return back()->with('error', 'Leave not found');
            }

            $rejectedBy = session('user')['name'] ?? 'Admin';
            $this->firebase->rejectLeave($id, $rejectedBy, $request->rejection_reason);

            return redirect()->back()
                ->with('success', 'Leave rejected successfully');

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to reject leave: ' . $e->getMessage());
        }
    }

    public function cancel($id)
    {
        try {
            $leave = $this->firebase->getLeave($id);

            if (!$leave) {
                return back()->with('error', 'Leave not found');
            }

            $this->firebase->cancelLeave($id);

            return redirect()->back()
                ->with('success', 'Leave cancelled successfully');

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to cancel leave: ' . $e->getMessage());
        }
    }

    public function calendar()
    {
        try {
            $leaves = $this->firebase->getAllLeaves();
            $employees = $this->firebase->getCompanyEmployees();

            $calendarEvents = [];
            foreach ($leaves as $leaveId => $leave) {
                if (($leave['status'] ?? 'pending') === 'approved') {
                    $employee = $employees[$leave['employeeId']] ?? [];
                    $color = $this->getLeaveColor($leave['type'] ?? 'annual');

                    $calendarEvents[] = [
                        'id' => $leaveId,
                        'title' => $employee['name'] ?? 'Unknown',
                        'start' => $leave['startDate'],
                        'end' => date('Y-m-d', strtotime($leave['endDate'] . ' +1 day')),
                        'color' => $color,
                        'extendedProps' => [
                            'type' => $leave['type'] ?? 'annual',
                            'employee' => $employee['name'] ?? 'Unknown',
                            'department' => $employee['department'] ?? '-',
                            'status' => $leave['status'] ?? 'pending'
                        ]
                    ];
                }
            }

            return view('leaves.calendar', compact('calendarEvents'));

        } catch (\Exception $e) {
            return view('leaves.calendar', [
                'calendarEvents' => [],
                'error' => $e->getMessage()
            ]);
        }
    }

    public function myLeaves()
    {
        try {
            $user = session('user');
            $employeeId = $user['employee_id'] ?? null;

            if (!$employeeId) {
                return redirect()->route('leaves.index')
                    ->with('error', 'Employee ID not found in session');
            }

            $leaves = $this->firebase->getEmployeeLeaves($employeeId);
            $employee = $this->firebase->getEmployee($employeeId);

            return view('leaves.my-leaves', compact('leaves', 'employee'));

        } catch (\Exception $e) {
            return view('leaves.my-leaves', [
                'leaves' => [],
                'employee' => [],
                'error' => $e->getMessage()
            ]);
        }
    }

    private function getLeaveColor($type)
    {
        $colors = [
            'annual' => '#27ae60',    // Green
            'sick' => '#e74c3c',      // Red
            'personal' => '#3498db',  // Blue
            'maternity' => '#9b59b6', // Purple
            'paternity' => '#2ecc71', // Light Green
            'unpaid' => '#f39c12'     // Orange
        ];

        return $colors[$type] ?? '#95a5a6'; // Grey default
    }

    // ==================== API METHODS ====================

    public function apiEmployeeLeaves($employeeId): JsonResponse
    {
        try {
            $employee = $this->firebase->getEmployee($employeeId);

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee not found'
                ], 404);
            }

            $leaves = $this->firebase->getEmployeeLeaves($employeeId);

            return response()->json([
                'success' => true,
                'data' => [
                    'employee' => $employee,
                    'leaves' => $leaves,
                    'total' => count($leaves)
                ],
                'message' => 'Employee leaves retrieved'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get employee leaves',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
