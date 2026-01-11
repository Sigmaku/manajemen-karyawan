@php
    // Data dari controller
    $hasCheckedIn = $hasCheckedIn ?? false;
    $employeeId = $currentEmployeeId ?? ($employeeId ?? null);
    $employee = $employee ?? null;
    $todayAttendance = $todayAttendance ?? null;
@endphp

@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-qrcode me-2"></i>My Check-in QR Code
                    </h5>
                </div>
                <div class="card-body text-center">

                    <!-- Status dari PHP langsung -->
                    @if($hasCheckedIn && $employee)
                        <div class="alert alert-success mb-4" id="status-checked-in">
                            <i class="fas fa-check-circle me-2"></i>
                            <span>You have checked in today. Ready to generate QR Code.</span>
                        </div>

                        <!-- Employee Info -->
                        <div class="card bg-light mb-4">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center"
                                             style="width: 60px; height: 60px;">
                                            <i class="fas fa-user fa-2x"></i>
                                        </div>
                                    </div>
                                    <div class="text-start">
                                        <h5 class="mb-1">{{ $employee['name'] }}</h5>
                                        <p class="mb-1 text-muted">
                                            <i class="fas fa-id-card me-1"></i> {{ $employeeId }}
                                        </p>
                                        <p class="mb-0 text-muted">
                                            <i class="fas fa-building me-1"></i> {{ $employee['department'] ?? 'Department' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Check-in Details -->
                        @if($todayAttendance)
                        <div class="alert alert-info mb-4">
                            <div class="row">
                                <div class="col-6 text-start">
                                    <small>Check-in Time:</small><br>
                                    <strong>{{ $todayAttendance['checkIn'] ?? 'N/A' }}</strong>
                                </div>
                                <div class="col-6 text-end">
                                    <small>Location:</small><br>
                                    <strong>{{ $todayAttendance['location'] ?? 'Office' }}</strong>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- QR Code Display Area -->
                        <div id="barcode-display" class="d-none">
                            <!-- QR Code Display -->
                            <div class="mb-4">
                                <div id="qrcode-container" class="d-inline-block p-3 border rounded bg-white" style="width: 220px; height: 220px;">
                                    <div class="qr-loading">
                                        <i class="fas fa-qrcode fa-3x text-muted"></i>
                                        <p class="text-muted small mt-2">QR Code will appear here</p>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted">Scan this QR code to verify attendance</small>
                                </div>
                            </div>

                            <!-- Barcode Text -->
                            <div class="mb-4">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title">QR Code Data</h6>
                                        <code id="barcode-text" class="d-block p-2 bg-white border rounded text-break small"></code>
                                        <button class="btn btn-sm btn-outline-secondary mt-2" onclick="copyBarcode()">
                                            <i class="fas fa-copy me-1"></i> Copy Data
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Details Card -->
                            <div class="mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-6 text-start">
                                                <small class="text-muted">Name:</small><br>
                                                <strong id="emp-name">{{ $employee['name'] }}</strong>
                                            </div>
                                            <div class="col-6 text-end">
                                                <small class="text-muted">ID:</small><br>
                                                <strong id="emp-id">{{ $employeeId }}</strong>
                                            </div>
                                        </div>
                                        <hr>
                                        <div class="row">
                                            <div class="col-6 text-start">
                                                <small class="text-muted">Check-in Time:</small><br>
                                                <strong id="checkin-time">{{ $todayAttendance['checkIn'] ?? 'N/A' }}</strong>
                                            </div>
                                            <div class="col-6 text-end">
                                                <small class="text-muted">Valid Until:</small><br>
                                                <strong id="valid-until" class="text-danger"></strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Instructions -->
                            <div class="alert alert-warning">
                                <h6><i class="fas fa-exclamation-circle me-2"></i>Instructions:</h6>
                                <ol class="mb-0 small">
                                    <li>Show this QR code to supervisor/administrator</li>
                                    <li>QR code is valid for <strong>5 minutes</strong> only</li>
                                    <li>Regenerate if QR code expires</li>
                                    <li>QR code will be verified against your check-in record</li>
                                </ol>
                            </div>
                        </div>

                        <!-- Controls -->
                        <div class="mt-4">
                            <button id="generate-btn" class="btn btn-success" onclick="generateBarcode()">
                                <i class="fas fa-qrcode me-2"></i>Generate QR Code
                            </button>
                            <button id="refresh-btn" class="btn btn-outline-primary d-none" onclick="refreshBarcode()">
                                <i class="fas fa-redo me-2"></i>Refresh
                            </button>
                            <button class="btn btn-outline-secondary" onclick="printBarcode()" id="print-btn" style="display: none;">
                                <i class="fas fa-print me-2"></i>Print
                            </button>
                        </div>

                        <!-- Countdown Timer -->
                        <div class="mt-3">
                            <div class="progress" style="height: 6px;">
                                <div id="countdown-bar" class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
                            </div>
                            <small class="text-muted" id="countdown-text">Expires in: 5:00</small>
                        </div>

                    @else
                        <!-- NOT CHECKED IN -->
                        <div class="alert alert-warning mb-4" id="status-not-checked">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <span>You need to check in first before generating QR code.</span>
                        </div>

                        <div id="not-checked-in">
                            <div class="py-4">
                                <i class="fas fa-clock fa-3x text-muted mb-3"></i>
                                <p class="text-muted">You haven't checked in today</p>
                                <a href="{{ route('attendance.dashboard') }}" class="btn btn-primary">
                                    <i class="fas fa-sign-in-alt me-2"></i>Go to Check-in
                                </a>
                            </div>
                        </div>
                    @endif

                </div>
                <div class="card-footer bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            <i class="fas fa-clock me-1"></i>
                            <span id="current-time">{{ date('H:i:s') }}</span>
                        </small>
                        <small class="text-muted">
                            <i class="fas fa-calendar me-1"></i>
                            {{ date('F d, Y') }}
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-success">
                    <i class="fas fa-check-circle me-2"></i>Success!
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4">
                <i class="fas fa-check-circle fa-4x text-success mb-4"></i>
                <h5>QR Code Generated</h5>
                <p class="mb-0">Your check-in QR code has been generated successfully.</p>
                <p class="text-muted small">Valid for 5 minutes</p>
            </div>
            <div class="modal-footer border-0 justify-content-center">
                <button type="button" class="btn btn-success" data-bs-dismiss="modal">
                    <i class="fas fa-check me-2"></i>OK
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Error Modal -->
<div class="modal fade" id="errorModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>Error
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4">
                <i class="fas fa-exclamation-circle fa-4x text-danger mb-4"></i>
                <h5 id="error-title"></h5>
                <p class="mb-0" id="error-message"></p>
            </div>
            <div class="modal-footer border-0 justify-content-center">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Close
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    #qrcode-container {
        display: flex;
        align-items: center;
        justify-content: center;
        background: white;
    }

    .qr-loading {
        text-align: center;
        padding: 20px;
    }

    .barcode-fallback {
        font-family: 'Courier New', monospace;
        text-align: center;
        padding: 10px;
        background: #f8f9fa;
        border-radius: 5px;
        border: 1px dashed #dee2e6;
    }

    .barcode-title {
        font-weight: bold;
        color: #0d6efd;
        font-size: 14px;
        margin-bottom: 5px;
    }

    .barcode-data {
        font-size: 18px;
        letter-spacing: 2px;
        color: #000;
        margin-bottom: 5px;
    }

    .barcode-time {
        font-size: 12px;
        color: #6c757d;
        margin-bottom: 5px;
    }

    .barcode-valid {
        font-size: 11px;
        color: #dc3545;
        font-weight: bold;
    }

    .countdown-warning {
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.5; }
        100% { opacity: 1; }
    }
</style>
@endpush

@push('scripts')
<!-- QR Code Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<script>
// Global variables
let currentBarcode = null;
let countdownInterval = null;
let remainingTime = 300;
let qrCodeInstance = null;

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    console.log('QRCode library available:', typeof QRCode !== 'undefined');
    updateCurrentTime();
});

// Generate barcode
async function generateBarcode() {
    const generateBtn = document.getElementById('generate-btn');
    const originalText = generateBtn.innerHTML;

    generateBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Generating...';
    generateBtn.disabled = true;

    try {
        const response = await fetch('{{ route("barcode.generate.checkin") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });

        const result = await response.json();

        if (result.success) {
            currentBarcode = result.data;
            displayBarcode(result.data);

            const modal = new bootstrap.Modal(document.getElementById('successModal'));
            modal.show();

        } else if (result.code === 'NOT_CHECKED_IN') {
            showError('Not Checked In', 'Please check in first before generating QR code');
            setTimeout(() => {
                window.location.href = '{{ route("attendance.dashboard") }}';
            }, 2000);
        } else {
            showError('Generation Failed', result.message);
        }

    } catch (error) {
        console.error('Generate error:', error);
        showError('Network Error', 'Unable to generate QR code. Please check connection.');
    } finally {
        generateBtn.innerHTML = originalText;
        generateBtn.disabled = false;
    }
}

// Display barcode dengan QR Code
function displayBarcode(data) {
    // Show barcode display area
    document.getElementById('barcode-display').classList.remove('d-none');
    document.getElementById('generate-btn').classList.add('d-none');
    document.getElementById('refresh-btn').classList.remove('d-none');
    document.getElementById('print-btn').style.display = 'inline-block';

    // Set employee info
    document.getElementById('emp-name').textContent = data.employee_name;
    document.getElementById('emp-id').textContent = data.employee_id;
    document.getElementById('checkin-time').textContent = data.check_in_time;
    document.getElementById('valid-until').textContent = data.valid_until.split(' ')[1];

    // Set barcode text
    document.getElementById('barcode-text').textContent = data.barcode;

    // Generate QR Code
    generateQRCode(data.barcode);

    // Start countdown
    startCountdown(300);
}

// Generate QR Code
function generateQRCode(text) {
    const container = document.getElementById('qrcode-container');

    if (!container) {
        console.error('QR Code container not found');
        return;
    }

    // Clear container
    container.innerHTML = '';

    // Cek apakah library tersedia
    if (typeof QRCode === 'undefined') {
        console.error('QRCode library not loaded');
        container.innerHTML = `
            <div class="barcode-fallback">
                <div class="barcode-title">CHECK-IN CODE</div>
                <div class="barcode-data">${currentBarcode.employee_id}</div>
                <div class="barcode-time">${currentBarcode.check_in_time}</div>
                <div class="barcode-valid">Valid: 5 minutes</div>
            </div>
        `;
        return;
    }

    try {
        // Buat QR Code baru
        qrCodeInstance = new QRCode(container, {
            text: text,
            width: 200,
            height: 200,
            colorDark: "#000000",
            colorLight: "#FFFFFF",
            correctLevel: QRCode.CorrectLevel.H
        });

        console.log('QR Code generated successfully');

    } catch (error) {
        console.error('QR Code generation error:', error);

        // Fallback
        container.innerHTML = `
            <div class="barcode-fallback">
                <div class="barcode-title">CHECK-IN CODE</div>
                <div class="barcode-data">${currentBarcode.employee_id}</div>
                <div class="barcode-time">${currentBarcode.check_in_time}</div>
                <div class="barcode-valid">Valid: 5 minutes</div>
            </div>
        `;
    }
}

// Start countdown timer
function startCountdown(seconds) {
    remainingTime = seconds;

    if (countdownInterval) {
        clearInterval(countdownInterval);
    }

    updateCountdownDisplay();

    countdownInterval = setInterval(() => {
        remainingTime--;
        updateCountdownDisplay();

        if (remainingTime <= 0) {
            clearInterval(countdownInterval);
            barcodeExpired();
        }
    }, 1000);
}

// Update countdown display
function updateCountdownDisplay() {
    const minutes = Math.floor(remainingTime / 60);
    const seconds = remainingTime % 60;

    document.getElementById('countdown-text').textContent =
        `Expires in: ${minutes}:${seconds.toString().padStart(2, '0')}`;

    const percentage = (remainingTime / 300) * 100;
    document.getElementById('countdown-bar').style.width = `${percentage}%`;

    if (remainingTime <= 60) {
        document.getElementById('countdown-bar').className = 'progress-bar bg-danger countdown-warning';
    } else if (remainingTime <= 120) {
        document.getElementById('countdown-bar').className = 'progress-bar bg-warning';
    } else {
        document.getElementById('countdown-bar').className = 'progress-bar bg-success';
    }
}

// Handle QR code expiration
function barcodeExpired() {
    document.getElementById('countdown-text').textContent = 'QR Code Expired';
    document.getElementById('countdown-text').className = 'text-danger';
    document.getElementById('valid-until').className = 'text-danger';

    // Show refresh prompt
    const refreshBtn = document.getElementById('refresh-btn');
    refreshBtn.innerHTML = '<i class="fas fa-sync-alt me-2"></i>Regenerate Expired QR Code';
    refreshBtn.className = 'btn btn-warning';
}

// Refresh/regenerate QR code
async function refreshBarcode() {
    await generateBarcode();
}

// Copy barcode to clipboard
function copyBarcode() {
    const barcodeText = document.getElementById('barcode-text').textContent;

    navigator.clipboard.writeText(barcodeText).then(() => {
        showToast('QR Code data copied to clipboard!', 'success');
    }).catch(err => {
        console.error('Copy failed:', err);
        showToast('Failed to copy data', 'error');
    });
}

// Print QR code
function printBarcode() {
    if (!currentBarcode) return;

    const printContent = `
        <html>
        <head>
            <title>Check-in QR Code - ${currentBarcode.employee_name}</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; text-align: center; }
                .header { margin-bottom: 20px; }
                .qrcode { margin: 20px auto; border: 1px solid #ddd; padding: 10px; display: inline-block; }
                .details { margin: 20px 0; text-align: left; max-width: 400px; margin-left: auto; margin-right: auto; }
                .footer { margin-top: 30px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class="header">
                <h3>Check-in Verification QR Code</h3>
                <p>${new Date().toLocaleString()}</p>
            </div>

            <div class="qrcode">
                ${document.querySelector('#qrcode-container').innerHTML}
            </div>

            <div class="details">
                <p><strong>Employee:</strong> ${currentBarcode.employee_name}</p>
                <p><strong>ID:</strong> ${currentBarcode.employee_id}</p>
                <p><strong>Check-in Time:</strong> ${currentBarcode.check_in_time}</p>
                <p><strong>Date:</strong> ${currentBarcode.date}</p>
                <p><strong>Valid Until:</strong> ${currentBarcode.valid_until}</p>
                <hr>
                <p><strong>QR Code Data:</strong><br>
                <code style="font-size: 10px;">${currentBarcode.barcode}</code></p>
            </div>

            <div class="footer">
                <p>Present this QR code to supervisor for attendance verification</p>
                <p>${document.querySelector('meta[name="company"]')?.content || 'Company Name'}</p>
            </div>
        </body>
        </html>
    `;

    const printWindow = window.open('', '_blank');
    printWindow.document.write(printContent);
    printWindow.document.close();
    printWindow.print();
}

// Update current time
function updateCurrentTime() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('en-US', {
        hour12: false,
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });

    document.getElementById('current-time').textContent = timeString;
    setTimeout(updateCurrentTime, 1000);
}

// Show error modal
function showError(title, message) {
    document.getElementById('error-title').textContent = title;
    document.getElementById('error-message').textContent = message;

    const modal = new bootstrap.Modal(document.getElementById('errorModal'));
    modal.show();
}

// Show toast notification
function showToast(message, type = 'info') {
    const toastHtml = `
        <div class="toast align-items-center text-bg-${type} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-${type === 'success' ? 'check' : 'info'}-circle me-2"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;

    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(container);
    }

    container.insertAdjacentHTML('beforeend', toastHtml);
    const toastEl = container.lastElementChild;
    const bsToast = new bootstrap.Toast(toastEl);
    bsToast.show();

    setTimeout(() => {
        bsToast.hide();
        setTimeout(() => toastEl.remove(), 300);
    }, 3000);
}
</script>
@endpush
