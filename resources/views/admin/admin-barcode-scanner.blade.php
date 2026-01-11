@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <!-- Scanner Card -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-qrcode me-2"></i>Barcode Verification Scanner
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Scanner Area -->
                    <div id="scanner-container" class="position-relative">
                        <div id="reader" style="width: 100%; min-height: 400px;"></div>
                        <div class="scanner-overlay">
                            <div class="scan-line"></div>
                        </div>
                    </div>

                    <!-- Controls -->
                    <div class="text-center mt-3">
                        <div class="btn-group" role="group">
                            <button id="start-btn" class="btn btn-success" onclick="startScanner()">
                                <i class="fas fa-play me-2"></i>Start Scanner
                            </button>
                            <button id="stop-btn" class="btn btn-danger" onclick="stopScanner()" disabled>
                                <i class="fas fa-stop me-2"></i>Stop Scanner
                            </button>
                            <button class="btn btn-info" onclick="switchCamera()">
                                <i class="fas fa-sync-alt me-2"></i>Switch Camera
                            </button>
                        </div>
                    </div>

                    <!-- Manual Entry -->
                    <div class="mt-4">
                        <h6>Or Enter Barcode Manually:</h6>
                        <div class="input-group">
                            <input type="text" id="manual-input" class="form-control"
                                   placeholder="Paste barcode here..." maxlength="200">
                            <button class="btn btn-primary" onclick="manualVerify()">
                                <i class="fas fa-check me-2"></i>Verify
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Verification Result -->
            <div class="card" id="result-card" style="display: none;">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-user-check me-2"></i>Verification Result
                    </h6>
                </div>
                <div class="card-body">
                    <div id="verification-result"></div>
                </div>
            </div>

            <!-- Recent Verifications -->
            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-history me-2"></i>Recent Scans
                    </h6>
                </div>
                <div class="card-body">
                    <div id="recent-scans">
                        <div class="text-center py-3">
                            <i class="fas fa-qrcode fa-2x text-muted mb-3"></i>
                            <p class="text-muted">Scan results will appear here</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Verification Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-success">
                    <i class="fas fa-check-circle me-2"></i>Verified!
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4">
                <i class="fas fa-check-circle fa-4x text-success mb-4"></i>
                <h5 id="success-name"></h5>
                <p class="mb-1">Check-in verified successfully</p>
                <div class="alert alert-success mt-3">
                    <div class="row small">
                        <div class="col-6 text-start">Check-in Time:</div>
                        <div class="col-6 text-end"><strong id="success-checkin"></strong></div>

                        <div class="col-6 text-start">Verified At:</div>
                        <div class="col-6 text-end"><strong id="success-verified"></strong></div>

                        <div class="col-6 text-start">Location:</div>
                        <div class="col-6 text-end"><strong id="success-location"></strong></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 justify-content-center">
                <button type="button" class="btn btn-success" data-bs-dismiss="modal" onclick="continueScanning()">
                    <i class="fas fa-qrcode me-2"></i>Scan Next
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
<script>
let html5QrCode = null;
let currentCameraId = null;
let isScanning = false;

document.addEventListener('DOMContentLoaded', function() {
    initScanner();
});

function initScanner() {
    html5QrCode = new Html5Qrcode("reader");

    // Get cameras
    Html5Qrcode.getCameras().then(devices => {
        if (devices && devices.length) {
            currentCameraId = devices[0].id;
        }
    });
}

function startScanner() {
    if (!currentCameraId) {
        alert('No camera found');
        return;
    }

    const config = {
        fps: 10,
        qrbox: { width: 250, height: 250 }
    };

    html5QrCode.start(
        currentCameraId,
        config,
        onScanSuccess,
        onScanError
    ).then(() => {
        isScanning = true;
        document.getElementById('start-btn').disabled = true;
        document.getElementById('stop-btn').disabled = false;
        document.querySelector('.scanner-overlay').style.display = 'block';
    });
}

function stopScanner() {
    html5QrCode.stop().then(() => {
        isScanning = false;
        document.getElementById('start-btn').disabled = false;
        document.getElementById('stop-btn').disabled = true;
        document.querySelector('.scanner-overlay').style.display = 'none';
    });
}

function onScanSuccess(decodedText) {
    // Debounce
    if (window.lastScan && Date.now() - window.lastScan < 2000) return;
    window.lastScan = Date.now();

    verifyBarcode(decodedText);
    html5QrCode.pause(true);
}

function onScanError(error) {
    // Ignore not found errors
}

function switchCamera() {
    Html5Qrcode.getCameras().then(devices => {
        if (devices.length < 2) {
            alert('Only one camera available');
            return;
        }

        const currentIndex = devices.findIndex(d => d.id === currentCameraId);
        const nextIndex = (currentIndex + 1) % devices.length;

        stopScanner();
        currentCameraId = devices[nextIndex].id;
        setTimeout(startScanner, 500);
    });
}

async function verifyBarcode(barcode) {
    try {
        const response = await fetch('{{ route("barcode.verify.checkin") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ barcode_data: barcode })
        });

        const result = await response.json();

        if (result.success) {
            showVerificationSuccess(result.data);
            addToRecentScans(result.data, true);
        } else {
            showVerificationError(result.message);
            addToRecentScans({employee_name: 'Invalid', error: result.message}, false);
        }

    } catch (error) {
        console.error('Verify error:', error);
        showVerificationError('Network error');
    }
}

function manualVerify() {
    const barcode = document.getElementById('manual-input').value.trim();
    if (barcode) {
        verifyBarcode(barcode);
        document.getElementById('manual-input').value = '';
    }
}

function showVerificationSuccess(data) {
    document.getElementById('success-name').textContent = data.employee_name;
    document.getElementById('success-checkin').textContent = data.check_in_time;
    document.getElementById('success-verified').textContent = data.verification_time;
    document.getElementById('success-location').textContent = data.location;

    const modal = new bootstrap.Modal(document.getElementById('successModal'));
    modal.show();

    // Update result card
    document.getElementById('result-card').style.display = 'block';
    document.getElementById('verification-result').innerHTML = `
        <div class="alert alert-success">
            <h6><i class="fas fa-check-circle me-2"></i>Verified Successfully</h6>
            <p class="mb-1"><strong>${data.employee_name}</strong></p>
            <p class="mb-1 small">Check-in: ${data.check_in_time}</p>
            <p class="mb-0 small">Location: ${data.location}</p>
        </div>
    `;
}

function showVerificationError(message) {
    document.getElementById('result-card').style.display = 'block';
    document.getElementById('verification-result').innerHTML = `
        <div class="alert alert-danger">
            <h6><i class="fas fa-times-circle me-2"></i>Verification Failed</h6>
            <p class="mb-0">${message}</p>
        </div>
    `;
}

function continueScanning() {
    document.getElementById('result-card').style.display = 'none';
    if (isScanning) {
        html5QrCode.resume();
    }
}

function addToRecentScans(data, success) {
    const recentScans = document.getElementById('recent-scans');
    const scanItem = document.createElement('div');
    scanItem.className = `scan-item mb-2 p-2 border rounded ${success ? 'border-success' : 'border-danger'}`;

    const time = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});

    scanItem.innerHTML = `
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <strong>${data.employee_name || 'Invalid'}</strong>
                <div class="small text-muted">${time}</div>
            </div>
            <div>
                ${success ?
                    '<i class="fas fa-check-circle text-success"></i>' :
                    '<i class="fas fa-times-circle text-danger"></i>'
                }
            </div>
        </div>
        ${data.error ? `<div class="small text-danger mt-1">${data.error}</div>` : ''}
    `;

    recentScans.insertBefore(scanItem, recentScans.firstChild);

    // Limit to 5 items
    const items = recentScans.querySelectorAll('.scan-item');
    if (items.length > 5) {
        items[items.length - 1].remove();
    }
}
</script>
@endpush
