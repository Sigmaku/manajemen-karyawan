<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Kreait\Firebase\Auth\SignIn\FailedToSignIn;
use Illuminate\Support\Facades\Log;

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
            $firebase = app(\App\Services\FirebaseService::class);

            // 1. Try to authenticate with Firebase Auth
            try {
                $signInResult = $firebase->getAuth()->signInWithEmailAndPassword(
                    $credentials['email'],
                    $credentials['password']
                );

                $uid = $signInResult->firebaseUserId();

                // 2. Get user data from database
                $userRef = $firebase->getDatabase()->getReference('users/' . $uid);
                $userData = $userRef->getValue();

                if ($userData) {
                    $authenticatedUser = [
                        'uid' => $uid,
                        'email' => $userData['email'],
                        'name' => $userData['name'] ?? 'User',
                        'role' => $userData['role'] ?? 'employee',
                        'employee_id' => $userData['employee_id'] ?? null,
                        'companyId' => $userData['companyId'] ?? null
                    ];

                    session(['user' => $authenticatedUser]);

                    // Log login activity
                    $firebase->getDatabase()
                        ->getReference('login_logs/' . $uid . '/' . time())
                        ->set([
                            'email' => $credentials['email'],
                            'ip' => $request->ip(),
                            'user_agent' => $request->header('User-Agent'),
                            'timestamp' => now()->toISOString()
                        ]);

                    return redirect()->route('dashboard')->with('success', 'Login successful!');
                }

            } catch (FailedToSignIn $e) {
                // Firebase auth failed, try demo users as fallback
                Log::warning('Firebase auth failed: ' . $e->getMessage());
            }

            // Fallback to demo users (for testing or admin accounts)
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
            Log::error('Login error: ' . $e->getMessage());
            return back()->withErrors([
                'email' => 'Login error. Please try again.',
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
