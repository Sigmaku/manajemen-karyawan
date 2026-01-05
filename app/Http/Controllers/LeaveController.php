<?php

namespace App\Http\Controllers;

use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

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
        try {
            // Ambil semua leave perusahaan (array: leaveId => data)
            $rawLeaves = $this->firebase->getAllLeaves(); // return array atau []

            // Jika tidak ada data, return empty collection
            if (empty($rawLeaves)) {
                $rawLeaves = [];
            }

            // Ubah menjadi Collection of objects + mapping field sesuai data Firebase
            $leavesCollection = collect($rawLeaves)->map(function ($leave, $leaveId) {
                return (object) [
                    'id'              => $leaveId,
                    'employeeId'      => $leave['employeeId'] ?? null,
                    'leave_type'      => $leave['type'] ?? 'annual',
                    'start_date'      => $leave['startDate'] ?? null,
                    'end_date'        => $leave['endDate'] ?? null,
                    'reason'          => $leave['reason'] ?? '-',
                    'status'          => $leave['status'] ?? 'pending',
                    'created_at'      => \Carbon\Carbon::parse($leave['createdAt'] ?? now()),
                    'approved_by'     => $leave['approvedBy'] ?? null,
                    'rejection_reason' => $leave['rejectionReason'] ?? null,
                    'contact_during_leave' => $leave['contactDuringLeave'] ?? '-',
                ];
            });

            // Filter berdasarkan status
            $status = $request->get('status', 'all');
            if ($status !== 'all') {
                $leavesCollection = $leavesCollection->where('status', $status);
            }

            // Filter berdasarkan karyawan
            $employeeId = $request->get('employee_id');
            if ($employeeId) {
                $leavesCollection = $leavesCollection->where('employeeId', $employeeId);
            }

            // Urutkan dari yang terbaru
            $leavesCollection = $leavesCollection->sortByDesc('created_at');

            // Ambil data semua karyawan perusahaan
            $employees = $this->firebase->getCompanyEmployees(); // array: empId => data

            // Tambahkan nama dan departemen ke setiap leave
            $leavesCollection = $leavesCollection->map(function ($leave) use ($employees) {
                $emp = $employees[$leave->employeeId] ?? null;

                $leave->employee_name       = $emp['name'] ?? 'Karyawan Tidak Diketahui';
                $leave->employee_department = $emp['department'] ?? '-';
                $leave->employee_phone      = $emp['phone'] ?? '-';

                return $leave;
            });

            // Pagination Laravel (bisa pakai {{ $leaves->links() }} di Blade)
            $perPage = 10;
            $currentPage = $request->input('page', 1);
            $paginatedItems = $leavesCollection->forPage($currentPage, $perPage)->values();

            $leaves = new \Illuminate\Pagination\LengthAwarePaginator(
                $paginatedItems,
                $leavesCollection->count(),
                $perPage,
                $currentPage,
                [
                    'path'  => $request->url(),
                    'query' => $request->query(),
                ]
            );

            return view('leaves.index', compact('leaves', 'employees', 'status', 'employeeId'));
        } catch (\Exception $e) {
            \Log::error('Error di LeaveController@index: ' . $e->getMessage());

            return view('leaves.index', [
                'leaves'      => new \Illuminate\Pagination\LengthAwarePaginator(collect(), 0, 10),
                'employees'   => [],
                'status'      => 'all',
                'employeeId'  => null,
                'error'       => 'Gagal memuat data pengajuan cuti. Silakan refresh halaman.',
            ]);
        }
    }
    public function create()
    {
        $employees = $this->firebase->getCompanyEmployees();

        $leaveTypes = [
            'annual' => 'Annual Leave',
            'sick' => 'Sick Leave',
            'personal' => 'Personal Leave',
            'maternity' => 'Maternity Leave',
            'paternity' => 'Paternity Leave',
            'unpaid' => 'Unpaid Leave'
        ];

        return view('leaves.create', compact('employees', 'leaveTypes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|string',
            'leave_type' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:500',
            'contact_during_leave' => 'required|string'
        ]);

        try {
            $employee = $this->firebase->getEmployee($request->employee_id);

            if (!$employee) {
                return back()->with('error', 'Employee not found')->withInput();
            }

            $leaveData = [
                'employee_id' => $request->employee_id,
                'leave_type' => $request->leave_type,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'reason' => $request->reason,
                'contact_during_leave' => $request->contact_during_leave
            ];

            $leaveId = $this->firebase->createLeave($leaveData);

            return redirect()->route('leaves.index')
                ->with('success', "Leave application submitted successfully! ID: $leaveId");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to submit leave: ' . $e->getMessage())->withInput();
        }
    }

    public function show($id)
    {
        try {
            // Ambil data leave mentah dari Firebase (array)
            $rawLeave = $this->firebase->getLeave($id);

            if (!$rawLeave) {
                return redirect()->route('leaves.index')
                    ->with('error', 'Pengajuan cuti tidak ditemukan.');
            }

            // Ubah menjadi objek + mapping field sesuai struktur Firebase
            $leave = (object) [
                'id'                   => $id,
                'employeeId'           => $rawLeave['employeeId'] ?? null,
                'leave_type'           => $rawLeave['type'] ?? 'annual',
                'start_date'           => $rawLeave['startDate'] ?? null,
                'end_date'              => $rawLeave['endDate'] ?? null,
                'reason'               => $rawLeave['reason'] ?? '-',
                'status'               => $rawLeave['status'] ?? 'pending',
                'created_at'           => \Carbon\Carbon::parse($rawLeave['createdAt'] ?? now()),
                'approved_by'          => $rawLeave['approvedBy'] ?? $rawLeave['approved_by'] ?? null,
                'rejection_reason'     => $rawLeave['rejectionReason'] ?? $rawLeave['rejection_reason'] ?? null,
                'contact_during_leave' => $rawLeave['contactDuringLeave'] ?? '-',
            ];

            // Ambil data karyawan
            $employee = $this->firebase->getEmployee($leave->employeeId);

            if (!$employee) {
                $employee = [
                    'name' => 'Karyawan Tidak Diketahui',
                    'department' => '-',
                    'phone' => '-'
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

            // Hitung jumlah hari
            $startDate = \Carbon\Carbon::parse($leave->start_date);
            $endDate = \Carbon\Carbon::parse($leave->end_date);
            $days = $startDate->diffInDays($endDate) + 1;

            return view('leaves.show', compact(
                'leave',
                'employee',
                'leaveTypeName',
                'days',
                'startDate',
                'endDate'
            ));
        } catch (\Exception $e) {
            \Log::error('Error di LeaveController@show: ' . $e->getMessage());

            return redirect()->route('leaves.index')
                ->with('error', 'Gagal memuat detail pengajuan cuti.');
        }
    }

    public function approve($id)
    {
        try {
            $leave = $this->firebase->getLeave($id);

            if (!$leave) {
                return back()->with('error', 'Leave not found');
            }

            $approvedBy = session('user')['name'] ?? 'Admin';
            $this->firebase->approveLeave($id, $approvedBy);

            return redirect()->back()
                ->with('success', "Leave approved by $approvedBy");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to approve leave: ' . $e->getMessage());
        }
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500'
        ]);

        try {
            $leave = $this->firebase->getLeave($id);

            if (!$leave) {
                return back()->with('error', 'Leave not found');
            }

            $rejectedBy = session('user')['name'] ?? 'Admin';
            $this->firebase->rejectLeave($id, $rejectedBy, $request->rejection_reason);

            return redirect()->back()
                ->with('success', 'Leave rejected successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to reject leave: ' . $e->getMessage());
        }
    }

    public function cancel($id)
    {
        try {
            $leave = $this->firebase->getLeave($id);

            if (!$leave) {
                return back()->with('error', 'Leave not found');
            }

            $this->firebase->cancelLeave($id);

            return redirect()->back()
                ->with('success', 'Leave cancelled successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to cancel leave: ' . $e->getMessage());
        }
    }

    public function calendar()
    {
        try {
            $leaves = $this->firebase->getAllLeaves();
            $employees = $this->firebase->getCompanyEmployees();

            $calendarEvents = [];
            foreach ($leaves as $leaveId => $leave) {
                if (($leave['status'] ?? 'pending') === 'approved') {
                    $employee = $employees[$leave['employeeId']] ?? [];
                    $color = $this->getLeaveColor($leave['type'] ?? 'annual');

                    $calendarEvents[] = [
                        'id' => $leaveId,
                        'title' => $employee['name'] ?? 'Unknown',
                        'start' => $leave['startDate'],
                        'end' => date('Y-m-d', strtotime($leave['endDate'] . ' +1 day')),
                        'color' => $color,
                        'extendedProps' => [
                            'type' => $leave['type'] ?? 'annual',
                            'employee' => $employee['name'] ?? 'Unknown',
                            'department' => $employee['department'] ?? '-',
                            'status' => $leave['status'] ?? 'pending'
                        ]
                    ];
                }
            }

            return view('leaves.calendar', compact('calendarEvents'));
        } catch (\Exception $e) {
            return view('leaves.calendar', [
                'calendarEvents' => [],
                'error' => $e->getMessage()
            ]);
        }
    }

    public function myLeaves()
    {
        try {
            $user = session('user');
            $employeeId = $user['employee_id'] ?? null;

            if (!$employeeId) {
                return redirect()->route('leaves.index')
                    ->with('error', 'Employee ID not found in session');
            }

            $leaves = $this->firebase->getEmployeeLeaves($employeeId);
            $employee = $this->firebase->getEmployee($employeeId);

            return view('leaves.my-leaves', compact('leaves', 'employee', 'remainingLeave', 'pendingLeaves', 'approvedLeaves', 'rejectedLeaves'));
        } catch (\Exception $e) {
            return view('leaves.my-leaves', [
                'leaves' => [],
                'employee' => [],
                'error' => $e->getMessage()
            ]);
        }
    }

    private function getLeaveColor($type)
    {
        $colors = [
            'annual' => '#27ae60',    // Green
            'sick' => '#e74c3c',      // Red
            'personal' => '#3498db',  // Blue
            'maternity' => '#9b59b6', // Purple
            'paternity' => '#2ecc71', // Light Green
            'unpaid' => '#f39c12'     // Orange
        ];

        return $colors[$type] ?? '#95a5a6'; // Grey default
    }

    // ==================== API METHODS ====================

    public function apiEmployeeLeaves($employeeId): JsonResponse
    {
        try {
            $employee = $this->firebase->getEmployee($employeeId);

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee not found'
                ], 404);
            }

            $leaves = $this->firebase->getEmployeeLeaves($employeeId);

            return response()->json([
                'success' => true,
                'data' => [
                    'employee' => $employee,
                    'leaves' => $leaves,
                    'total' => count($leaves)
                ],
                'message' => 'Employee leaves retrieved'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get employee leaves',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
