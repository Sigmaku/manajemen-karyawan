<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\FirebaseService;
use Symfony\Component\HttpFoundation\Response;

class CheckAttendance
{
    protected $firebase;

    public function __construct(FirebaseService $firebase)
    {
        $this->firebase = $firebase;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = session('user');
        $role = $user['role'] ?? 'employee';
        $employeeId = $user['employee_id'] ?? null;

        // Admin & Manager bebas akses
        if (in_array($role, ['admin', 'manager'])) {
            return $next($request);
        }

        // Karyawan harus check-in dulu
        if ($role === 'employee') {
            // Cek apakah sudah check-in hari ini
            $todayAttendance = $this->firebase->getTodayAttendance();
            $hasCheckedIn = isset($todayAttendance[$employeeId]);

            if (!$hasCheckedIn) {
                // Redirect ke halaman check-in
                return redirect()->route('attendance.checkin.page')
                    ->with('warning', 'You must check-in first before accessing attendance dashboard');
            }
        }

        return $next($request);
    }
}
