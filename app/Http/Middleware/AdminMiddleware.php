<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = session('user');

        if (!$user) {
            return redirect()->route('login')->with('error', 'Please login first.');
        }

        if ($user['role'] !== 'admin') {
            return redirect()->route('dashboard')
                ->with('error', 'Access denied. Admin only.');
        }

        return $next($request);
    }
}
