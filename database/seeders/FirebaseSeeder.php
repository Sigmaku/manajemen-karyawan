<?php

namespace Database\Seeders;

use App\Services\FirebaseService;
use Illuminate\Database\Seeder;

class FirebaseSeeder extends Seeder
{
    protected $firebase;

    public function __construct()
    {
        $this->firebase = new FirebaseService();
    }

    public function run()
    {
        $db = $this->firebase->getDatabase();
        $companyId = 'company_abc123';

        // Setup initial company data
        $db->getReference("companies/{$companyId}")
            ->set([
                'name' => 'PT. Maju Jaya',
                'createdAt' => date('Y-m-d'),
                'address' => 'Jakarta, Indonesia'
            ]);

        // Add test employees if not exists
        $existingEmployees = $db->getReference('employees')->getValue();

        if (!$existingEmployees || count($existingEmployees) < 2) {
            $employees = [
                'emp_001' => [
                    'companyId' => $companyId,
                    'name' => 'Budi Setiawan',
                    'email' => 'budi@company.com',
                    'phone' => '08123456789',
                    'department' => 'IT',
                    'position' => 'Developer',
                    'joinDate' => '2024-01-01',
                    'status' => 'active',
                    'role' => 'employee',
                    'createdAt' => date('Y-m-d'),
                    'updatedAt' => date('Y-m-d')
                ],
                'emp_002' => [
                    'companyId' => $companyId,
                    'name' => 'Siti Rahayu',
                    'email' => 'siti@company.com',
                    'phone' => '08234567890',
                    'department' => 'HR',
                    'position' => 'HR Manager',
                    'joinDate' => '2024-01-01',
                    'status' => 'active',
                    'role' => 'employee',
                    'createdAt' => date('Y-m-d'),
                    'updatedAt' => date('Y-m-d')
                ]
            ];

            foreach ($employees as $id => $data) {
                $db->getReference("employees/{$id}")->set($data);
            }
        }

        echo "Firebase data seeded successfully!\n";
    }
}
