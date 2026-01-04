<?php

namespace Database\Seeders;

use App\Services\FirebaseService;
use Illuminate\Database\Seeder;

class FirebaseUserSeeder extends Seeder
{
    protected $firebase;

    public function __construct()
    {
        $this->firebase = new FirebaseService();
    }

    public function run()
    {
        $db = $this->firebase->getDatabase();

        $users = [
            'admin_uid_123' => [
                'uid' => 'admin_uid_123',
                'email' => 'admin@company.com',
                'name' => 'Administrator',
                'role' => 'admin',
                'employee_id' => 'emp_001',
                'created_at' => now()->toISOString()
            ],
            'manager_uid_456' => [
                'uid' => 'manager_uid_456',
                'email' => 'manager@company.com',
                'name' => 'Manager',
                'role' => 'manager',
                'employee_id' => 'emp_002',
                'created_at' => now()->toISOString()
            ],
            'employee_uid_789' => [
                'uid' => 'employee_uid_789',
                'email' => 'employee@company.com',
                'name' => 'John Employee',
                'role' => 'employee',
                'employee_id' => 'emp_003',
                'created_at' => now()->toISOString()
            ]
        ];

        foreach ($users as $uid => $userData) {
            $db->getReference("users/{$uid}")->set($userData);
        }

        echo "Firebase users seeded successfully!\n";
    }
}
