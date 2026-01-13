@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2"></i>Barcode Verification History
                    </h5>
                    <div>
                        <span class="badge bg-light text-dark">
                            Total: {{ $total ?? 0 }}
                        </span>
                    </div>
                </div>
                <div class="card-body">

                    <!-- Filter Section -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-2">
                            <label class="form-label">Employee</label>
                            <select id="employee-filter" class="form-select form-select-sm">
                                <option value="">All Employees</option>
                                @if(isset($employees) && count($employees) > 0)
                                    @foreach($employees as $id => $employee)
                                        <option value="{{ $id }}">{{ $employee['name'] }} ({{ $id }})</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="form-label">Date From</label>
                            <input type="date" id="date-from" class="form-control form-control-sm"
                                   value="{{ date('Y-m-d', strtotime('-7 days')) }}">
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="form-label">Date To</label>
                            <input type="date" id="date-to" class="form-control form-control-sm"
                                   value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="form-label">Status</label>
                            <select id="status-filter" class="form-select form-select-sm">
                                <option value="">All Status</option>
                                <option value="verified">Verified</option>
                                <option value="failed">Failed</option>
                                <option value="expired">Expired</option>
                            </select>
                        </div>
                    </div>

                    <!-- Stats Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3 col-6">
                            <div class="card text-center bg-light">
                                <div class="card-body py-3">
                                    <h6 class="text-muted mb-2">Today</h6>
                                    <h4 id="today-count">0</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="card text-center bg-light">
                                <div class="card-body py-3">
                                    <h6 class="text-muted mb-2">This Week</h6>
                                    <h4 id="week-count">0</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="card text-center bg-light">
                                <div class="card-body py-3">
                                    <h6 class="text-muted mb-2">Verified</h6>
                                    <h4 id="verified-count" class="text-success">0</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="card text-center bg-light">
                                <div class="card-body py-3">
                                    <h6 class="text-muted mb-2">Failed</h6>
                                    <h4 id="failed-count" class="text-danger">0</h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="table-responsive">
                        <table class="table table-hover table-sm" id="verification-table">
                            <thead class="table-light">
                                <tr>
                                    <th width="150">Time</th>
                                    <th>Employee</th>
                                    <th>Verified By</th>
                                    <th>Status</th>
                                    <th>Details</th>
                                    <th width="100">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(isset($verifications) && count($verifications) > 0)
                                    @foreach($verifications as $logId => $log)
                                    @php
                                        $employee = $employees[$log['employee_id']] ?? null;
                                        $verificationTime = \Carbon\Carbon::parse($log['verified_at'] ?? now());
                                        $isToday = $verificationTime->isToday();
                                        $isThisWeek = $verificationTime->isCurrentWeek();

                                        // Determine status based on barcode_data
                                        $status = 'verified';
                                        $statusClass = 'success';
                                        $statusIcon = 'check-circle';

                                        if (isset($log['error']) || strpos($log['barcode_data'] ?? '', 'expired') !== false) {
                                            $status = 'expired';
                                            $statusClass = 'warning';
                                            $statusIcon = 'exclamation-triangle';
                                        } elseif (isset($log['failed']) || !isset($log['verified_by'])) {
                                            $status = 'failed';
                                            $statusClass = 'danger';
                                            $statusIcon = 'times-circle';
                                        }
                                    @endphp
                                    <tr data-employee="{{ $log['employee_id'] }}"
                                        data-date="{{ $verificationTime->format('Y-m-d') }}"
                                        data-status="{{ $status }}"
                                        class="{{ $isToday ? 'table-info' : '' }}">
                                        <td>
                                            <div class="small">
                                                {{ $verificationTime->format('H:i') }}
                                            </div>
                                            <div class="text-muted smaller">
                                                {{ $verificationTime->format('M d, Y') }}
                                            </div>
                                        </td>
                                        <td>
                                            @if($employee)
                                            <div class="d-flex align-items-center">
                                                <div class="me-2">
                                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center"
                                                         style="width: 32px; height: 32px;">
                                                        <i class="fas fa-user"></i>
                                                    </div>
                                                </div>
                                                <div>
                                                    <div class="fw-medium">{{ $employee['name'] }}</div>
                                                    <small class="text-muted">{{ $log['employee_id'] }}</small>
                                                </div>
                                            </div>
                                            @else
                                            <div class="text-muted">{{ $log['employee_id'] }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                {{ $log['verified_by'] ?? 'System' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $statusClass }}">
                                                <i class="fas fa-{{ $statusIcon }} me-1"></i>
                                                {{ ucfirst($status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="small">
                                                <div>Barcode:
                                                    <code class="text-muted" title="{{ $log['barcode_data'] ?? 'N/A' }}">
                                                        {{ Str::limit($log['barcode_data'] ?? '', 30) }}
                                                    </code>
                                                </div>
                                                @if(isset($log['ip_address']))
                                                <div class="text-muted">IP: {{ $log['ip_address'] }}</div>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"
                                                    onclick="showDetails('{{ $logId }}')"
                                                    title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <i class="fas fa-history fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">No verification records found</p>
                                            @if(isset($error))
                                            <div class="alert alert-danger small">
                                                Error: {{ $error }}
                                            </div>
                                            @endif
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>

                    <!-- Export Section -->
                    @if(isset($verifications) && count($verifications) > 0)
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="btn-group">
                                <button class="btn btn-outline-primary btn-sm" onclick="exportToCSV()">
                                    <i class="fas fa-download me-2"></i>Export CSV
                                </button>
                                <button class="btn btn-outline-secondary btn-sm" onclick="printTable()">
                                    <i class="fas fa-print me-2"></i>Print
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6 text-end">
                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-info" onclick="refreshData()">
                                    <i class="fas fa-sync-alt me-1"></i>Refresh
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="clearFilters()">
                                    <i class="fas fa-times me-1"></i>Clear Filters
                                </button>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Verification Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="details-content">
                    <div class="text-center py-5">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p class="mt-2">Loading details...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .verification-row:hover {
        background-color: #f8f9fa;
        cursor: pointer;
    }

    .smaller {
        font-size: 0.75rem;
    }

    #verification-table tbody tr {
        transition: all 0.2s ease;
    }

    .barcode-preview {
        font-family: 'Courier New', monospace;
        background: #f8f9fa;
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 12px;
        word-break: break-all;
    }
</style>
@endpush

@push('scripts')
<script>
// Global variables
let allVerifications = @json($verifications ?? []);
let employees = @json($employees ?? []);

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateStats();
    setupFilters();

    // Auto-refresh every 30 seconds
    setInterval(refreshData, 30000);
});

// Update statistics
function updateStats() {
    const today = new Date().toISOString().split('T')[0];
    const todayCount = document.querySelectorAll(`[data-date="${today}"]`).length;
    const weekCount = document.querySelectorAll('[data-date]').length; // Simplified

    const verifiedCount = document.querySelectorAll('[data-status="verified"]').length;
    const failedCount = document.querySelectorAll('[data-status="failed"]').length;
    const expiredCount = document.querySelectorAll('[data-status="expired"]').length;

    document.getElementById('today-count').textContent = todayCount;
    document.getElementById('week-count').textContent = weekCount;
    document.getElementById('verified-count').textContent = verifiedCount;
    document.getElementById('failed-count').textContent = failedCount + expiredCount;
}

// Setup filter functionality
function setupFilters() {
    const employeeFilter = document.getElementById('employee-filter');
    const dateFrom = document.getElementById('date-from');
    const dateTo = document.getElementById('date-to');
    const statusFilter = document.getElementById('status-filter');

    function applyFilters() {
        const selectedEmployee = employeeFilter.value;
        const selectedDateFrom = dateFrom.value;
        const selectedDateTo = dateTo.value;
        const selectedStatus = statusFilter.value;

        const rows = document.querySelectorAll('#verification-table tbody tr');
        let visibleCount = 0;

        rows.forEach(row => {
            if (row.cells.length < 2) return; // Skip empty rows

            let show = true;
            const employeeId = row.getAttribute('data-employee');
            const date = row.getAttribute('data-date');
            const status = row.getAttribute('data-status');

            // Employee filter
            if (selectedEmployee && employeeId !== selectedEmployee) {
                show = false;
            }

            // Date filter
            if (selectedDateFrom && date < selectedDateFrom) {
                show = false;
            }
            if (selectedDateTo && date > selectedDateTo) {
                show = false;
            }

            // Status filter
            if (selectedStatus && status !== selectedStatus) {
                show = false;
            }

            row.style.display = show ? '' : 'none';
            if (show) visibleCount++;
        });

        // Update visible count in header
        const badge = document.querySelector('.card-header .badge');
        if (badge) {
            badge.innerHTML = `Showing: ${visibleCount} of {{ $total ?? 0 }}`;
        }
    }

    // Add event listeners
    [employeeFilter, dateFrom, dateTo, statusFilter].forEach(filter => {
        filter.addEventListener('change', applyFilters);
    });

    // Apply filters initially
    applyFilters();
}

// Clear all filters
function clearFilters() {
    document.getElementById('employee-filter').value = '';
    document.getElementById('date-from').value = '{{ date("Y-m-d", strtotime("-7 days")) }}';
    document.getElementById('date-to').value = '{{ date("Y-m-d") }}';
    document.getElementById('status-filter').value = '';

    // Re-apply filters
    const rows = document.querySelectorAll('#verification-table tbody tr');
    rows.forEach(row => row.style.display = '');

    // Reset badge
    const badge = document.querySelector('.card-header .badge');
    if (badge) {
        badge.innerHTML = `Total: {{ $total ?? 0 }}`;
    }

    updateStats();
}

// Show details modal
function showDetails(logId) {
    const log = allVerifications[logId];
    if (!log) return;

    const employee = employees[log.employee_id] || {};
    const verificationTime = new Date(log.verified_at || new Date());

    // Build details HTML
    const detailsHtml = `
        <div class="row">
            <div class="col-md-6">
                <h6>Employee Information</h6>
                <table class="table table-sm">
                    <tr>
                        <td width="120"><strong>Name:</strong></td>
                        <td>${employee.name || log.employee_id}</td>
                    </tr>
                    <tr>
                        <td><strong>Employee ID:</strong></td>
                        <td>${log.employee_id}</td>
                    </tr>
                    <tr>
                        <td><strong>Department:</strong></td>
                        <td>${employee.department || 'N/A'}</td>
                    </tr>
                    <tr>
                        <td><strong>Position:</strong></td>
                        <td>${employee.position || 'N/A'}</td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6>Verification Details</h6>
                <table class="table table-sm">
                    <tr>
                        <td width="120"><strong>Verified By:</strong></td>
                        <td>${log.verified_by || 'System'}</td>
                    </tr>
                    <tr>
                        <td><strong>Time:</strong></td>
                        <td>${verificationTime.toLocaleString()}</td>
                    </tr>
                    <tr>
                        <td><strong>IP Address:</strong></td>
                        <td>${log.ip_address || 'N/A'}</td>
                    </tr>
                    <tr>
                        <td><strong>User Agent:</strong></td>
                        <td><small class="text-muted">${log.user_agent || 'N/A'}</small></td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-12">
                <h6>Barcode Data</h6>
                <div class="barcode-preview mb-3">
                    ${log.barcode_data || 'No barcode data'}
                </div>

                <h6>Barcode Breakdown</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr>
                                <th>Part</th>
                                <th>Value</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${parseBarcodeData(log.barcode_data)}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        ${log.error ? `
        <div class="row mt-3">
            <div class="col-12">
                <div class="alert alert-danger">
                    <h6><i class="fas fa-exclamation-triangle me-2"></i>Error Details</h6>
                    <p class="mb-0">${log.error}</p>
                </div>
            </div>
        </div>
        ` : ''}
    `;

    document.getElementById('details-content').innerHTML = detailsHtml;

    const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
    modal.show();
}

// Parse barcode data for display
function parseBarcodeData(barcodeData) {
    if (!barcodeData) return '<tr><td colspan="3" class="text-center">No barcode data</td></tr>';

    const parts = barcodeData.split(':');
const descriptions = [
  'Employee ID',
  'Timestamp',
  'Date',
  'Check-in Time',
  'Security Hash'
];


    let html = '';
    parts.forEach((part, index) => {
        let value = part;
        let description = descriptions[index] || 'Additional Data';


// Format timestamp (index 1)
if (index === 1 && !isNaN(part)) {
    const d = new Date(parseInt(part) * 1000);
    value = `${part} (${d.toLocaleString()})`;
}


        // Truncate long values
        if (value.length > 50) {
            value = value.substring(0, 50) + '...';
        }

        html += `
            <tr>
                <td>${index + 1}</td>
                <td><code>${value}</code></td>
                <td>${description}</td>
            </tr>
        `;
    });

    return html;
}

// Export to CSV
function exportToCSV() {
    const rows = document.querySelectorAll('#verification-table tbody tr:not([style*="display: none"])');

    let csv = 'Time,Employee ID,Employee Name,Verified By,Status,IP Address,Barcode Data\n';

    rows.forEach(row => {
        if (row.cells.length < 6) return;

        const cells = row.cells;
        const time = cells[0].textContent.trim().replace(/\n/g, ' ');
        const employee = cells[1].textContent.trim();
        const verifiedBy = cells[2].textContent.trim();
        const status = cells[3].textContent.trim();

        // Get additional data from row attributes
        const employeeId = row.getAttribute('data-employee');
        const barcodeData = allVerifications[Object.keys(allVerifications).find(key =>
            allVerifications[key].employee_id === employeeId
        )]?.barcode_data || '';

        const ipAddress = allVerifications[Object.keys(allVerifications).find(key =>
            allVerifications[key].employee_id === employeeId
        )]?.ip_address || '';

        csv += `"${time}","${employeeId}","${employee}","${verifiedBy}","${status}","${ipAddress}","${barcodeData}"\n`;
    });

    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);

    link.setAttribute('href', url);
    link.setAttribute('download', `barcode-verifications-${new Date().toISOString().split('T')[0]}.csv`);
    link.style.visibility = 'hidden';

    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Print table
function printTable() {
    const printWindow = window.open('', '_blank');
    const table = document.getElementById('verification-table').cloneNode(true);

    // Remove action column for printing
    const rows = table.querySelectorAll('tr');
    rows.forEach(row => {
        if (row.cells.length > 0) {
            row.deleteCell(5); // Remove actions column
        }
    });

    printWindow.document.write(`
        <html>
        <head>
            <title>Barcode Verification History</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                h1 { color: #333; margin-bottom: 20px; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th { background: #f8f9fa; border: 1px solid #dee2e6; padding: 8px; text-align: left; }
                td { border: 1px solid #dee2e6; padding: 8px; }
                .badge { padding: 2px 8px; border-radius: 4px; font-size: 12px; }
                .bg-success { background: #28a745; color: white; }
                .bg-danger { background: #dc3545; color: white; }
                .bg-warning { background: #ffc107; color: black; }
                .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <h1>Barcode Verification History</h1>
            <p>Generated: ${new Date().toLocaleString()}</p>
            ${table.outerHTML}
            <div class="footer">
                <p>${document.querySelector('meta[name="company"]')?.content || 'Company Name'}</p>
                <p>Total Records: {{ $total ?? 0 }}</p>
            </div>
        </body>
        </html>
    `);

    printWindow.document.close();
    printWindow.print();
}

// Refresh data
function refreshData() {
    // Show loading indicator
    const refreshBtn = document.querySelector('[onclick="refreshData()"]');
    const originalHtml = refreshBtn.innerHTML;
    refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Refreshing...';
    refreshBtn.disabled = true;

    // Simulate refresh (in real app, this would be an AJAX call)
    setTimeout(() => {
        window.location.reload();
    }, 1000);
}
</script>
@endpush
