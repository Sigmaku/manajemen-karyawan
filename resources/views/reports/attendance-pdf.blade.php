<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan Kehadiran {{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') }}</title>

    <style>
        /* ===== PDF friendly ===== */
        @page { margin: 24px 28px; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111;
        }

        h1,h2,h3,h4,h5 { margin: 0; }
        .muted { color: #666; }

        .header {
            border-bottom: 2px solid #222;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }
        .header .title {
            font-size: 18px;
            font-weight: 700;
        }
        .header .subtitle {
            margin-top: 2px;
            font-size: 12px;
            color: #444;
        }

        .meta-row {
            margin-top: 10px;
            display: table;
            width: 100%;
        }
        .meta-col {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .meta-box {
            border: 1px solid #ddd;
            padding: 10px 12px;
            border-radius: 6px;
        }
        .meta-box strong { font-size: 12px; }

        .section-title {
            margin: 16px 0 8px;
            font-size: 13px;
            font-weight: 700;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 7px 8px;
            vertical-align: middle;
        }
        th {
            background: #f3f4f6;
            font-weight: 700;
            text-align: left;
        }

        .t-center { text-align: center; }
        .t-right { text-align: right; }

        .zebra tbody tr:nth-child(even) { background: #fafafa; }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 700;
            line-height: 1.2;
            border: 1px solid transparent;
            white-space: nowrap;
        }
        .b-success { background: #e8f5e9; color: #1b5e20; border-color: #c8e6c9; }
        .b-warning { background: #fff8e1; color: #7a5a00; border-color: #ffe0b2; }
        .b-danger  { background: #ffebee; color: #b71c1c; border-color: #ffcdd2; }
        .b-info    { background: #e3f2fd; color: #0d47a1; border-color: #bbdefb; }
        .b-muted   { background: #f5f5f5; color: #444; border-color: #e0e0e0; }

        .note {
            margin-top: 8px;
            font-size: 10px;
            color: #666;
        }

        /* Employee detail block */
        .employee-block {
            margin-top: 14px;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 10px 12px;
        }
        .employee-block .name {
            font-size: 12px;
            font-weight: 700;
        }
        .employee-block .dept {
            font-size: 10px;
            color: #666;
            margin-top: 2px;
        }

        .page-break { page-break-before: always; }

        .small { font-size: 10px; }
        .nowrap { white-space: nowrap; }

        /* widths for summary table */
        .w-name { width: 28%; }
        .w-dept { width: 16%; }
        .w-num  { width: 9%; }
        .w-rate { width: 10%; }

        /* widths for detail table */
        .w-date   { width: 18%; }
        .w-status { width: 14%; }
        .w-reason { width: 38%; }
        .w-time   { width: 15%; }
    </style>
</head>

<body>
    @php
        $periodLabel = \Carbon\Carbon::createFromFormat('Y-m', $month)->translatedFormat('F Y');
        $companyName = config('app.name', 'Employee Management System');

        // Optional global totals (kalau mau dipakai)
        $totalEmployees = is_array($reportData) ? count($reportData) : 0;
        $totalPresent = 0; $totalLate = 0; $totalLeave = 0; $totalAbsent = 0;

        foreach ($reportData as $row) {
            $totalPresent += $row['stats']['present'] ?? 0;
            $totalLate    += $row['stats']['late'] ?? 0;
            $totalLeave   += $row['stats']['leave'] ?? 0;
            $totalAbsent  += $row['stats']['absent'] ?? 0;
        }
    @endphp

    <!-- Header -->
    <div class="header">
        <div class="title">Laporan Kehadiran Bulanan</div>
        <div class="subtitle">{{ $periodLabel }} • {{ $companyName }}</div>

        <div class="meta-row">
            <div class="meta-col">
                <div class="meta-box">
                    <strong>Periode</strong><br>
                    <span class="muted">{{ $periodLabel }}</span>
                </div>
            </div>
            <div class="meta-col" style="padding-left:10px;">
                <div class="meta-box">
                    <strong>Ringkasan</strong><br>
                    <span class="muted">
                        Karyawan: {{ $totalEmployees }} • Hadir: {{ $totalPresent }} • Telat: {{ $totalLate }} • Cuti: {{ $totalLeave }} • Absen: {{ $totalAbsent }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary table -->
    <div class="section-title">Rekap Per Karyawan</div>
    <table class="zebra">
        <thead>
            <tr>
                <th class="w-name">Nama</th>
                <th class="w-dept">Dept</th>
                <th class="w-num t-center">Hadir</th>
                <th class="w-num t-center">Telat</th>
                <th class="w-num t-center">Cuti</th>
                <th class="w-num t-center">Absen</th>
                <th class="w-rate t-center">Rate</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reportData as $data)
                @php
                    $present = $data['stats']['present'] ?? 0;
                    $late    = $data['stats']['late'] ?? 0;
                    $leave   = $data['stats']['leave'] ?? 0;
                    $absent  = $data['stats']['absent'] ?? 0;
                    $rate    = $data['attendance_rate'] ?? 0;
                @endphp
                <tr>
                    <td>{{ $data['employee']['name'] ?? '-' }}</td>
                    <td>{{ $data['employee']['department'] ?? '-' }}</td>
                    <td class="t-center"><span class="badge b-success">{{ $present }}</span></td>
                    <td class="t-center"><span class="badge b-info">{{ $late }}</span></td>
                    <td class="t-center"><span class="badge b-warning">{{ $leave }}</span></td>
                    <td class="t-center"><span class="badge b-danger">{{ $absent }}</span></td>
                    <td class="t-center"><strong>{{ number_format((float)$rate, 1) }}%</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="note">
        Catatan: Rate = (Hadir / Total hari kerja pada periode) × 100. Perhitungan mengikuti data yang tersedia pada sistem.
    </div>


</body>
</html>
