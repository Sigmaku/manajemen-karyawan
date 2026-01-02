@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">System Settings</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('settings.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <h5 class="mb-3">Company Settings</h5>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Company Name</label>
                                <input type="text" class="form-control" name="company_name"
                                       value="PT. Employee Management">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Working Hours</label>
                                <input type="text" class="form-control" name="working_hours"
                                       value="08:00 - 17:00">
                            </div>
                        </div>

                        <h5 class="mb-3 mt-4">Attendance Settings</h5>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Check-in Start</label>
                                <input type="time" class="form-control" name="checkin_start" value="07:00">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Check-in End</label>
                                <input type="time" class="form-control" name="checkin_end" value="09:00">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Check-out Start</label>
                                <input type="time" class="form-control" name="checkout_start" value="16:00">
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
