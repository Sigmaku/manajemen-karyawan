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
                        <div id="reader" class="border rounded" style="width: 100%; min-height: 400px; position: relative;"></div>
                        <div class="scanner-overlay text-center mt-2">
                            <div class="scan-guide">
                                <i class="fas fa-qrcode fa-3x text-primary mb-3"></i>
                                <p class="text-muted">Position the QR/Barcode within the frame</p>
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
                    <div class="row mt-2">
                        <div class="col-12 text-center">
                            <small class="text-muted">Status:</small>
                            <div class="fw-bold" id="verified-status">--</div>
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
    .scanner-container { position: relative; overflow: hidden; }
    #reader { background: #000; }
    .scanner-overlay { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 100; }
    .scan-guide { background: rgba(0, 0, 0, 0.7); color: white; padding: 20px; border-radius: 10px; }
    .scan-line { position: absolute; width: 100%; height: 3px; background: #00ff00; box-shadow: 0 0 10px #00ff00; animation: scan 2s linear infinite; top: 50%; }
    @keyframes scan { 0% { top: 0%; } 100% { top: 100%; } }
    .stat-box { transition: transform 0.3s; }
    .stat-box:hover { transform: translateY(-5px); }
    .verification-item { padding: 10px 15px; border-bottom: 1px solid #eee; transition: background 0.2s; }
    .verification-item:hover { background: #f8f9fa; }
    .verification-item.success { border-left: 4px solid #28a745; }
    .verification-item.error { border-left: 4px solid #dc3545; }
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
<script>
let html5QrCode = null;
let currentCameraId = null;
let isScanning = false;
let currentTorchState = false;

let recentScans = [];
let scanStats = { today: 0, verified: 0, failed: 0 };

document.addEventListener('DOMContentLoaded', function() {
    initScanner();
    updateStatsDisplay();
    checkCameraPermission();
});

function initScanner() {
    html5QrCode = new Html5Qrcode("reader");
    Html5Qrcode.getCameras().then(devices => {
        if (devices && devices.length) currentCameraId = devices[0].id;
        else showErrorModal('No Camera', 'No cameras found. Please connect a camera.');
    }).catch(err => {
        console.error(err);
        showErrorModal('Camera Error', 'Unable to access camera. Please check permissions.');
    });
}

function startScanner() {
    if (!currentCameraId) return alert('No camera available.');

    const config = { fps: 10, qrbox: { width: 250, height: 250 } };

    html5QrCode.start(currentCameraId, config, onScanSuccess, onScanError)
        .then(() => {
            isScanning = true;
            document.getElementById('start-scanner').disabled = true;
            document.getElementById('stop-scanner').disabled = false;
            document.querySelector('.scan-guide').style.display = 'none';

            const scanLine = document.createElement('div');
            scanLine.className = 'scan-line';
            scanLine.id = 'scan-line';
            document.getElementById('reader').appendChild(scanLine);
        })
        .catch(err => {
            console.error(err);
            showErrorModal('Scanner Error', 'Failed to start scanner: ' + err);
        });
}

function stopScanner() {
    if (!isScanning) return;
    html5QrCode.stop().then(() => {
        isScanning = false;
        document.getElementById('start-scanner').disabled = false;
        document.getElementById('stop-scanner').disabled = true;
        document.querySelector('.scan-guide').style.display = 'block';
        document.getElementById('scan-line')?.remove();
    });
}

function switchCamera() {
    Html5Qrcode.getCameras().then(devices => {
        if (devices.length < 2) return alert('Only one camera available');
        const idx = devices.findIndex(d => d.id === currentCameraId);
        const next = (idx + 1) % devices.length;
        stopScanner();
        currentCameraId = devices[next].id;
        setTimeout(startScanner, 500);
    });
}

function toggleTorch() {
    if (!html5QrCode) return;
    try {
        currentTorchState = !currentTorchState;
        html5QrCode.applyVideoConstraints({ advanced: [{ torch: currentTorchState }] });
    } catch (e) {
        console.warn('Torch not supported:', e);
    }
}

function onScanSuccess(decodedText) {
    if (window.lastScan && Date.now() - window.lastScan < 2000) return;
    window.lastScan = Date.now();
    html5QrCode.pause();
    verifyBarcode(decodedText);
}

function onScanError(error) { /* ignore */ }

function verifyManualBarcode() {
    const v = document.getElementById('manual-barcode').value.trim();
    if (!v) return alert('Please enter a barcode');
    verifyBarcode(v);
    document.getElementById('manual-barcode').value = '';
}

async function verifyBarcode(barcodeData) {
    showLoading('Verifying barcode...');


    try {
        console.log('Scanned raw:', barcodeData);
console.log('Parts:', String(barcodeData).split(':').length);

        const response = await fetch('{{ route("barcode.verify.checkin") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ barcode_data: barcodeData })
        });

        const result = await response.json();
        console.log('HTTP status:', response.status);
        console.log('Result:', result);


        if (result.success) {
            handleSuccess(result.data);
        } else {
            handleError(result.message || 'Verification failed', result.code || '');
        }
    } catch (e) {
        console.error(e);
        handleError('Network error. Please try again.', 'NETWORK_ERROR');
    }
}

function handleSuccess(data) {
    scanStats.today++;
    scanStats.verified++;

    // recent list
    addToRecentScans({
        employee_id: data.employee_id,
        employee_name: data.employee_name,
        status: 'success',
        time: new Date().toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'}),
        message: `Verified (${(data.status || '').toUpperCase()})`
    });

    updateVerificationResult(data);
    updateStatsDisplay();
    playSuccessSound();
    showSuccessModal(data);

    setTimeout(() => { if (isScanning) html5QrCode.resume(); }, 2000);
}

function handleError(message, code) {
    scanStats.today++;
    scanStats.failed++;

    addToRecentScans({
        employee_id: '-',
        employee_name: 'Invalid Barcode',
        status: 'error',
        time: new Date().toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'}),
        message: code ? `${message} (${code})` : message
    });

    showErrorResult(message, code);
    updateStatsDisplay();
    showErrorModal(code === 'BARCODE_EXPIRED' ? 'Barcode Expired' : 'Verification Failed', message);

    setTimeout(() => { if (isScanning) html5QrCode.resume(); }, 1500);
}

function addToRecentScans(scan) {
    recentScans.unshift(scan);
    if (recentScans.length > 20) recentScans.pop();
    updateRecentScansDisplay();
}

function updateRecentScansDisplay() {
    const container = document.getElementById('recent-scans-list');
    document.getElementById('recent-count').textContent = recentScans.length;

    if (recentScans.length === 0) {
        container.innerHTML = `<div class="text-center py-4"><p class="text-muted">No scans yet</p></div>`;
        return;
    }

    container.innerHTML = recentScans.map(scan => {
        const ok = scan.status === 'success';
        return `
            <div class="verification-item ${ok ? 'success' : 'error'}">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-bold">${scan.employee_name}</div>
                        <small class="text-muted">${scan.employee_id} • ${scan.time}</small>
                        <div class="small mt-1">${scan.message}</div>
                    </div>
                    <div>
                        ${ok ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-times-circle text-danger"></i>'}
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

function updateStatsDisplay() {
    document.getElementById('today-scans').textContent = scanStats.today;
    document.getElementById('verified-count').textContent = scanStats.verified;
    document.getElementById('failed-count').textContent = scanStats.failed;

    const rate = scanStats.today > 0 ? Math.round((scanStats.verified / scanStats.today) * 100) : 0;
    document.getElementById('success-rate').textContent = rate + '%';
}

function updateVerificationResult(data) {
    document.getElementById('verification-result').innerHTML = `
        <div class="alert alert-success text-start">
            <h5 class="mb-2"><i class="fas fa-check-circle me-2"></i>Verified</h5>
            <div><strong>${data.employee_name}</strong> (${data.employee_id})</div>
            <div class="small text-muted">Dept: ${data.department || '-'}</div>
            <hr>
            <div class="small">Check-in: <strong>${data.check_in_time}</strong></div>
            <div class="small">Verified at: <strong>${data.verification_time}</strong></div>
            <div class="small">Location: <strong>${data.location || 'Office'}</strong></div>
            <div class="small">Status: <strong>${(data.status || '').toUpperCase()}</strong></div>
        </div>
    `;
}

function showErrorResult(message, code) {
    document.getElementById('verification-result').innerHTML = `
        <div class="alert alert-danger text-start">
            <h5 class="mb-2"><i class="fas fa-times-circle me-2"></i>Failed</h5>
            <div>${message}</div>
            ${code ? `<div class="small text-muted">Code: ${code}</div>` : ''}
        </div>
    `;
}

function showLoading(msg) {
    document.getElementById('verification-result').innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary mb-3" role="status"></div>
            <p class="text-muted">${msg}</p>
        </div>
    `;
}

function showSuccessModal(data) {
    document.getElementById('employee-name').textContent = data.employee_name;
    document.getElementById('employee-details').textContent = `${data.position || '-'} • ${data.department || '-'}`;
    document.getElementById('checkin-time').textContent = data.check_in_time;
    document.getElementById('verified-time').textContent = (data.verification_time || '').split(' ')[1] || data.verification_time;
    document.getElementById('verified-status').textContent = (data.status || '').toUpperCase();

    new bootstrap.Modal(document.getElementById('successModal')).show();
}

function continueScanning() {
    if (isScanning) html5QrCode.resume();
}

function playSuccessSound() {
    const audio = document.getElementById('success-sound');
    if (!audio) return;
    audio.currentTime = 0;
    audio.play().catch(() => {});
}

function checkCameraPermission() {
    navigator.mediaDevices.getUserMedia({ video: true })
        .then(stream => stream.getTracks().forEach(t => t.stop()))
        .catch(() => {});
}

document.addEventListener('keydown', function(e) {
    if (e.code === 'Space') { e.preventDefault(); isScanning ? stopScanner() : startScanner(); }
    if (e.code === 'Escape' && isScanning) stopScanner();
    if (e.code === 'Enter' && document.activeElement.id === 'manual-barcode') verifyManualBarcode();
});

window.addEventListener('beforeunload', function() {
    if (isScanning) stopScanner();
});
function showErrorModal(title, message) {
    const t = document.getElementById('error-title');
    const m = document.getElementById('error-message');
    if (t) t.textContent = title || 'Verification Failed';
    if (m) m.textContent = message || 'Unknown error';

    const modalEl = document.getElementById('errorModal');
    if (modalEl) new bootstrap.Modal(modalEl).show();
}

function showSuccessModal(data) {
    document.getElementById('employee-name').textContent = data.employee_name || '-';
    document.getElementById('employee-details').textContent =
        `${data.position || '-'} • ${data.department || '-'}`;

    document.getElementById('checkin-time').textContent = data.check_in_time || '--:--';
    document.getElementById('verified-time').textContent =
        (data.verification_time || '').split(' ')[1] || (data.verification_time || '--:--');
    const st = document.getElementById('verified-status');
    if (st) st.textContent = (data.status || '').toUpperCase();

    const modalEl = document.getElementById('successModal');
    if (modalEl) new bootstrap.Modal(modalEl).show();
}

</script>
@endpush
