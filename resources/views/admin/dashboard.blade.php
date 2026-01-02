@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4">Admin Dashboard</h1>

    <!-- Admin Stats -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <h6 class="mb-0">Total Employees</h6>
                    <h2 class="mt-2">{{ $totalEmployees }}</h2>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <h6 class="mb-0">Total Leaves</h6>
                    <h2 class="mt-2">{{ $totalLeaves }}</h2>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">
                    <h6 class="mb-0">Pending Leaves</h6>
                    <h2 class="mt-2">{{ $pendingLeaves }}</h2>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-info text-white mb-4">
                <div class="card-body">
                    <h6 class="mb-0">Active Users</h6>
                    <h2 class="mt-2">{{ $activeUsers }}</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Admin Actions -->
    <div class="row">
        <div class="col-md-4 mb-3">
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-users-cog fa-3x text-primary mb-3"></i>
                    <h5>User Management</h5>
                    <a href="{{ route('admin.users') }}" class="btn btn-primary mt-2">Manage Users</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-file-alt fa-3x text-success mb-3"></i>
                    <h5>System Logs</h5>
                    <a href="{{ route('admin.logs') }}" class="btn btn-success mt-2">View Logs</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-database fa-3x text-warning mb-3"></i>
                    <h5>Backup & Restore</h5>
                    <a href="{{ route('admin.backup') }}" class="btn btn-warning mt-2">Backup Data</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
