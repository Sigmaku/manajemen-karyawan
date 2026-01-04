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
            // Get company employees
            $employees = $this->firebase->getCompanyEmployees();

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
        $departments = ['IT', 'HR', 'Finance', 'Marketing', 'Operations', 'Sales', 'General'];
        $positions = ['Manager', 'Supervisor', 'Staff', 'Intern', 'Developer', 'Designer'];
        return view('employees.create', compact('departments', 'positions'));
    }

    // STORE - Simpan employee baru
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'required|string',
            'department' => 'required|string',
            'position' => 'required|string',
            'hire_date' => 'required|date'
        ]);

        try {
            // Check if email already exists in Firebase
            $allEmployees = $this->firebase->getAllEmployees();
            foreach ($allEmployees as $emp) {
                if (isset($emp['email']) && $emp['email'] === $request->email) {
                    return back()->with('error', 'Email already exists!')
                        ->withInput();
                }
            }

            $employeeData = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'department' => $request->department,
                'position' => $request->position,
                'hire_date' => $request->hire_date,
                'address' => $request->address ?? '',
                'status' => 'active',
                'salary' => $request->salary ?? 0,
                'created_at' => now()->toISOString(),
                'updated_at' => now()->toISOString()
            ];

            $employeeId = $this->firebase->createEmployee($employeeData);

            return redirect()->route('employees.index')
                ->with('success', "Employee created successfully! ID: $employeeId");

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to create employee: ' . $e->getMessage())
                ->withInput();
        }
    }

    // SHOW - Detail employee
    public function show($id)
    {
        try {
            $employee = $this->firebase->getEmployee($id);
            if (!$employee) {
                abort(404, 'Employee not found');
            }

            // Get attendance history
            $attendance = $this->firebase->getEmployeeAttendance($id, date('Y-m'));

            return view('employees.show', compact('employee', 'attendance'));

        } catch (\Exception $e) {
            return redirect()->route('employees.index')
                ->with('error', 'Failed to load employee: ' . $e->getMessage());
        }
    }

    // EDIT - Form edit employee
    public function edit($id)
    {
        try {
            $employee = $this->firebase->getEmployee($id);
            if (!$employee) {
                abort(404, 'Employee not found');
            }

            $departments = ['IT', 'HR', 'Finance', 'Marketing', 'Operations', 'Sales', 'General'];
            $positions = ['Manager', 'Supervisor', 'Staff', 'Intern', 'Developer', 'Designer'];

            return view('employees.edit', compact('employee', 'departments', 'positions'));

        } catch (\Exception $e) {
            return redirect()->route('employees.index')
                ->with('error', 'Failed to load employee: ' . $e->getMessage());
        }
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

        try {
            $employeeData = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'department' => $request->department,
                'position' => $request->position,
                'status' => $request->status,
                'address' => $request->address ?? '',
                'updated_at' => now()->toISOString()
            ];

            $this->firebase->updateEmployee($id, $employeeData);

            return redirect()->route('employees.show', $id)
                ->with('success', 'Employee updated successfully!');

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update employee: ' . $e->getMessage())
                ->withInput();
        }
    }

    // DESTROY - Delete employee
    public function destroy($id)
    {
        try {
            $this->firebase->deleteEmployee($id);

            return redirect()->route('employees.index')
                ->with('success', 'Employee deleted successfully!');

        } catch (\Exception $e) {
            return redirect()->route('employees.index')
                ->with('error', 'Failed to delete employee: ' . $e->getMessage());
        }
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
            $attendance = $this->firebase->getEmployeeAttendance($id, $month);

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
                        return isset($day['checkIn']);
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
}
