@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-8">
            <!-- Scanner Section -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-qrcode me-2"></i>Employee Check-in Barcode Scanner
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Scanner Container -->
                    <div class="scanner-container mb-4">
                        <div id="reader" class="border rounded" style="width: 100%; min-height: 400px; position: relative;">
                            <!-- Scanner overlay will be added here -->
                        </div>
                        <div class="scanner-overlay text-center mt-2">
                            <div class="scan-guide">
                                <i class="fas fa-qrcode fa-3x text-primary mb-3"></i>
                                <p class="text-muted">Position the barcode within the frame</p>
                            </div>
                        </div>
                    </div>

                    <!-- Scanner Controls -->
                    <div class="text-center mb-4">
                        <div class="btn-group" role="group">
                            <button id="start-scanner" class="btn btn-success" onclick="startScanner()">
                                <i class="fas fa-play me-2"></i>Start Scanner
                            </button>
                            <button id="stop-scanner" class="btn btn-danger" onclick="stopScanner()" disabled>
                                <i class="fas fa-stop me-2"></i>Stop Scanner
                            </button>
                            <button class="btn btn-info" onclick="switchCamera()">
                                <i class="fas fa-sync-alt me-2"></i>Switch Camera
                            </button>
                            <button class="btn btn-warning" onclick="toggleTorch()">
                                <i class="fas fa-lightbulb me-2"></i>Torch
                            </button>
                        </div>
                    </div>

                    <!-- Manual Input -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Manual Barcode Entry</h6>
                        </div>
                        <div class="card-body">
                            <div class="input-group">
                                <input type="text" id="manual-barcode" class="form-control"
                                       placeholder="Paste or type barcode here...">
                                <button class="btn btn-primary" onclick="verifyManualBarcode()">
                                    <i class="fas fa-check me-2"></i>Verify
                                </button>
                            </div>
                            <small class="text-muted mt-2 d-block">
                                Enter the barcode manually if scanner cannot read it
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Verification Result -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-check-circle me-2"></i>Verification Result
                    </h5>
                </div>
                <div class="card-body">
                    <div id="verification-result" class="text-center py-4">
                        <div class="empty-state">
                            <i class="fas fa-qrcode fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Scan result will appear here</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Scans -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2"></i>Recent Scans
                        <span class="badge bg-primary float-end" id="recent-count">0</span>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div id="recent-scans-list" style="max-height: 300px; overflow-y: auto;">
                        <div class="text-center py-4">
                            <i class="fas fa-clock fa-2x text-muted mb-3"></i>
                            <p class="text-muted">No scans yet</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar me-2"></i>Today's Stats
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <div class="stat-box bg-primary text-white p-3 rounded">
                                <h3 id="today-scans">0</h3>
                                <small>Scans Today</small>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="stat-box bg-success text-white p-3 rounded">
                                <h3 id="verified-count">0</h3>
                                <small>Verified</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-box bg-warning text-white p-3 rounded">
                                <h3 id="failed-count">0</h3>
                                <small>Failed</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-box bg-info text-white p-3 rounded">
                                <h3 id="success-rate">0%</h3>
                                <small>Success Rate</small>
                            </div>
                        </div>
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
                    <i class="fas fa-check-circle me-2"></i>Verification Successful!
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4">
                <div class="mb-4">
                    <i class="fas fa-check-circle fa-4x text-success"></i>
                </div>
                <h4 id="employee-name">Employee Name</h4>
                <p class="text-muted mb-4" id="employee-details">Employee Details</p>

                <div class="verification-details bg-light p-3 rounded mb-4">
                    <div class="row">
                        <div class="col-6 text-start">
                            <small class="text-muted">Check-in Time:</small>
                            <div class="fw-bold" id="checkin-time">--:--</div>
                        </div>
                        <div class="col-6 text-end">
                            <small class="text-muted">Verified At:</small>
                            <div class="fw-bold" id="verified-time">--:--</div>
                        </div>
                    </div>
                </div>

                <audio id="success-sound" src="{{ asset('sounds/success.mp3') }}" preload="auto"></audio>
            </div>
            <div class="modal-footer border-0 justify-content-center">
                <button type="button" class="btn btn-success" data-bs-dismiss="modal" onclick="continueScanning()">
                    <i class="fas fa-qrcode me-2"></i>Scan Next
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
                    <i class="fas fa-times-circle me-2"></i>Verification Failed
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4">
                <div class="mb-4">
                    <i class="fas fa-times-circle fa-4x text-danger"></i>
                </div>
                <h5 id="error-title">Verification Failed</h5>
                <p class="text-muted" id="error-message">Error message here</p>
            </div>
            <div class="modal-footer border-0 justify-content-center">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="continueScanning()">
                    <i class="fas fa-redo me-2"></i>Try Again
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .scanner-container {
        position: relative;
        overflow: hidden;
    }

    #reader {
        background: #000;
    }

    .scanner-overlay {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 100;
    }

    .scan-guide {
        background: rgba(0, 0, 0, 0.7);
        color: white;
        padding: 20px;
        border-radius: 10px;
    }

    .scan-line {
        position: absolute;
        width: 100%;
        height: 3px;
        background: #00ff00;
        box-shadow: 0 0 10px #00ff00;
        animation: scan 2s linear infinite;
        top: 50%;
    }

    @keyframes scan {
        0% { top: 0%; }
        100% { top: 100%; }
    }

    .stat-box {
        transition: transform 0.3s;
    }

    .stat-box:hover {
        transform: translateY(-5px);
    }

    .verification-item {
        padding: 10px 15px;
        border-bottom: 1px solid #eee;
        transition: background 0.2s;
    }

    .verification-item:hover {
        background: #f8f9fa;
    }

    .verification-item.success {
        border-left: 4px solid #28a745;
    }

    .verification-item.error {
        border-left: 4px solid #dc3545;
    }

    .qr-box {
        width: 200px;
        height: 200px;
        margin: 0 auto;
        border: 2px solid #007bff;
        border-radius: 10px;
        padding: 10px;
        background: white;
    }
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
<script>
// Global variables
let html5QrCode = null;
let currentCameraId = null;
let isScanning = false;
let currentTorchState = false;
let recentScans = [];
let scanStats = {
    today: 0,
    verified: 0,
    failed: 0
};

// Initialize scanner
document.addEventListener('DOMContentLoaded', function() {
    initScanner();
    loadRecentScans();
    updateStatsDisplay();

    // Check camera permissions
    checkCameraPermission();

    // Auto-refresh recent scans every 10 seconds
    setInterval(loadRecentScans, 10000);
});

function initScanner() {
    html5QrCode = new Html5Qrcode("reader");

    // Get available cameras
    Html5Qrcode.getCameras().then(devices => {
        if (devices && devices.length) {
            currentCameraId = devices[0].id;
            console.log(`Found ${devices.length} cameras. Using: ${devices[0].label}`);
        } else {
            showError('No cameras found', 'Please connect a camera to use the scanner.');
        }
    }).catch(err => {
        console.error("Camera error:", err);
        showError('Camera Error', 'Unable to access camera. Please check permissions.');
    });
}

function startScanner() {
    if (!currentCameraId) {
        alert('No camera available. Please connect a camera.');
        return;
    }

    const config = {
        fps: 10,
        qrbox: { width: 250, height: 250 },
        aspectRatio: 1.0,
        facingMode: { exact: "environment" }
    };

    html5QrCode.start(
        currentCameraId,
        config,
        onScanSuccess,
        onScanError
    ).then(() => {
        isScanning = true;
        document.getElementById('start-scanner').disabled = true;
        document.getElementById('stop-scanner').disabled = false;
        document.querySelector('.scan-guide').style.display = 'none';

        // Add scan line animation
        const readerDiv = document.getElementById('reader');
        const scanLine = document.createElement('div');
        scanLine.className = 'scan-line';
        scanLine.id = 'scan-line';
        readerDiv.appendChild(scanLine);

        console.log('Scanner started successfully');
    }).catch(err => {
        console.error("Scanner start failed:", err);
        showError('Scanner Error', 'Failed to start scanner: ' + err.message);
    });
}

function stopScanner() {
    if (!isScanning) return;

    html5QrCode.stop().then(() => {
        isScanning = false;
        document.getElementById('start-scanner').disabled = false;
        document.getElementById('stop-scanner').disabled = true;
        document.querySelector('.scan-guide').style.display = 'block';

        // Remove scan line
        const scanLine = document.getElementById('scan-line');
        if (scanLine) scanLine.remove();

        console.log('Scanner stopped');
    }).catch(err => {
        console.error("Scanner stop failed:", err);
    });
}

function switchCamera() {
    Html5Qrcode.getCameras().then(devices => {
        if (devices.length < 2) {
            alert('Only one camera available');
            return;
        }

        const currentIndex = devices.findIndex(d => d.id === currentCameraId);
        const nextIndex = (currentIndex + 1) % devices.length;

        console.log(`Switching camera from ${devices[currentIndex]?.label} to ${devices[nextIndex]?.label}`);

        stopScanner();
        currentCameraId = devices[nextIndex].id;
        setTimeout(startScanner, 500);
    });
}

function toggleTorch() {
    if (!html5QrCode) return;

    try {
        currentTorchState = !currentTorchState;
        html5QrCode.applyVideoConstraints({
            advanced: [{torch: currentTorchState}]
        });

        const torchBtn = document.querySelector('[onclick="toggleTorch()"]');
        torchBtn.innerHTML = currentTorchState ?
            '<i class="fas fa-lightbulb me-2"></i>Torch On' :
            '<i class="fas fa-lightbulb me-2"></i>Torch';
        torchBtn.className = currentTorchState ?
            'btn btn-warning active' : 'btn btn-warning';

    } catch (err) {
        console.error('Torch toggle error:', err);
    }
}

function onScanSuccess(decodedText) {
    // Debounce: ignore if last scan was less than 2 seconds ago
    if (window.lastScan && Date.now() - window.lastScan < 2000) {
        console.log('Scan debounced');
        return;
    }
    window.lastScan = Date.now();

    console.log('Scanned:', decodedText.substring(0, 50) + '...');

    // Pause scanner temporarily
    html5QrCode.pause();

    // Verify the barcode
    verifyBarcode(decodedText);
}

function onScanError(error) {
    // Mostly ignore, but log for debugging
    if (!error.includes('NotFoundException')) {
        console.error('Scan error:', error);
    }
}

async function verifyBarcode(barcodeData) {
    showLoading('Verifying barcode...');

    try {
        const response = await fetch('{{ route("barcode.verify.checkin") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                barcode_data: barcodeData,
                scan_time: new Date().toISOString()
            })
        });

        const result = await response.json();

        if (result.success) {
            handleVerificationSuccess(result.data, barcodeData);
        } else {
            handleVerificationError(result.message, barcodeData, result.code);
        }

    } catch (error) {
        console.error('Verification error:', error);
        handleVerificationError('Network error. Please try again.', barcodeData, 'NETWORK_ERROR');
    }
}

function handleVerificationSuccess(data, barcodeData) {
    // Update stats
    scanStats.today++;
    scanStats.verified++;

    // Add to recent scans
    addToRecentScans({
        id: 'scan_' + Date.now(),
        employee_id: data.employee.id,
        employee_name: data.employee.name,
        time: new Date().toLocaleTimeString(),
        status: 'success',
        message: 'Verified successfully',
        checkin_time: data.attendance.checkIn,
        verified_by: data.verification.scanned_by
    });

    // Update display
    updateVerificationResult(data);
    updateStatsDisplay();

    // Play success sound if available
    playSuccessSound();

    // Show success modal
    showSuccessModal(data);

    // Resume scanning after 3 seconds
    setTimeout(() => {
        if (isScanning) {
            html5QrCode.resume();
        }
    }, 3000);
}

function handleVerificationError(message, barcodeData, errorCode) {
    // Update stats
    scanStats.today++;
    scanStats.failed++;

    // Add to recent scans
    addToRecentScans({
        id: 'scan_' + Date.now(),
        employee_id: barcodeData.split(':')[0] || 'unknown',
        employee_name: 'Invalid Barcode',
        time: new Date().toLocaleTimeString(),
        status: 'error',
        message: message,
        error_code: errorCode
    });

    // Update display
    showErrorResult(message, errorCode);
    updateStatsDisplay();

    // Show error modal
    showErrorModal(message, errorCode);

    // Resume scanning after 2 seconds
    setTimeout(() => {
        if (isScanning) {
            html5QrCode.resume();
        }
    }, 2000);
}

function verifyManualBarcode() {
    const barcodeInput = document.getElementById('manual-barcode');
    const barcode = barcodeInput.value.trim();

    if (!barcode) {
        alert('Please enter a barcode');
        return;
    }

    if (barcode.length < 10) {
        alert('Invalid barcode format');
        return;
    }

    verifyBarcode(barcode);
    barcodeInput.value = '';
}

function addToRecentScans(scan) {
    recentScans.unshift(scan);

    // Keep only last 20 scans
    if (recentScans.length > 20) {
        recentScans.pop();
    }

    updateRecentScansDisplay();
}

function updateRecentScansDisplay() {
    const container = document.getElementById('recent-scans-list');
    const countElement = document.getElementById('recent-count');

    countElement.textContent = recentScans.length;

    if (recentScans.length === 0) {
        container.innerHTML = `
            <div class="text-center py-4">
                <i class="fas fa-clock fa-2x text-muted mb-3"></i>
                <p class="text-muted">No scans yet</p>
            </div>
        `;
        return;
    }

    let html = '';
    recentScans.forEach(scan => {
        const statusClass = scan.status === 'success' ? 'success' : 'error';
        const statusIcon = scan.status === 'success' ?
            '<i class="fas fa-check-circle text-success me-2"></i>' :
            '<i class="fas fa-times-circle text-danger me-2"></i>';

        html += `
            <div class="verification-item ${statusClass}">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-bold">${scan.employee_name}</div>
                        <small class="text-muted">${scan.employee_id} • ${scan.time}</small>
                        ${scan.message ? `<div class="small mt-1">${scan.message}</div>` : ''}
                    </div>
                    <div>
                        ${statusIcon}
                    </div>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
}

function updateStatsDisplay() {
    document.getElementById('today-scans').textContent = scanStats.today;
    document.getElementById('verified-count').textContent = scanStats.verified;
    document.getElementById('failed-count').textContent = scanStats.failed;

    const successRate = scanStats.today > 0 ?
        Math.round((scanStats.verified / scanStats.today) * 100) : 0;
    document.getElementById('success-rate').textContent = successRate + '%';
}

function updateVerificationResult(data) {
    const container = document.getElementById('verification-result');

    container.innerHTML = `
        <div class="alert alert-success">
            <div class="d-flex align-items-center">
                <i class="fas fa-check-circle fa-2x me-3"></i>
                <div>
                    <h5 class="mb-1">Verified Successfully!</h5>
                    <p class="mb-0">${data.employee.name} has been verified.</p>
                </div>
            </div>

            <hr>

            <div class="row mt-3">
                <div class="col-6">
                    <small class="text-muted">Employee ID</small>
                    <div class="fw-bold">${data.employee.id}</div>
                </div>
                <div class="col-6">
                    <small class="text-muted">Department</small>
                    <div class="fw-bold">${data.employee.department}</div>
                </div>
            </div>

            <div class="row mt-2">
                <div class="col-6">
                    <small class="text-muted">Check-in Time</small>
                    <div class="fw-bold">${data.attendance.checkIn}</div>
                </div>
                <div class="col-6">
                    <small class="text-muted">Verified At</small>
                    <div class="fw-bold">${data.verification.time}</div>
                </div>
            </div>

            <div class="mt-3">
                <small class="text-muted">Location</small>
                <div class="fw-bold">${data.attendance.location || 'Office'}</div>
            </div>
        </div>
    `;
}

function showErrorResult(message, errorCode) {
    const container = document.getElementById('verification-result');

    container.innerHTML = `
        <div class="alert alert-danger">
            <div class="d-flex align-items-center">
                <i class="fas fa-times-circle fa-2x me-3"></i>
                <div>
                    <h5 class="mb-1">Verification Failed</h5>
                    <p class="mb-0">${message}</p>
                    ${errorCode ? `<small class="text-muted">Error Code: ${errorCode}</small>` : ''}
                </div>
            </div>
        </div>
    `;
}

function showLoading(message) {
    const container = document.getElementById('verification-result');

    container.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary mb-3" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="text-muted">${message}</p>
        </div>
    `;
}

function showSuccessModal(data) {
    document.getElementById('employee-name').textContent = data.employee.name;
    document.getElementById('employee-details').textContent =
        `${data.employee.position} • ${data.employee.department}`;
    document.getElementById('checkin-time').textContent = data.attendance.checkIn;
    document.getElementById('verified-time').textContent = data.verification.time;

    const modal = new bootstrap.Modal(document.getElementById('successModal'));
    modal.show();
}

function showErrorModal(message, errorCode) {
    document.getElementById('error-title').textContent =
        errorCode === 'BARCODE_EXPIRED' ? 'Barcode Expired' : 'Verification Failed';
    document.getElementById('error-message').textContent = message;

    const modal = new bootstrap.Modal(document.getElementById('errorModal'));
    modal.show();
}

function continueScanning() {
    if (isScanning) {
        html5QrCode.resume();
    }
}

function playSuccessSound() {
    try {
        const audio = document.getElementById('success-sound');
        if (audio) {
            audio.currentTime = 0;
            audio.play().catch(e => console.log('Audio play failed:', e));
        }
    } catch (e) {
        console.log('Sound error:', e);
    }
}

function checkCameraPermission() {
    navigator.mediaDevices.getUserMedia({ video: true })
        .then(stream => {
            console.log('Camera permission granted');
            stream.getTracks().forEach(track => track.stop());
        })
        .catch(err => {
            console.warn('Camera permission denied or error:', err);
            showError('Camera Access Required',
                'Please allow camera access to use the scanner. ' +
                'Check your browser settings and refresh the page.');
        });
}

async function loadRecentScans() {
    try {
        const response = await fetch('{{ route("barcode.verification.history") }}');
        const result = await response.json();

        if (result.success && result.data) {
            // Update recent scans with server data
            recentScans = result.data.map(item => ({
                id: item.id,
                employee_id: item.employee_id,
                employee_name: item.employee_name,
                time: new Date(item.verification_time).toLocaleTimeString(),
                status: item.status,
                message: 'Verified by ' + item.verified_by
            }));

            updateRecentScansDisplay();
        }
    } catch (error) {
        console.error('Load recent scans error:', error);
    }
}

// Handle visibility change
document.addEventListener('visibilitychange', function() {
    if (document.hidden && isScanning) {
        // Page is hidden, stop scanner to save resources
        stopScanner();
    }
});

// Handle before unload
window.addEventListener('beforeunload', function() {
    if (isScanning) {
        stopScanner();
    }
});

// Keyboard shortcuts
document.addEventListener('keydown', function(event) {
    // Space to toggle scanner
    if (event.code === 'Space') {
        event.preventDefault();
        if (isScanning) {
            stopScanner();
        } else {
            startScanner();
        }
    }

    // Escape to stop scanner
    if (event.code === 'Escape' && isScanning) {
        stopScanner();
    }

    // Enter on manual input
    if (event.code === 'Enter' && document.activeElement.id === 'manual-barcode') {
        verifyManualBarcode();
    }
});
</script>
@endpush
