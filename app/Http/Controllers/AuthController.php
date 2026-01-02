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

        // Hardcoded users untuk development
        $users = [
            'admin@example.com' => [
                'id' => 1,
                'name' => 'Administrator',
                'email' => 'admin@example.com',
                'role' => 'admin',
                'employee_id' => 'EMP001',
                'password' => 'password123'
            ],
            'manager@example.com' => [
                'id' => 2,
                'name' => 'Manager',
                'email' => 'manager@example.com',
                'role' => 'manager',
                'employee_id' => 'EMP002',
                'password' => 'password123'
            ],
            'employee@example.com' => [
                'id' => 3,
                'name' => 'John Employee',
                'email' => 'employee@example.com',
                'role' => 'employee',
                'employee_id' => 'EMP003',
                'password' => 'password123'
            ]
        ];

        if (isset($users[$request->email]) && $users[$request->email]['password'] === $request->password) {
            $user = $users[$request->email];
            unset($user['password']); // Jangan simpan password di session

            session(['user' => $user]);

            return redirect()->route('dashboard')->with('success', 'Login successful!');
        }

        return back()->withErrors([
            'email' => 'Invalid credentials.',
        ]);
    }

    public function logout(Request $request)
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')->with('success', 'Logged out successfully!');
    }
}
