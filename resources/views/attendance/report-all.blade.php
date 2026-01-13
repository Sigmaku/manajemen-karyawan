@extends('layouts.app')

@section('title', 'Attendance Report')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4">Attendance Report (All Employees)</h1>

    @if(isset($error))
        <div class="alert alert-danger">{{ $error }}</div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Month: {{ $month ?? date('Y-m') }}</strong>
            <form method="GET" action="{{ route('attendance.report') }}" class="d-flex gap-2">
                <input type="month" name="month" value="{{ $month ?? date('Y-m') }}" class="form-control">
                <button class="btn btn-primary">Filter</button>
            </form>
        </div>

        <div class="card-body">
            @if(!empty($stats))
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Employee</th>
                                <th>Department</th>
                                <th class="text-center">Present Days</th>
                                <th class="text-center">Total Hours</th>
                                <th class="text-center">Attendance Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stats as $empId => $row)
                                <tr>
                                    <td>{{ $row['employee']['name'] ?? 'Unknown' }}</td>
                                    <td>{{ $row['employee']['department'] ?? '-' }}</td>
                                    <td class="text-center">{{ $row['present_days'] ?? 0 }}</td>
                                    <td class="text-center">{{ number_format($row['total_hours'] ?? 0, 2) }}</td>
                                    <td class="text-center"><strong>{{ $row['attendance_rate'] ?? 0 }}%</strong></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-muted p-4 text-center">No data for this month.</div>
            @endif
        </div>
    </div>
</div>
@endsection

