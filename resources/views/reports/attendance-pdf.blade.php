<!DOCTYPE html>
<html>
<head>
    <title>Laporan Kehadiran {{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: DejaVu Sans, sans-serif; }
        .badge-success { background: #d4edda; color: #155724; padding: 5px 10px; border-radius: 5px; }
        .badge-warning { background: #fff3cd; color: #856404; padding: 5px 10px; border-radius: 5px; }
        .badge-danger { background: #f8d7da; color: #721c24; padding: 5px 10px; border-radius: 5px; }
        .badge-info { background: #d1ecf1; color: #0c5460; padding: 5px 10px; border-radius: 5px; }
    </style>
</head>
<body class="p-5">
    <h2 class="text-center mb-4">Laporan Kehadiran Bulanan</h2>
    <h4 class="text-center mb-5">{{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') }}</h4>

    <table class="table table-bordered">
        <thead class="table-light">
            <tr>
                <th>Nama</th>
                <th>Dept</th>
                <th>Hadir</th>
                <th>Telat</th>
                <th>Cuti</th>
                <th>Sakit</th>
                <th>Izin</th>
                <th>Absent</th>
                <th>Rate (%)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reportData as $data)
            <tr>
                <td>{{ $data['employee']['name'] }}</td>
                <td>{{ $data['employee']['department'] }}</td>
                <td><span class="badge-success">{{ $data['stats']['present'] ?? 0 }}</span></td>
                <td><span class="badge-info">{{ $data['stats']['late'] ?? 0 }}</span></td>
                <td><span class="badge-warning">{{ $data['stats']['leave'] ?? 0 }}</span></td>
                <td><span class="badge-warning">{{ $data['stats']['sick'] ?? 0 }}</span></td>
                <td><span class="badge-warning">{{ $data['stats']['permission'] ?? 0 }}</span></td>
                <td><span class="badge-danger">{{ $data['stats']['absent'] ?? 0 }}</span></td>
                <td><strong>{{ $data['attendance_rate'] }}%</strong></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <h4 class="mt-5">Detail Harian per Karyawan</h4>
    @foreach($reportData as $empId => $data)
        <h5 class="mt-4">{{ $data['employee']['name'] }} - {{ $data['employee']['department'] }}</h5>
        <table class="table table-bordered table-sm">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Status</th>
                    <th>Alasan</th>
                    <th>Clock In</th>
                    <th>Clock Out</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['details'] as $detail)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($detail['date'])->format('d M Y') }}</td>
                    <td>
                        @php
                            $badge = match($detail['status']) {
                                'Present', 'Late' => 'badge-success',
                                'Leave', 'Sick', 'Permission' => 'badge-warning',
                                default => 'badge-danger'
                            };
                        @endphp
                        <span class="{{ $badge }}">{{ $detail['status'] }}</span>
                    </td>
                    <td>{{ $detail['reason'] }}</td>
                    <td>{{ $detail['clock_in'] }}</td>
                    <td>{{ $detail['clock_out'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach
</body>
</html>