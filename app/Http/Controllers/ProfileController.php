<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FirebaseService;

class ProfileController extends Controller
{
    protected $firebase;

    public function __construct(FirebaseService $firebase)
    {
        $this->firebase = $firebase;
    }

    public function index()
    {
        $user = session('user');

        if (isset($user['employee_id'])) {
            $employee = $this->firebase->getEmployee($user['employee_id']);
            return view('profile.index', compact('user', 'employee'));
        }

        return view('profile.index', compact('user'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'nullable|string',
            'address' => 'nullable|string'
        ]);

        $user = session('user');
        $user['name'] = $request->name;
        $user['email'] = $request->email;

        session(['user' => $user]);

        // Update employee data jika ada employee_id
        if (isset($user['employee_id'])) {
            $updateData = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'updated_at' => now()->toISOString()
            ];

            $this->firebase->getDatabase()
                ->getReference('employees/' . $user['employee_id'])
                ->update($updateData);
        }

        return redirect()->route('profile')->with('success', 'Profile updated successfully!');
    }
}
