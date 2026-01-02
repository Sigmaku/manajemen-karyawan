<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EmployeeController extends Controller
{
    protected $firebase;

    public function __construct(FirebaseService $firebase)
    {
        $this->firebase = $firebase;
    }

    // ==================== WEB METHODS ====================

    // INDEX - List semua employee
    public function index()
    {
        try {
            // Coba get company employees dulu
            if (method_exists($this->firebase, 'getCompanyEmployees')) {
                $employees = $this->firebase->getCompanyEmployees();
            } else {
                $employees = $this->firebase->getAllEmployees() ?: [];
            }

            // Clean up data - tambah default values jika field tidak ada
            foreach ($employees as $id => &$employee) {
                // Tambah field status jika tidak ada
                if (!isset($employee['status'])) {
                    $employee['status'] = 'active';
                }

                // Tambah field department jika tidak ada
                if (!isset($employee['department'])) {
                    $employee['department'] = 'General';
                }

                // Tambah field position jika tidak ada
                if (!isset($employee['position'])) {
                    $employee['position'] = 'Staff';
                }
            }

            return view('employees.index', compact('employees'));

        } catch (\Exception $e) {
            Log::error('EmployeeController index error: ' . $e->getMessage());

            return view('employees.index', [
                'employees' => [],
                'error' => 'Failed to load employees: ' . $e->getMessage()
            ]);
        }
    }

    // CREATE - Form tambah employee
    public function create()
    {
        $departments = ['IT', 'HR', 'Finance', 'Marketing', 'Operations', 'Sales'];
        $positions = ['Manager', 'Supervisor', 'Staff', 'Intern'];
        return view('employees.create', compact('departments', 'positions'));
    }

    // STORE - Simpan employee baru
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            'department' => 'required',
            'position' => 'required'
        ]);

        $employeeData = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone ?? '',
            'department' => $request->department,
            'position' => $request->position,
            'joinDate' => $request->hire_date ?? date('Y-m-d'),
            'address' => $request->address ?? '',
            'status' => 'active',
            'role' => 'employee'
        ];

        $employeeId = $this->firebase->createEmployee($employeeData);

        return redirect()->route('employees.index')
            ->with('success', "Employee created: $employeeId");
        }

        // SHOW - Detail employee
        public function show($id)
        {
            $employee = $this->firebase->getEmployee($id);
            if (!$employee) abort(404);

            // Get attendance history
            $db = $this->firebase->getDatabase();
            $attendanceRef = $db->getReference('attendances')
                ->orderByChild($id)
                ->limitToLast(30)
                ->getValue();

            return view('employees.show', compact('employee', 'attendanceRef'));
        }

    // EDIT - Form edit employee
    public function edit($id)
    {
        $employee = $this->firebase->getEmployee($id);
        if (!$employee) abort(404);

        $departments = ['IT', 'HR', 'Finance', 'Marketing', 'Operations', 'Sales'];
        $positions = ['Manager', 'Supervisor', 'Staff', 'Intern'];

        return view('employees.edit', compact('employee', 'departments', 'positions'));
    }

    // UPDATE - Update employee
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'required|string',
            'department' => 'required|string',
            'position' => 'required|string',
            'status' => 'required|in:active,inactive'
        ]);

        $employeeData = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'department' => $request->department,
            'position' => $request->position,
            'status' => $request->status,
            'address' => $request->address,
            'updated_at' => now()->toISOString()
        ];

        $this->firebase->getDatabase()
            ->getReference("employees/$id")
            ->update($employeeData);

        return redirect()->route('employees.show', $id)
            ->with('success', 'Employee updated successfully!');
    }

    // DESTROY - Delete employee
    public function destroy($id)
    {
        $this->firebase->getDatabase()
            ->getReference("employees/$id")
            ->remove();

        return redirect()->route('employees.index')
            ->with('success', 'Employee deleted successfully!');
    }

    // ==================== API METHODS ====================

    // API: Get all employees
    public function apiIndex(): JsonResponse
    {
        try {
            $employees = $this->firebase->getAllEmployees();

            return response()->json([
                'success' => true,
                'data' => $employees,
                'count' => count($employees),
                'message' => 'Employees retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve employees',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // API: Get single employee
    public function apiShow($id): JsonResponse
    {
        try {
            $employee = $this->firebase->getEmployee($id);

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $employee,
                'message' => 'Employee retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve employee',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // API: Get employee attendance
    public function apiAttendance($id): JsonResponse
    {
        try {
            $month = date('Y-m');
            $db = $this->firebase->getDatabase();
            $attendance = $db->getReference("attendances/$month/$id")->getValue() ?: [];

            $employee = $this->firebase->getEmployee($id);

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'employee' => $employee,
                    'attendance' => $attendance,
                    'month' => $month,
                    'total_days' => count($attendance),
                    'present_days' => count(array_filter($attendance, function($day) {
                        return isset($day['check_in']);
                    }))
                ],
                'message' => 'Attendance retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve attendance',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // API: Create employee
    public function apiStore(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email',
                'department' => 'required|string',
                'position' => 'required|string'
            ]);

            $employeeData = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone ?? '',
                'department' => $request->department,
                'position' => $request->position,
                'hire_date' => $request->hire_date ?? date('Y-m-d'),
                'salary' => $request->salary ?? 0,
                'address' => $request->address ?? '',
                'status' => 'active',
                'created_at' => now()->toISOString(),
                'updated_at' => now()->toISOString()
            ];

            $employeeId = $this->firebase->createEmployee($employeeData);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $employeeId,
                    ...$employeeData
                ],
                'message' => 'Employee created successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create employee',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
