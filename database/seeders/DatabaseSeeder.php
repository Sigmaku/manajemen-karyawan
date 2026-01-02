<?php

namespace Database\Seeders;

use App\Services\FirebaseService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $firebase = app(FirebaseService::class);
        $db = $firebase->getDatabase();

        // Clear existing data
        $db->getReference('/')->remove();

        // Sample employees
        $employees = [
            'EMP001' => [
                'id' => 'EMP001',
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'phone' => '081234567890',
                'department' => 'IT',
                'position' => 'Software Engineer',
                'hire_date' => '2023-01-15',
                'salary' => 15000000,
                'address' => 'Jl. Sudirman No. 123, Jakarta',
                'status' => 'active',
                'created_at' => '2023-01-15T08:00:00Z',
                'updated_at' => '2023-12-01T10:00:00Z'
            ],
            'EMP002' => [
                'id' => 'EMP002',
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
                'phone' => '081298765432',
                'department' => 'HR',
                'position' => 'HR Manager',
                'hire_date' => '2022-05-20',
                'salary' => 20000000,
                'address' => 'Jl. Thamrin No. 45, Jakarta',
                'status' => 'active',
                'created_at' => '2022-05-20T09:00:00Z',
                'updated_at' => '2023-11-15T14:00:00Z'
            ],
            'EMP003' => [
                'id' => 'EMP003',
                'name' => 'Robert Johnson',
                'email' => 'robert@example.com',
                'phone' => '081345678901',
                'department' => 'Finance',
                'position' => 'Finance Analyst',
                'hire_date' => '2023-03-10',
                'salary' => 12000000,
                'address' => 'Jl. Gatot Subroto No. 67, Jakarta',
                'status' => 'active',
                'created_at' => '2023-03-10T08:30:00Z',
                'updated_at' => '2023-12-05T11:00:00Z'
            ]
        ];

        $db->getReference('employees')->set($employees);

        // Sample attendance for current month
        $currentMonth = date('Y-m');
        $attendance = [
            $currentMonth => [
                'EMP001' => [
                    date('Y-m-d') => [
                        'check_in' => '08:00:00',
                        'check_out' => '17:00:00',
                        'location' => 'Office',
                        'status' => 'present',
                        'timestamp' => date('Y-m-d') . 'T08:00:00Z'
                    ]
                ],
                'EMP002' => [
                    date('Y-m-d') => [
                        'check_in' => '08:15:00',
                        'check_out' => '17:30:00',
                        'location' => 'Office',
                        'status' => 'present',
                        'timestamp' => date('Y-m-d') . 'T08:15:00Z'
                    ]
                ]
            ]
        ];

        $db->getReference('attendances')->set($attendance);

        // Sample leaves
        $leaves = [
            'LEAVE001' => [
                'id' => 'LEAVE001',
                'employee_id' => 'EMP001',
                'leave_type' => 'annual',
                'start_date' => date('Y-m-d', strtotime('+5 days')),
                'end_date' => date('Y-m-d', strtotime('+7 days')),
                'reason' => 'Family vacation',
                'contact_during_leave' => '081234567890',
                'status' => 'approved',
                'applied_date' => date('Y-m-d') . 'T10:00:00Z',
                'approved_at' => date('Y-m-d') . 'T14:00:00Z',
                'approved_by' => 'admin'
            ]
        ];

        $db->getReference('leaves')->set($leaves);

        echo "Sample data seeded successfully!\n";
        echo "Employees: EMP001, EMP002, EMP003\n";
        echo "Password for all: password123\n";
    }
}
