<?php

namespace App\Http\Controllers;

use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class LeaveController extends Controller
{
    protected $firebase;

    public function __construct(FirebaseService $firebase)
    {
        $this->firebase = $firebase;
    }

    // ==================== WEB METHODS ====================

    /**
     * Halaman utama manajemen cuti
     * Admin & Manager → lihat semua
     * Employee → redirect ke My Leaves
     */
    public function index(Request $request)
    {
        $user = session('user');
        $role = $user['role'] ?? 'employee';

        // Employee langsung ke My Leaves
        if ($role === 'employee') {
            return redirect()->route('leaves.my');
        }

        // Hanya admin & manager yang boleh lanjut
        try {
            $rawLeaves = $this->firebase->getAllLeaves() ?? [];

            $leavesCollection = collect($rawLeaves)->map(function ($leave, $leaveId) {
                return (object) [
                    'id'                => $leaveId,
                    'employeeId'        => $leave['employeeId'] ?? null,
                    'leave_type'        => $leave['type'] ?? 'annual',
                    'start_date'        => $leave['startDate'] ?? null,
                    'end_date'          => $leave['endDate'] ?? null,
                    'reason'            => $leave['reason'] ?? '-',
                    'status'            => $leave['status'] ?? 'pending',
                    'created_at'        => \Carbon\Carbon::parse($leave['createdAt'] ?? now()),
                    'approved_by'       => $leave['approvedBy'] ?? null,
                    'rejection_reason'  => $leave['rejectionReason'] ?? null,
                ];
            });

            // Filter status
            $status = $request->get('status', 'all');
            if ($status !== 'all') {
                $leavesCollection = $leavesCollection->where('status', $status);
            }

            // Filter karyawan
            $employeeId = $request->get('employee_id');
            if ($employeeId) {
                $leavesCollection = $leavesCollection->where('employeeId', $employeeId);
            }

            $leavesCollection = $leavesCollection->sortByDesc('created_at');

            $employees = $this->firebase->getCompanyEmployees();

            $leavesCollection = $leavesCollection->map(function ($leave) use ($employees) {
                $emp = $employees[$leave->employeeId] ?? null;
                $leave->employee_name = $emp['name'] ?? 'Karyawan Tidak Diketahui';
                $leave->employee_department = $emp['department'] ?? '-';
                return $leave;
            });

            // Pagination
            $perPage = 10;
            $currentPage = $request->input('page', 1);
            $paginatedItems = $leavesCollection->forPage($currentPage, $perPage)->values();

            $leaves = new LengthAwarePaginator(
                $paginatedItems,
                $leavesCollection->count(),
                $perPage,
                $currentPage,
                ['path' => $request->url(), 'query' => $request->query()]
            );

            return view('leaves.index', compact('leaves', 'employees', 'status', 'employeeId'));
        } catch (\Exception $e) {
            \Log::error('LeaveController@index error: ' . $e->getMessage());
            return view('leaves.index', [
                'leaves' => new LengthAwarePaginator(collect(), 0, 10),
                'employees' => [],
                'status' => 'all',
                'employeeId' => null,
            ])->with('error', 'Gagal memuat data cuti.');
        }
    }

    /**
     * Halaman My Leaves untuk karyawan biasa
     */
    public function myLeaves(Request $request)
    {
        $user = session('user');
        $employeeId = $user['employee_id'] ?? null;

        if (!$employeeId) {
            return redirect()->route('dashboard')->with('error', 'Data karyawan tidak ditemukan.');
        }

        $rawLeaves = $this->firebase->getEmployeeLeaves($employeeId) ?? [];

        $leavesCollection = collect($rawLeaves)->map(function ($leave, $leaveId) {
            return (object) [
                'id'                   => $leaveId,
                'leave_type'           => $leave['type'] ?? 'annual',
                'start_date'           => $leave['startDate'] ?? null,
                'end_date'             => $leave['endDate'] ?? null,
                'reason'               => $leave['reason'] ?? '-',
                'status'               => $leave['status'] ?? 'pending',
                'created_at'           => \Carbon\Carbon::parse($leave['createdAt'] ?? now()),
                'contact_during_leave' => $leave['contactDuringLeave'] ?? '-',
            ];
        })->sortByDesc('created_at');

        // Summary untuk card
        $pendingLeaves  = $leavesCollection->where('status', 'pending')->count();
        $approvedLeaves = $leavesCollection->where('status', 'approved')->count();
        $rejectedLeaves = $leavesCollection->where('status', 'rejected')->count();

        // Hitung sisa cuti tahunan (contoh: kuota 12 hari)
        $usedAnnualDays = $leavesCollection
            ->where('leave_type', 'annual')
            ->where('status', 'approved')
            ->sum(function ($leave) {
                $start = \Carbon\Carbon::parse($leave->start_date);
                $end = \Carbon\Carbon::parse($leave->end_date);
                return $start->diffInDays($end) + 1;
            });

        $remainingLeave = max(0, 12 - $usedAnnualDays);

        // Pagination
        $perPage = 10;
        $currentPage = $request->input('page', 1);
        $paginatedItems = $leavesCollection->forPage($currentPage, $perPage)->values();

        $leaves = new LengthAwarePaginator(
            $paginatedItems,
            $leavesCollection->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('leaves.my-leaves', compact(
            'leaves',
            'remainingLeave',
            'pendingLeaves',
            'approvedLeaves',
            'rejectedLeaves'
        ));
    }

    public function create()
    {
        $user = session('user');
        $role = $user['role'] ?? 'employee';

        $employees = [];
        if (in_array($role, ['admin', 'manager'])) {
            $employees = $this->firebase->getCompanyEmployees();
        }

        $leaveTypes = [
            'annual'    => 'Cuti Tahunan',
            'sick'      => 'Cuti Sakit',
            'personal'  => 'Cuti Pribadi',
            'maternity' => 'Cuti Melahirkan',
            'paternity' => 'Cuti Ayah',
            'unpaid'    => 'Tanpa Gaji'
        ];

        return view('leaves.create', compact('employees', 'leaveTypes'));
    }

public function store(Request $request)
{
    // ================= VALIDATION =================
    $request->validate([
        'employee_id'          => 'required|string',
        'leave_type'           => 'required|string|in:annual,sick,personal,maternity,paternity,unpaid',
        'start_date'           => 'required|date',
        'end_date'             => 'required|date|after_or_equal:start_date',
        'reason'               => 'required|string|max:500',
        'contact_during_leave' => 'nullable|string|max:255',
    ]);

    try {
        // ================= CEK KARYAWAN =================
        $employee = $this->firebase->getEmployee($request->employee_id);
        if (!$employee) {
            return back()
                ->with('error', 'Karyawan tidak ditemukan.')
                ->withInput();
        }

        // ================= CEK BENTROK CUTI =================
        // Ambil semua cuti milik karyawan
        $existingLeaves = $this->firebase->getEmployeeLeaves($request->employee_id) ?? [];

        $newStart = \Carbon\Carbon::parse($request->start_date)->startOfDay();
        $newEnd   = \Carbon\Carbon::parse($request->end_date)->endOfDay();

        foreach ($existingLeaves as $leave) {
            // HANYA cek cuti yang SUDAH DISETUJUI
            if (($leave['status'] ?? 'pending') !== 'approved') {
                continue;
            }

            $existingStart = \Carbon\Carbon::parse($leave['startDate'])->startOfDay();
            $existingEnd   = \Carbon\Carbon::parse($leave['endDate'])->endOfDay();

            // Jika tanggal bentrok → TOLAK
            if ($newStart->lte($existingEnd) && $newEnd->gte($existingStart)) {
                return back()
                    ->with('error', 'Pengajuan cuti gagal. Anda sudah memiliki cuti yang disetujui pada tanggal tersebut.')
                    ->withInput();
            }
        }

        // ================= SIMPAN CUTI =================
        $leaveData = [
            'employee_id'          => $request->employee_id,
            'leave_type'           => $request->leave_type,
            'start_date'           => $request->start_date,
            'end_date'             => $request->end_date,
            'reason'               => $request->reason,
            'contact_during_leave' => $request->contact_during_leave ?? '-',
            'status'               => 'pending',
            'created_at'           => now()->toDateTimeString(),
        ];

        $leaveId = $this->firebase->createLeave($leaveData);

        // ================= REDIRECT =================
        return redirect()
            ->route(session('user')['role'] === 'employee' ? 'leaves.my' : 'leaves.index')
            ->with('success', 'Pengajuan cuti berhasil diajukan dan menunggu persetujuan.');

    } catch (\Exception $e) {
        \Log::error('LeaveController@store error: ' . $e->getMessage());

        return back()
            ->with('error', 'Terjadi kesalahan saat mengajukan cuti. Silakan coba lagi.')
            ->withInput();
    }
}


    public function show($id)
    {
        try {
            // Ambil data leave dari Firebase
            $rawLeave = $this->firebase->getLeave($id);

            if (!$rawLeave) {
                return redirect()->back()->with('error', 'Pengajuan cuti tidak ditemukan.');
            }

            // Mapping data ke object agar konsisten dengan view lain
            $leave = (object) [
                'id'                   => $id,
                'employeeId'           => $rawLeave['employeeId'] ?? null,
                'leave_type'           => $rawLeave['type'] ?? 'annual',
                'start_date'           => $rawLeave['startDate'] ?? null,
                'end_date'             => $rawLeave['endDate'] ?? null,
                'reason'               => $rawLeave['reason'] ?? '-',
                'status'               => $rawLeave['status'] ?? 'pending',
                'created_at'           => \Carbon\Carbon::parse($rawLeave['createdAt'] ?? now()),
                'approved_by'          => $rawLeave['approvedBy'] ?? null,
                'rejection_reason'     => $rawLeave['rejectionReason'] ?? null,
                'contact_during_leave' => $rawLeave['contactDuringLeave'] ?? '-',
            ];

            // Ambil data karyawan
            $employee = $this->firebase->getEmployee($leave->employeeId);

            if (!$employee) {
                $employee = [
                    'name'       => 'Karyawan Tidak Diketahui',
                    'department' => '-',
                    'phone'      => '-',
                ];
            }

            // Mapping nama jenis cuti
            $leaveTypes = [
                'annual'    => 'Cuti Tahunan',
                'sick'      => 'Cuti Sakit',
                'personal'  => 'Cuti Pribadi',
                'maternity' => 'Cuti Melahirkan',
                'paternity' => 'Cuti Ayah',
                'unpaid'    => 'Tanpa Gaji'
            ];
            $leaveTypeName = $leaveTypes[$leave->leave_type] ?? 'Cuti Lainnya';

            // Hitung jumlah hari cuti
            $startDate = \Carbon\Carbon::parse($leave->start_date);
            $endDate   = \Carbon\Carbon::parse($leave->end_date);
            $days      = $startDate->diffInDays($endDate) + 1;

            // Ambil role user dari session untuk breadcrumb & tombol kembali
            $user = session('user');
            $role = $user['role'] ?? 'employee';

            // Kirim semua data ke view
            return view('leaves.show', compact(
                'leave',
                'employee',
                'leaveTypeName',
                'days',
                'startDate',
                'endDate',
                'role'  // ← PENTING: agar tidak error undefined $role di view
            ));
        } catch (\Exception $e) {
            \Log::error('LeaveController@show error: ' . $e->getMessage() . ' | Leave ID: ' . $id);

            return redirect()->back()->with('error', 'Gagal memuat detail pengajuan cuti. Silakan coba lagi.');
        }
    }

    public function approve($id)
    {
        $user = session('user');
        $role = $user['role'] ?? 'employee';

        if (!in_array($role, ['admin', 'manager'])) {
            return back()->with('error', 'Anda tidak memiliki izin untuk menyetujui cuti.');
        }

        try {
            $leave = $this->firebase->getLeave($id);
            if (!$leave || ($leave['status'] ?? 'pending') !== 'pending') {
                return back()->with('error', 'Pengajuan tidak valid atau sudah diproses.');
            }

            $approvedBy = $user['name'] ?? 'Admin';
            $this->firebase->approveLeave($id, $approvedBy);

            return back()->with('success', "Cuti berhasil disetujui oleh $approvedBy.");
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menyetujui cuti.');
        }
    }

    public function reject(Request $request, $id)
    {
        $user = session('user');
        $role = $user['role'] ?? 'employee';

        if (!in_array($role, ['admin', 'manager'])) {
            return back()->with('error', 'Anda tidak memiliki izin untuk menolak cuti.');
        }

        $request->validate([
            'rejection_reason' => 'required|string|max:500'
        ]);

        try {
            $leave = $this->firebase->getLeave($id);
            if (!$leave || ($leave['status'] ?? 'pending') !== 'pending') {
                return back()->with('error', 'Pengajuan tidak valid atau sudah diproses.');
            }

            $rejectedBy = $user['name'] ?? 'Admin';
            $this->firebase->rejectLeave($id, $rejectedBy, $request->rejection_reason);

            return back()->with('success', 'Cuti berhasil ditolak.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menolak cuti.');
        }
    }

    public function cancel($id)
    {
        $user = session('user');
        $role = $user['role'] ?? 'employee';

        try {
            $leave = $this->firebase->getLeave($id);
            if (!$leave || ($leave['status'] ?? 'pending') !== 'pending') {
                return back()->with('error', 'Pengajuan tidak dapat dibatalkan.');
            }

            // Hanya pemilik atau admin yang boleh cancel
            if ($role === 'employee' && ($leave['employeeId'] ?? null) !== $user['employee_id']) {
                return back()->with('error', 'Anda hanya bisa membatalkan pengajuan sendiri.');
            }

            $this->firebase->cancelLeave($id);

            return back()->with('success', 'Pengajuan cuti berhasil dibatalkan.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal membatalkan cuti.');
        }
    }

    public function calendar()
    {
        $user = session('user');
        $role = $user['role'] ?? 'employee';

        if (!in_array($role, ['admin', 'manager'])) {
            return redirect()->route('leaves.my')->with('error', 'Akses ditolak.');
        }

        try {
            $leaves = $this->firebase->getAllLeaves() ?? [];
            $employees = $this->firebase->getCompanyEmployees();

            $calendarEvents = [];
            foreach ($leaves as $leaveId => $leave) {
                if (($leave['status'] ?? 'pending') === 'approved') {
                    $emp = $employees[$leave['employeeId']] ?? ['name' => 'Unknown'];
                    $color = $this->getLeaveColor($leave['type'] ?? 'annual');

                    $calendarEvents[] = [
                        'id'    => $leaveId,
                        'title' => $emp['name'],
                        'start' => $leave['startDate'],
                        'end'   => date('Y-m-d', strtotime($leave['endDate'] . ' +1 day')),
                        'color' => $color,
                        'extendedProps' => [
                            'type'       => $leave['type'] ?? 'annual',
                            'employee'   => $emp['name'],
                            'department' => $emp['department'] ?? '-',
                        ]
                    ];
                }
            }

            return view('leaves.calendar', compact('calendarEvents'));
        } catch (\Exception $e) {
            return view('leaves.calendar', ['calendarEvents' => []]);
        }
    }

    private function getLeaveColor($type)
    {
        $colors = [
            'annual'    => '#27ae60',
            'sick'      => '#e74c3c',
            'personal'  => '#3498db',
            'maternity' => '#9b59b6',
            'paternity' => '#2ecc71',
            'unpaid'    => '#f39c12'
        ];
        return $colors[$type] ?? '#95a5a6';
    }
}
