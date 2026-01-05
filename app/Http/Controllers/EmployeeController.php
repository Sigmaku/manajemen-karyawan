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

                // Tambah field untuk menandai apakah punya akun login
                if (!isset($employee['has_account'])) {
                    $employee['has_account'] = isset($employee['uid']) ? true : false;
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

    // STORE - Simpan employee baru DENGAN AUTO CREATE ACCOUNT
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'required|string',
            'department' => 'required|string',
            'position' => 'required|string',
            'hire_date' => 'required|date',
            'create_account' => 'nullable|boolean',
            'password_option' => 'required_if:create_account,1|in:random,custom',
            'password' => 'required_if:password_option,custom|min:8|confirmed',
            'random_password' => 'required_if:password_option,random'
        ]);

        try {
            // Check if email already exists
            $existingUser = $this->firebase->getUserByEmail($request->email);
            if ($existingUser) {
                return back()->with('error', 'Email already registered as user!')
                    ->withInput();
            }

            // Check if employee email exists
            $allEmployees = $this->firebase->getAllEmployees();
            foreach ($allEmployees as $emp) {
                if (isset($emp['email']) && $emp['email'] === $request->email) {
                    return back()->with('error', 'Email already used by another employee!')
                        ->withInput();
                }
            }

            // 1. Create employee data
            $employeeData = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'department' => $request->department,
                'position' => $request->position,
                'hire_date' => $request->hire_date,
                'address' => $request->address ?? '',
                'salary' => $request->salary ?? 0,
                'status' => 'active',
                'created_at' => now()->toISOString(),
                'updated_at' => now()->toISOString()
            ];

            // 2. Save employee to database (ini akan generate employeeId)
            $employeeId = $this->firebase->createEmployee($employeeData);

            // 3. Create login account if requested
            $credentials = null;
            if ($request->has('create_account') && $request->create_account == '1') {
                // Determine password
                $password = '';
                if ($request->password_option === 'custom') {
                    $password = $request->password;
                } else {
                    $password = $request->random_password ?? $this->firebase->generateRandomPassword();
                }

                // Create auth user
                $userData = [
                    'name' => $request->name,
                    'employee_id' => $employeeId
                ];

                $uid = $this->firebase->createAuthUser($request->email, $password, $userData);

                // Update employee with uid
                $this->firebase->getDatabase()
                    ->getReference('employees/' . $employeeId)
                    ->update([
                        'uid' => $uid,
                        'has_account' => true,
                        'password_set_by_admin' => true,
                        'password_set_date' => now()->toISOString(),
                        'updated_at' => now()->toISOString()
                    ]);

                $credentials = [
                    'email' => $request->email,
                    'password' => $password,
                    'employee_id' => $employeeId,
                    'password_type' => $request->password_option === 'custom' ? 'custom' : 'random'
                ];

                return redirect()->route('employees.index')
                    ->with('success', "Employee created with login account!")
                    ->with('credentials', $credentials);
            } else {
                // Tidak buat akun, hanya employee record
                return redirect()->route('employees.index')
                    ->with('success', "Employee created successfully! ID: $employeeId")
                    ->with('info', 'No login account created. You can create it later from employee details.');
            }

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

            // Check if has account
            $hasAccount = isset($employee['uid']) && !empty($employee['uid']);

            // Get this month attendance only
            $attendance = $this->firebase->getEmployeeAttendance($id, date('Y-m'));

            // Calculate stats
            $totalDays = count($attendance);
            $presentDays = 0;
            $totalHours = 0;

            foreach ($attendance as $record) {
                if (isset($record['checkIn'])) {
                    $presentDays++;
                }
                $totalHours += $record['hoursWorked'] ?? 0;
            }

            $attendanceRate = $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 1) : 0;

            return view('employees.show', compact('employee', 'attendance', 'totalDays', 'presentDays', 'totalHours', 'attendanceRate', 'hasAccount'));

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
            // Get current employee data
            $currentEmployee = $this->firebase->getEmployee($id);
            if (!$currentEmployee) {
                abort(404, 'Employee not found');
            }

            // Check if email changed and if new email already exists
            if ($currentEmployee['email'] !== $request->email) {
                $existingUser = $this->firebase->getUserByEmail($request->email);
                if ($existingUser) {
                    return back()->with('error', 'Email already registered as user!')
                        ->withInput();
                }

                // Update email in auth if employee has account
                if (isset($currentEmployee['uid'])) {
                    $this->firebase->updateUserEmail($currentEmployee['uid'], $request->email);
                }
            }

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

    // CREATE ACCOUNT - Create login account for existing employee
    public function createAccount($id)
    {
        try {
            $employee = $this->firebase->getEmployee($id);
            if (!$employee) {
                abort(404, 'Employee not found');
            }

            // Check if already has account
            if (isset($employee['uid']) && !empty($employee['uid'])) {
                return redirect()->route('employees.show', $id)
                    ->with('warning', 'Employee already has login account!');
            }

            // Check if email already used
            $existingUser = $this->firebase->getUserByEmail($employee['email']);
            if ($existingUser) {
                return redirect()->route('employees.show', $id)
                    ->with('error', 'Email already registered as user!');
            }

            // Generate password
            $password = $this->firebase->generateRandomPassword();

            // Create auth user
            $userData = [
                'name' => $employee['name'],
                'employee_id' => $id
            ];

            $uid = $this->firebase->createAuthUser($employee['email'], $password, $userData);

            // Update employee with uid
            $this->firebase->getDatabase()
                ->getReference('employees/' . $id)
                ->update([
                    'uid' => $uid,
                    'has_account' => true,
                    'updated_at' => now()->toISOString()
                ]);

            return redirect()->route('employees.show', $id)
                ->with('success', 'Login account created successfully!')
                ->with('credentials', [
                    'email' => $employee['email'],
                    'password' => $password,
                    'employee_id' => $id
                ]);

        } catch (\Exception $e) {
            return redirect()->route('employees.show', $id)
                ->with('error', 'Failed to create account: ' . $e->getMessage());
        }
    }

    // UPDATE PASSWORD - Update employee password (AJAX)
    public function updatePassword(Request $request, $id)
    {
        $request->validate([
            'password' => 'required|min:8',
            'password_type' => 'required|in:random,custom'
        ]);

        try {
            $employee = $this->firebase->getEmployee($id);
            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee not found'
                ], 404);
            }

            // Check if has account
            if (!isset($employee['uid']) || empty($employee['uid'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee does not have login account!'
                ], 400);
            }

            // Update password in Firebase Auth
            $this->firebase->getAuth()->changeUserPassword($employee['uid'], $request->password);

            // Update employee record
            $this->firebase->getDatabase()
                ->getReference('employees/' . $id)
                ->update([
                    'password_set_by_admin' => true,
                    'password_set_date' => now()->toISOString(),
                    'password_type' => $request->password_type,
                    'password_updated_at' => now()->toISOString(),
                    'updated_at' => now()->toISOString()
                ]);

            // Log password change
            $this->firebase->getDatabase()
                ->getReference('password_changes/' . $employee['uid'] . '/' . time())
                ->set([
                    'changed_by' => session('user')['name'] ?? 'Admin',
                    'changed_at' => now()->toISOString(),
                    'type' => $request->password_type,
                    'method' => 'admin_manual'
                ]);

            // Also update user record
            $this->firebase->getDatabase()
                ->getReference('users/' . $employee['uid'])
                ->update([
                    'password_updated_at' => now()->toISOString(),
                    'updated_at' => now()->toISOString()
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Password updated successfully!',
                'credentials' => [
                    'email' => $employee['email'],
                    'password' => $request->password,
                    'employee_id' => $id,
                    'password_type' => $request->password_type
                ]
            ]);

        } catch (\Kreait\Firebase\Auth\FailedToChangePassword $e) {
            Log::error('Firebase password change error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update password: ' . $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            Log::error('Update password error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update password: ' . $e->getMessage()
            ], 500);
        }
    }

    // GET PASSWORD HISTORY - Get employee password change history
    public function getPasswordHistory($id)
    {
        try {
            $employee = $this->firebase->getEmployee($id);
            if (!$employee || !isset($employee['uid'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee not found or no account'
                ], 404);
            }

            $history = $this->firebase->getDatabase()
                ->getReference('password_changes/' . $employee['uid'])
                ->getValue();

            return response()->json([
                'success' => true,
                'data' => $history ?: [],
                'message' => 'Password history retrieved'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get password history'
            ], 500);
        }
    }

    // FORCE PASSWORD CHANGE - Force employee to change password on next login
    public function forcePasswordChange($id)
    {
        try {
            $employee = $this->firebase->getEmployee($id);
            if (!$employee || !isset($employee['uid'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee not found or no account'
                ], 404);
            }

            // Generate temporary password
            $tempPassword = $this->firebase->generateRandomPassword();

            // Update password in Firebase Auth
            $this->firebase->getAuth()->changeUserPassword($employee['uid'], $tempPassword);

            // Set flag for forced password change
            $this->firebase->getDatabase()
                ->getReference('employees/' . $id)
                ->update([
                    'force_password_change' => true,
                    'temp_password' => $tempPassword,
                    'force_change_date' => now()->toISOString(),
                    'updated_at' => now()->toISOString()
                ]);

            // Log the action
            $this->firebase->getDatabase()
                ->getReference('password_changes/' . $employee['uid'] . '/' . time())
                ->set([
                    'changed_by' => session('user')['name'] ?? 'Admin',
                    'changed_at' => now()->toISOString(),
                    'type' => 'forced_reset',
                    'method' => 'admin_force',
                    'temporary_password' => $tempPassword
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Password reset forced. Employee must change password on next login.',
                'temporary_password' => $tempPassword
            ]);

        } catch (\Exception $e) {
            Log::error('Force password change error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to force password change'
            ], 500);
        }
    }

    // CHECK PASSWORD POLICY - Validate password against policy
    public function checkPasswordPolicy(Request $request)
    {
        $password = $request->input('password', '');

        $errors = [];

        // Check minimum length
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }

        // Check for uppercase
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }

        // Check for lowercase
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }

        // Check for numbers
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }

        // Check for special characters (optional)
        if (!preg_match('/[!@#$%^&*()\-_=+{};:,<.>]/', $password)) {
            // This is a warning, not an error
            $warning = 'Consider adding special characters for stronger security';
        }

        // Calculate strength score
        $strength = 0;
        if (strlen($password) >= 8) $strength += 20;
        if (strlen($password) >= 12) $strength += 10;
        if (preg_match('/[A-Z]/', $password)) $strength += 20;
        if (preg_match('/[a-z]/', $password)) $strength += 20;
        if (preg_match('/[0-9]/', $password)) $strength += 20;
        if (preg_match('/[!@#$%^&*()\-_=+{};:,<.>]/', $password)) $strength += 10;

        $strengthLevel = 'weak';
        if ($strength >= 70) $strengthLevel = 'strong';
        elseif ($strength >= 50) $strengthLevel = 'medium';

        return response()->json([
            'success' => true,
            'valid' => empty($errors),
            'errors' => $errors,
            'warning' => $warning ?? null,
            'strength' => $strength,
            'strength_level' => $strengthLevel,
            'requirements' => [
                'min_length' => 8,
                'needs_uppercase' => true,
                'needs_lowercase' => true,
                'needs_number' => true,
                'needs_special' => false
            ]
        ]);
    }

    // RESET PASSWORD - Reset employee password
    public function resetPassword($id)
    {
        try {
            $employee = $this->firebase->getEmployee($id);
            if (!$employee) {
                abort(404, 'Employee not found');
            }

            // Check if has account
            if (!isset($employee['uid']) || empty($employee['uid'])) {
                return redirect()->route('employees.show', $id)
                    ->with('error', 'Employee does not have login account!');
            }

            // Reset password
            $newPassword = $this->firebase->resetUserPassword($employee['uid']);

            return redirect()->route('employees.show', $id)
                ->with('success', 'Password reset successfully!')
                ->with('credentials', [
                    'email' => $employee['email'],
                    'password' => $newPassword,
                    'employee_id' => $id
                ]);

        } catch (\Exception $e) {
            return redirect()->route('employees.show', $id)
                ->with('error', 'Failed to reset password: ' . $e->getMessage());
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

