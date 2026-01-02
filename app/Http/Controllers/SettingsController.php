<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FirebaseService;

class SettingsController extends Controller
{
    protected $firebase;

    public function __construct(FirebaseService $firebase)
    {
        $this->firebase = $firebase;
    }

    public function index()
    {
        $db = $this->firebase->getDatabase();

        // Load settings from Firebase
        $settings = $db->getReference('settings')->getValue() ?: [
            'company_name' => 'PT. Employee Management',
            'working_hours' => '08:00 - 17:00',
            'checkin_start' => '07:00',
            'checkin_end' => '09:00',
            'checkout_start' => '16:00',
            'checkout_end' => '19:00',
            'late_threshold' => '08:15',
            'office_location' => 'Jakarta Office',
            'leave_annual_days' => 12
        ];

        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'working_hours' => 'required|string',
            'checkin_start' => 'required|date_format:H:i',
            'checkin_end' => 'required|date_format:H:i',
            'checkout_start' => 'required|date_format:H:i',
            'checkout_end' => 'required|date_format:H:i',
            'late_threshold' => 'required|date_format:H:i',
            'office_location' => 'required|string',
            'leave_annual_days' => 'required|integer|min:0'
        ]);

        $settings = $request->only([
            'company_name', 'working_hours', 'checkin_start', 'checkin_end',
            'checkout_start', 'checkout_end', 'late_threshold', 'office_location',
            'leave_annual_days'
        ]);

        $settings['updated_at'] = now()->toISOString();
        $settings['updated_by'] = session('user')['id'] ?? 'system';

        $this->firebase->getDatabase()
            ->getReference('settings')
            ->set($settings);

        return redirect()->route('settings.index')->with('success', 'Settings updated successfully!');
    }
}
