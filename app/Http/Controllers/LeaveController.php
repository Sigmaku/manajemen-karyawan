<?php

namespace App\Http\Controllers;

use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Log;
use Cloudinary\Cloudinary;

class LeaveController extends Controller
{
    protected $firebase;

    public function __construct(FirebaseService $firebase)
    {
        $this->firebase = $firebase;
    }

    // ==================== WEB METHODS ====================

    public function index(Request $request)
    {
        $user = session('user');
        $role = $user['role'] ?? 'employee';

        if ($role === 'employee') {
            return redirect()->route('leaves.my');
        }

        try {
            $rawLeaves = $this->firebase->getAllLeaves() ?? [];

            $leavesCollection = collect($rawLeaves)->map(function ($leave, $leaveId) {
                $days = $this->countWorkingDays(
                    $leave['startDate'] ?? null,
                    $leave['endDate'] ?? null
                );

                return (object) [
                    'id'                    => $leaveId,
                    'employeeId'            => $leave['employeeId'] ?? null,
                    'leave_type'            => $leave['type'] ?? 'annual',
                    'start_date'            => $leave['startDate'] ?? null,
                    'end_date'              => $leave['endDate'] ?? null,
                    'reason'                => $leave['reason'] ?? '-',
                    'status'                => $leave['status'] ?? 'pending',
                    'created_at'            => Carbon::parse($leave['createdAt'] ?? now()),
                    'contact_during_leave'  => $leave['contactDuringLeave'] ?? '-',
                    'proof_url'             => $leave['proof_url'] ?? null,
                    'proof_filename'        => $leave['proof_filename'] ?? null,
                    'days'                  => $days,
                ];
            })->sortByDesc('created_at');

            // Filter status & employee
            $status = $request->get('status', 'all');
            if ($status !== 'all') $leavesCollection = $leavesCollection->where('status', $status);
            
            $employeeId = $request->get('employee_id');
            if ($employeeId) $leavesCollection = $leavesCollection->where('employeeId', $employeeId);

            $employees = $this->firebase->getCompanyEmployees();
            $leavesCollection = $leavesCollection->map(function ($leave) use ($employees) {
                $emp = $employees[$leave->employeeId] ?? null;
                $leave->employee_name = $emp['name'] ?? 'Karyawan Tidak Diketahui';
                $leave->employee_department = $emp['department'] ?? '-';
                return $leave;
            });

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
            Log::error('LeaveController@index error: ' . $e->getMessage());
            return view('leaves.index', [
                'leaves' => new LengthAwarePaginator(collect(), 0, 10),
                'employees' => [],
                'status' => 'all',
                'employeeId' => null,
            ])->with('error', 'Gagal memuat data cuti.');
        }
    }

    private function countWorkingDays($startDate, $endDate)
    {
        if(!$startDate || !$endDate) return 0;
        $start = Carbon::parse($startDate);
        $end   = Carbon::parse($endDate);
        $workingDays = 0;
        foreach (CarbonPeriod::create($start, $end) as $date) {
            if ($date->isWeekday()) $workingDays++;
        }
        return $workingDays;
    }

    // --- Bagian yang Tadi Conflict (Diambil dari Main) ---
    private function getEmployeeAnnualQuota(array $employee): int
    {
        $raw = $employee['leavequota'] ?? 0;
        if ($raw === '' || $raw === null) return 0;
        return (int) $raw;
    }

    private function ensureLeaveQuotaUpdatedDbStyle(array $employee): void
    {
        $joinDate = $employee['joinDate'] ?? null;
        if (!$joinDate) return;
        $currentQuota = $this->getEmployeeAnnualQuota($employee);
        if ($currentQuota > 0) return;
        $join = \Carbon\Carbon::parse($joinDate)->startOfDay();
        $today = now()->startOfDay();
        if ($today->gte($join->copy()->addYear())) {
            $this->firebase->getDatabase()->getReference('employees/' . $employee['id'])->update([
                'leavequota' => '12',
                'updatedAt' => now()->toISOString(),
            ]);
        }
    }

    private function ensureAnnualQuotaResetDbStyle(array $employee): void
    {
        $empId = $employee['id'] ?? null;
        if (!$empId) return;
        if (!(now()->month === 1 && now()->day === 1)) return;
        $currentYear = (int) now()->format('Y');
        $lastYearRaw = $employee['leavequotaYear'] ?? null;
        if ($lastYearRaw && (int)$lastYearRaw === $currentYear) return;
        $joinDate = $employee['joinDate'] ?? null;
        if (!$joinDate) return;
        $join = \Carbon\Carbon::parse($joinDate)->startOfDay();
        $quota = now()->startOfDay()->lt($join->addYear()) ? '0' : '12';
        $this->firebase->getDatabase()->getReference('employees/' . $empId)->update([
            'leavequota' => $quota,
            'leavequotaYear' => (string) $currentYear,
            'updatedAt' => now()->toISOString(),
        ]);
    }
    // --- End of Conflict Resolve ---

    public function myLeaves(Request $request)
    {
        $user = session('user');
        $employeeId = $user['employee_id'] ?? null;
        if (!$employeeId) return redirect()->route('dashboard')->with('error', 'Data tidak ditemukan.');

        $employee = $this->firebase->getEmployee($employeeId);
        if (!$employee) return redirect()->route('dashboard')->with('error', 'Data tidak ditemukan.');

        // Jalankan logic quota
        $this->ensureAnnualQuotaResetDbStyle($employee);
        $this->ensureLeaveQuotaUpdatedDbStyle($employee);
        $employee = $this->firebase->getEmployee($employeeId);
        $quotaDays = $this->getEmployeeAnnualQuota($employee);

        $rawLeaves = $this->firebase->getEmployeeLeaves($employeeId) ?? [];
        $leavesCollection = collect($rawLeaves)->map(function ($leave, $leaveId) {
            return (object) [
                'id'                    => $leaveId,
                'employeeId'            => $leave['employeeId'] ?? null,
                'leave_type'            => $leave['type'] ?? 'annual',
                'start_date'            => $leave['startDate'] ?? null,
                'end_date'              => $leave['endDate'] ?? null,
                'reason'                => $leave['reason'] ?? '-',
                'status'                => $leave['status'] ?? 'pending',
                'created_at'            => Carbon::parse($leave['createdAt'] ?? now()),
                'contact_during_leave'  => $leave['contactDuringLeave'] ?? '-',
                'proof_url'             => $leave['proof_url'] ?? null,
                'proof_filename'        => $leave['proof_filename'] ?? null,
                'days'                  => $this->countWorkingDays($leave['startDate'], $leave['endDate']),
            ];
        });

        $pendingLeaves  = $leavesCollection->where('status', 'pending')->count();
        $approvedLeaves = $leavesCollection->where('status', 'approved')->count();
        $rejectedLeaves = $leavesCollection->where('status', 'rejected')->count();
        
        // Pake quotaDays yang dinamis (Resolve Conflict baris 200-an)
        $remainingLeave = max(0, $quotaDays);

        $perPage = 10;
        $currentPage = $request->input('page', 1);
        $paginatedItems = $leavesCollection->forPage($currentPage, $perPage)->values();
        $leaves = new LengthAwarePaginator($paginatedItems, $leavesCollection->count(), $perPage, $currentPage, ['path' => $request->url()]);

        return view('leaves.my-leaves', compact('leaves', 'remainingLeave', 'pendingLeaves', 'approvedLeaves', 'rejectedLeaves', 'quotaDays'));
    }

    public function apiMyLeaves()
    {
        $user = session('user');
        $employeeId = $user['employee_id'] ?? null;

        if (!$employeeId) {
            return response()->json(['success' => false, 'message' => 'Employee ID not found'], 401);
        }

        $rawLeaves = $this->firebase->getEmployeeLeaves($employeeId) ?? [];

        // Normalisasi + hitung hari kerja (sesuai logika kamu)
        $items = collect($rawLeaves)->map(function ($leave, $leaveId) {
            $start = $leave['startDate'] ?? null;
            $end   = $leave['endDate'] ?? null;

            $days = 0;
            if ($start && $end) {
                $days = $this->countWorkingDays($start, $end);
            }

            $createdAt = $leave['createdAt'] ?? now()->toISOString();

            return [
                'id' => $leaveId,
                'type' => $leave['type'] ?? 'annual',
                'startDate' => $start,
                'endDate' => $end,
                'status' => $leave['status'] ?? 'pending',
                'proof_url' => $leave['proof_url'] ?? null, // NEW
                'proof_filename' => $leave['proof_filename'] ?? null, // NEW
                'createdAt' => $createdAt,
                'days' => $days,
            ];
        })
            ->sortByDesc(function ($x) {
                try {
                    return Carbon::parse($x['createdAt'])->timestamp;
                } catch (\Exception $e) {
                    return 0;
                }
            })
            ->values();

        // Summary
        $pending  = $items->where('status', 'pending')->count();
        $approved = $items->where('status', 'approved')->count();
        $rejected = $items->where('status', 'rejected')->count();

        // Ambil 10 terbaru untuk tabel realtime
        $latest = $items->take(10)->values();

        return response()->json([
            'success' => true,
            'summary' => [
                'pending' => $pending,
                'approved' => $approved,
                'rejected' => $rejected,
            ],
            'items' => $latest,
        ]);
    }

public function apiAllLeaves(Request $request)
    {
        $user = session('user');
        $role = $user['role'] ?? 'employee';

        // Hanya admin/manager yang bisa akses API ini
        if (!in_array($role, ['admin', 'manager'])) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        try {
            $rawLeaves = $this->firebase->getAllLeaves() ?? [];
            $employees = $this->firebase->getCompanyEmployees();

            // Filter optional
            $statusFilter = $request->get('status', 'all');
            $employeeFilter = $request->get('employee_id');

            $items = collect($rawLeaves)->map(function ($leave, $leaveId) use ($employees) {
                $empId = $leave['employeeId'] ?? null;
                $emp = $employees[$empId] ?? null;

                return [
                    'id'            => $leaveId,
                    'employeeId'    => $empId,
                    'employeeName'  => $emp['name'] ?? 'Karyawan Tidak Diketahui',
                    'department'    => $emp['department'] ?? '-',
                    'type'          => $leave['type'] ?? 'annual',
                    'startDate'     => $leave['startDate'] ?? null,
                    'endDate'       => $leave['endDate'] ?? null,
                    'status'        => $leave['status'] ?? 'pending',
                    'createdAt'     => $leave['createdAt'] ?? null,
                    'proof_url'     => $leave['proof_url'] ?? null,
                ];
            });

            // Jalankan Filter Status
            if ($statusFilter !== 'all') {
                $items = $items->where('status', $statusFilter);
            }
            
            // Jalankan Filter Karyawan
            if ($employeeFilter) {
                $items = $items->where('employeeId', $employeeFilter);
            }

            // Urutkan dari yang terbaru
            $items = $items->sortByDesc(function ($x) {
                try {
                    return \Carbon\Carbon::parse($x['createdAt'])->timestamp;
                } catch (\Exception $e) {
                    return 0;
                }
            })->values();

            return response()->json([
                'success' => true,
                'items'   => $items->take(50)->values(), // Ambil 50 saja biar ringan
            ]);

        } catch (\Exception $e) {
            Log::error('LeaveController@apiAllLeaves error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Internal Server Error'], 500);
        }
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

    /**
     * Upload file ke Cloudinary
     */
    private function uploadToCloudinary($file)
    {
        try {
            // ⭐ SIMPLE VERSION - Auto read dari CLOUDINARY_URL di .env ⭐
            $cloudinary = new Cloudinary();

            // Generate unique filename
            $filename = 'bukti_cuti_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

            // Upload ke Cloudinary
            $uploadResult = $cloudinary->uploadApi()->upload(
                $file->getRealPath(),
                [
                    'public_id' => 'bukti_cuti/' . $filename,
                    'folder' => 'manajemen_karyawan/bukti_cuti',
                    'resource_type' => 'image',
                    'transformation' => [
                        'width' => 1200,
                        'height' => 1200,
                        'crop' => 'limit',
                        'quality' => 'auto'
                    ]
                ]
            );

            return [
                'url' => $uploadResult['secure_url'],
                'filename' => $filename
            ];
        } catch (\Exception $e) {
            Log::error('Cloudinary upload error: ' . $e->getMessage());
            throw new \Exception('Gagal mengunggah bukti: ' . $e->getMessage());
        }
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
            'proof'                => 'required|image|mimes:jpg,jpeg,png|max:2048', // NEW: max 2MB
        ], [
            'proof.required' => 'Bukti pendukung wajib diunggah.',
            'proof.image' => 'File harus berupa gambar (JPG, JPEG, PNG).',
            'proof.mimes' => 'Format file harus JPG, JPEG, atau PNG.',
            'proof.max' => 'Ukuran file maksimal 2MB.',
            'end_date.after_or_equal' => 'Tanggal selesai harus setelah atau sama dengan tanggal mulai.',
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
            $existingLeaves = $this->firebase->getEmployeeLeaves($request->employee_id) ?? [];

            $newStart = Carbon::parse($request->start_date)->startOfDay();
            $newEnd   = Carbon::parse($request->end_date)->endOfDay();

            foreach ($existingLeaves as $leave) {
                // HANYA cek cuti yang SUDAH DISETUJUI
                if (($leave['status'] ?? 'pending') !== 'approved') {
                    continue;
                }

                $existingStart = Carbon::parse($leave['startDate'])->startOfDay();
                $existingEnd   = Carbon::parse($leave['endDate'])->endOfDay();

                // Jika tanggal bentrok → TOLAK
                if ($newStart->lte($existingEnd) && $newEnd->gte($existingStart)) {
                    return back()
                        ->with('error', 'Pengajuan cuti gagal. Anda sudah memiliki cuti yang disetujui pada tanggal tersebut.')
                        ->withInput();
                }
            }

            // ================= UPLOAD BUKTI KE CLOUDINARY =================
            $proofUrl = null;
            $proofFilename = null;

            if ($request->hasFile('proof') && $request->file('proof')->isValid()) {
                $proof = $request->file('proof');

                // Validasi ukuran file (2MB max) - double check
                if ($proof->getSize() > 2097152) {
                    return back()
                        ->with('error', 'Ukuran file bukti maksimal 2MB.')
                        ->withInput();
                }

                // Validasi tipe file
                $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png'];
                if (!in_array($proof->getMimeType(), $allowedMimes)) {
                    return back()
                        ->with('error', 'Format file tidak didukung. Hanya JPG, JPEG, dan PNG.')
                        ->withInput();
                }

                try {
                    $cloudinaryResult = $this->uploadToCloudinary($proof);
                    $proofUrl = $cloudinaryResult['url'];
                    $proofFilename = $cloudinaryResult['filename'];
                } catch (\Exception $e) {
                    Log::error('Cloudinary upload failed: ' . $e->getMessage());
                    return back()
                        ->with('error', 'Gagal mengunggah bukti. Silakan coba lagi.')
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
            ];

            // Gunakan method baru di FirebaseService yang support bukti
            $leaveId = $this->firebase->createLeaveWithProof($leaveData, $proofUrl, $proofFilename);

            // ================= REDIRECT =================
            return redirect()
                ->route(session('user')['role'] === 'employee' ? 'leaves.my' : 'leaves.index')
                ->with('success', 'Pengajuan cuti berhasil diajukan' . ($proofUrl ? ' dengan bukti' : '') . ' dan menunggu persetujuan.');
        } catch (\Exception $e) {
            Log::error('LeaveController@store error: ' . $e->getMessage());

            return back()
                ->with('error', 'Terjadi kesalahan saat mengajukan cuti: ' . $e->getMessage())
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
                'created_at'           => Carbon::parse($rawLeave['createdAt'] ?? now()),
                'approved_by'          => $rawLeave['approvedBy'] ?? null,
                'rejection_reason'     => $rawLeave['rejectionReason'] ?? null,
                'contact_during_leave' => $rawLeave['contactDuringLeave'] ?? '-',
                'proof_url'            => $rawLeave['proof_url'] ?? null, // NEW
                'proof_filename'       => $rawLeave['proof_filename'] ?? null, // NEW
            ];

            // Ambil data karyawan
            $employee = $this->firebase->getEmployee($leave->employeeId);
            $rawQuota = $employee['leavequota'] ?? 0;
            $remainingLeave = ($rawQuota === '' || $rawQuota === null) ? 0 : (int) $rawQuota;

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
            $startDate = Carbon::parse($leave->start_date);
            $endDate   = Carbon::parse($leave->end_date);
            $days = $this->countWorkingDays($leave->start_date, $leave->end_date);

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
                'role'
            ));
        } catch (\Exception $e) {
            Log::error('LeaveController@show error: ' . $e->getMessage() . ' | Leave ID: ' . $id);

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

            // 1) Approve dulu di Firebase
            $this->firebase->approveLeave($id, $approvedBy);

            // 2) Kalau jenis cuti = annual → POTONG QUOTA
            $leaveType = $leave['type'] ?? null;
            if ($leaveType === 'annual') {

                $employeeId = $leave['employeeId'] ?? null;
                if ($employeeId) {
                    $employee = $this->firebase->getEmployee($employeeId);
                    if ($employee) {
                        // quota saat ini (ngikut DB kamu: leavequota bisa "" / string)
                        $rawQuota = $employee['leavequota'] ?? 0;
                        $currentQuota = ($rawQuota === '' || $rawQuota === null) ? 0 : (int) $rawQuota;

                        // hitung hari kerja yang disetujui
                        $start = $leave['startDate'] ?? null;
                        $end   = $leave['endDate'] ?? null;

                        $days = 0;
                        if ($start && $end) {
                            $days = $this->countWorkingDays($start, $end);
                        }

                        $newQuota = max(0, $currentQuota - $days);

                        // update quota ke employee
                        $this->firebase->getDatabase()
                            ->getReference('employees/' . $employeeId)
                            ->update([
                                'leavequota' => (string) $newQuota, // simpan string sesuai DB kamu
                                'updatedAt' => now()->toISOString(),
                            ]);
                    }
                }
            }

            return back()->with('success', "Cuti berhasil disetujui oleh $approvedBy.");
        } catch (\Exception $e) {
            \Log::error('LeaveController@approve error: ' . $e->getMessage());
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

            // Method cancelLeave belum ada di FirebaseService, perlu ditambahkan
            // $this->firebase->cancelLeave($id);

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
