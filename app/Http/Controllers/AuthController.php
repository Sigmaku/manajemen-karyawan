<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        // Jika sudah login, redirect ke dashboard
        if (session('user')) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        try {
            // Use Firebase Service for authentication
            $firebase = app(\App\Services\FirebaseService::class);

            // For demo - in real app, use Firebase Auth
            // Get all users from Firebase
            $users = $firebase->getAllUsers();

            // Check if user exists
            $authenticatedUser = null;
            foreach ($users as $uid => $user) {
                if (isset($user['email']) && $user['email'] === $request->email) {
                    // In real app, verify password with Firebase Auth
                    // For demo, we'll accept any password
                    $authenticatedUser = [
                        'uid' => $uid,
                        'email' => $user['email'],
                        'name' => $user['name'] ?? 'User',
                        'role' => $user['role'] ?? 'employee',
                        'employee_id' => $user['employee_id'] ?? null
                    ];
                    break;
                }
            }

            if ($authenticatedUser) {
                // Store user in session
                session(['user' => $authenticatedUser]);

                return redirect()->route('dashboard')->with('success', 'Login successful!');
            }

            // Fallback to demo users (for testing)
            $demoUsers = [
                'admin@company.com' => [
                    'uid' => 'admin_uid_123',
                    'name' => 'Administrator',
                    'email' => 'admin@company.com',
                    'role' => 'admin',
                    'employee_id' => 'emp_001',
                    'password' => 'password123'
                ],
                'manager@company.com' => [
                    'uid' => 'manager_uid_456',
                    'name' => 'Manager',
                    'email' => 'manager@company.com',
                    'role' => 'manager',
                    'employee_id' => 'emp_002',
                    'password' => 'password123'
                ],
                'employee@company.com' => [
                    'uid' => 'employee_uid_789',
                    'name' => 'John Employee',
                    'email' => 'employee@company.com',
                    'role' => 'employee',
                    'employee_id' => 'emp_003',
                    'password' => 'password123'
                ],
                'najwan@company.com' => [
                    'uid' => 'user_najwan',
                    'name' => 'NajwanCF',
                    'email' => 'najwan@company.com',
                    'role' => 'employee',
                    'employee_id' => '-Ogk_zl5rnzcdBcV7Jav',
                    'password' => 'password123'
                ],
                'budi@company.com' => [
                    'uid' => 'user_budi',
                    'name' => 'Budi Setiawan',
                    'email' => 'budi@company.com',
                    'role' => 'employee',
                    'employee_id' => 'emp_001',
                    'password' => 'password123'
                ]
            ];

            if (isset($demoUsers[$request->email]) && $demoUsers[$request->email]['password'] === $request->password) {
                $user = $demoUsers[$request->email];
                unset($user['password']);

                session(['user' => $user]);

                return redirect()->route('dashboard')->with('success', 'Login successful!');
            }

            return back()->withErrors([
                'email' => 'Invalid credentials.',
            ]);

        } catch (\Exception $e) {
            return back()->withErrors([
                'email' => 'Login error: ' . $e->getMessage(),
            ]);
        }
    }

    public function logout(Request $request)
    {
        // Clear all session data
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')->with('success', 'Logged out successfully!');
    }
}
