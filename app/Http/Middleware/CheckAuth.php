<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckAuth
{
    public function handle(Request $request, Closure $next)
    {
        // Routes yang boleh diakses tanpa login
        $publicRoutes = [
            'login',
            'test-route',
            'test-firebase-connection',
            'api/*' // API routes juga boleh tanpa auth untuk sekarang
        ];

        // Check jika route public
        foreach ($publicRoutes as $route) {
            if ($request->is($route) || $request->is($route . '/*')) {
                return $next($request);
            }
        }

        // Check jika user belum login
        if (!session('user')) {
            return redirect()->route('login')->with('error', 'Please login first.');
        }

        return $next($request);
    }
}
