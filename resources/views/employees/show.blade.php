@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Employee Details</h4>
                    <div>
                        <a href="{{ route('employees.edit', $employee['id']) }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit me-1"></i> Edit
                        </a>
                        <a href="{{ route('employees.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i> Back
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 text-center mb-4">
                            <div class="avatar bg-primary rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 100px; height: 100px;">
                                <i class="fas fa-user text-white fa-3x"></i>
                            </div>
                            <h5 class="mt-3">{{ $employee['name'] }}</h5>
                            <span class="badge bg-{{ $employee['status'] == 'active' ? 'success' : 'danger' }}">
                                {{ ucfirst($employee['status']) }}
                            </span>

                            @if($hasAccount)
                            <div class="mt-2">
                                <span class="badge bg-success">
                                    <i class="fas fa-check-circle me-1"></i> Has Login Account
                                </span>
                            </div>
                            @else
                            <div class="mt-2">
                                <span class="badge bg-warning">
                                    <i class="fas fa-exclamation-circle me-1"></i> No Login Account
                                </span>
                            </div>
                            @endif
                        </div>

                        <div class="col-md-9">
                            <div class="row">
                                <div class="col-md-6">
    <strong class="text-muted">Sisa Cuti </strong>
    <p class="mb-0 fs-5">
        {{ $remainingLeave ?? 0 }} hari
    </p>
</div>

                                <div class="col-md-6 mb-3">
                                    <label class="text-muted">Email</label>
                                    <p>{{ $employee['email'] }}</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted">Phone</label>
                                    <p>{{ $employee['phone'] ?? '-' }}</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted">Department</label>
                                    <p>{{ $employee['department'] }}</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted">Position</label>
                                    <p>{{ $employee['position'] }}</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted">Join Date</label>
                                    <p>{{ \Carbon\Carbon::parse($employee['joinDate'] ?? $employee['createdAt'])->format('d M Y') }}</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted">Employee ID</label>
                                    <p><code>{{ $employee['id'] }}</code></p>
                                </div>
                                @if(isset($employee['salary']))
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted">Salary</label>
                                    <p>Rp {{ number_format($employee['salary'], 0, ',', '.') }}</p>
                                </div>
                                @endif
                                @if(isset($employee['address']) && !empty($employee['address']))
                                <div class="col-md-12 mb-3">
                                    <label class="text-muted">Address</label>
                                    <p>{{ $employee['address'] }}</p>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Account Actions -->
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="card border-0 bg-light">
                                <div class="card-body p-3">
                                    <h6 class="mb-3">Account Management</h6>
                                    <div class="d-flex gap-2 flex-wrap">
                                        @if($hasAccount)
                                        <form action="{{ route('employees.reset-password', $employee['id']) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-info btn-sm" onclick="return confirm('Reset password for {{ $employee['name'] }}?')">
                                                <i class="fas fa-key me-1"></i> Reset Password
                                            </button>
                                        </form>

                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#setPasswordModal">
                                            <i class="fas fa-key me-1"></i> Set Password
                                        </button>

                                        @else
                                        <form action="{{ route('employees.create-account', $employee['id']) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Create login account for {{ $employee['name'] }}?')">
                                                <i class="fas fa-user-plus me-1"></i> Create Login Account
                                            </button>
                                        </form>
                                        @endif

                                        <button class="btn btn-primary btn-sm" onclick="sendCredentials()">
                                            <i class="fas fa-paper-plane me-1"></i> Send Credentials
                                        </button>

                                        <button class="btn btn-warning btn-sm" onclick="showAccessHistory()">
                                            <i class="fas fa-history me-1"></i> Access History
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Attendance Stats -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Attendance This Month</h5>
                </div>
                <div class="card-body text-center">
                    <div class="row">
                        <div class="col-6 mb-3">
                            <h6>Work Days</h6>
                            <h3>{{ $totalDays }}</h3>
                        </div>
                        <div class="col-6 mb-3">
                            <h6>Present Days</h6>
                            <h3>{{ $presentDays }}</h3>
                        </div>
                        <div class="col-6 mb-3">
                            <h6>Attendance Rate</h6>
                            <h3>{{ $attendanceRate }}%</h3>
                        </div>
                        <div class="col-6 mb-3">
                            <h6>Total Hours</h6>
                            <h3>{{ $totalHours }}h</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('attendance.report') }}?employee_id={{ $employee['id'] }}" class="btn btn-info">
                            <i class="fas fa-chart-bar me-2"></i>View Attendance Report
                        </a>
                        <button class="btn btn-warning" onclick="showAttendanceModal()">
                            <i class="fas fa-clock me-2"></i>Manual Attendance Entry
                        </button>
                        <a href="mailto:{{ $employee['email'] }}" class="btn btn-primary">
                            <i class="fas fa-envelope me-2"></i>Send Email
                        </a>
                        @if(isset($employee['phone']))
                        <a href="tel:{{ $employee['phone'] }}" class="btn btn-success">
                            <i class="fas fa-phone me-2"></i>Call Employee
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Attendance -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Recent Attendance ({{ date('F Y') }})</h5>
                </div>
                <div class="card-body">
                    @if(count($attendance) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Day</th>
                                    <th>Check In</th>
                                    <th>Check Out</th>
                                    <th>Hours</th>
                                    <th>Location</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($attendance as $date => $record)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($date)->format('M d, Y') }}</td>
                                    <td>{{ \Carbon\Carbon::parse($date)->format('D') }}</td>
                                    <td>{{ $record['checkIn'] ?? '-' }}</td>
                                    <td>{{ $record['checkOut'] ?? '-' }}</td>
                                    <td>{{ $record['hoursWorked'] ?? 0 }}h</td>
                                    <td>{{ $record['location'] ?? 'Office' }}</td>
                                    <td>
                                        @if(isset($record['checkIn']))
                                            <span class="badge bg-success">Present</span>
                                        @else
                                            <span class="badge bg-danger">Absent</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-4">
                        <p class="text-muted">No attendance records for this month</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Set Password Modal -->
<div class="modal fade" id="setPasswordModal" tabindex="-1" aria-labelledby="setPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="setPasswordModalLabel">Set Password for {{ $employee['name'] }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="setPasswordForm">
                    @csrf
                    <input type="hidden" name="employee_id" value="{{ $employee['id'] }}">

                    <div class="mb-3">
                        <label class="form-label">Password Option</label>
                        <div class="mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="password_option" id="setRandomPassword" value="random" checked>
                                <label class="form-check-label" for="setRandomPassword">
                                    Generate Random Password
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="password_option" id="setCustomPassword" value="custom">
                                <label class="form-check-label" for="setCustomPassword">
                                    Set Custom Password
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3" id="setCustomPasswordSection" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">New Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" name="new_password" id="newPasswordInput" placeholder="Enter new password">
                                    <button class="btn btn-outline-secondary" type="button" id="toggleNewPassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" name="new_password_confirmation" id="newPasswordConfirm" placeholder="Confirm password">
                            </div>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i> Minimum 8 characters with letters and numbers
                            </small>
                        </div>
                    </div>

                    <div class="alert alert-info" id="setRandomPasswordSection">
                        <i class="fas fa-key me-2"></i>
                        <strong>Random Password:</strong>
                        <span id="setRandomPasswordPreview" class="fw-bold"></span>
                        <button type="button" class="btn btn-sm btn-outline-primary ms-2" onclick="generateSetRandomPassword()">
                            <i class="fas fa-sync-alt me-1"></i> Regenerate
                        </button>
                        <input type="hidden" name="set_random_password" id="setRandomPasswordInput">
                    </div>

                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Employee will need to use this password to login. They can change it after login.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="updatePassword()">
                    <i class="fas fa-save me-2"></i> Update Password
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Manual Attendance Modal -->
<div class="modal fade" id="attendanceModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Manual Attendance Entry</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="manualAttendanceForm">
                    @csrf
                    <input type="hidden" name="employee_id" value="{{ $employee['id'] }}">
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" class="form-control" name="date" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Check In Time</label>
                            <input type="time" class="form-control" name="check_in" value="08:00" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Check Out Time</label>
                            <input type="time" class="form-control" name="check_out" value="17:00">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status" required>
                            <option value="present">Present</option>
                            <option value="absent">Absent</option>
                            <option value="late">Late</option>
                            <option value="leave">Leave</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitManualAttendance()">
                    <i class="fas fa-save me-2"></i>Save
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // For create form
    function generateRandomPassword() {
        const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&*';
        let password = '';
        for (let i = 0; i < 12; i++) {
            password += chars.charAt(Math.floor(Math.random() * chars.length));
        }

        // Ensure password requirements
        if (!/[A-Z]/.test(password)) password = password.slice(0, -1) + 'A';
        if (!/[a-z]/.test(password)) password = password.slice(0, -1) + 'a';
        if (!/[0-9]/.test(password)) password = password.slice(0, -1) + '1';
        if (!/[!@#$%&*]/.test(password)) password = password.slice(0, -1) + '!';

        document.getElementById('randomPasswordPreview').textContent = password;
        document.getElementById('randomPassword').value = password;
    }

    // For set password modal
    function generateSetRandomPassword() {
        const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&*';
        let password = '';
        for (let i = 0; i < 12; i++) {
            password += chars.charAt(Math.floor(Math.random() * chars.length));
        }

        // Ensure password requirements
        if (!/[A-Z]/.test(password)) password = password.slice(0, -1) + 'A';
        if (!/[a-z]/.test(password)) password = password.slice(0, -1) + 'a';
        if (!/[0-9]/.test(password)) password = password.slice(0, -1) + '1';
        if (!/[!@#$%&*]/.test(password)) password = password.slice(0, -1) + '!';

        document.getElementById('setRandomPasswordPreview').textContent = password;
        document.getElementById('setRandomPasswordInput').value = password;
    }

    function updatePassword() {
        const employeeId = '{{ $employee["id"] }}';
        const passwordOption = document.querySelector('#setPasswordModal input[name="password_option"]:checked').value;
        let password = '';

        if (passwordOption === 'custom') {
            const newPassword = document.getElementById('newPasswordInput').value;
            const confirmPassword = document.getElementById('newPasswordConfirm').value;

            if (!newPassword || newPassword.length < 8) {
                alert('Password must be at least 8 characters long');
                return;
            }

            if (newPassword !== confirmPassword) {
                alert('Passwords do not match');
                return;
            }

            if (!/[a-z]/.test(newPassword) || !/[A-Z]/.test(newPassword) || !/[0-9]/.test(newPassword)) {
                alert('Password must contain at least one uppercase letter, one lowercase letter, and one number');
                return;
            }

            password = newPassword;
        } else {
            password = document.getElementById('setRandomPasswordInput').value;
            if (!password) {
                generateSetRandomPassword();
                password = document.getElementById('setRandomPasswordInput').value;
            }
        }

        // Send AJAX request to update password
        fetch('/employees/' + employeeId + '/update-password', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                password: password,
                password_type: passwordOption
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('setPasswordModal'));
                modal.hide();

                // Show success message
                showToast('success', data.message);

                // Show credentials if returned
                if (data.credentials) {
                    showCredentialsModal(data.credentials);
                }
            } else {
                showToast('error', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('error', 'Network error');
        });
    }

    // Toggle password option in set password modal
    document.querySelectorAll('#setPasswordModal input[name="password_option"]').forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'custom') {
                document.getElementById('setCustomPasswordSection').style.display = 'block';
                document.getElementById('setRandomPasswordSection').style.display = 'none';
            } else {
                document.getElementById('setCustomPasswordSection').style.display = 'none';
                document.getElementById('setRandomPasswordSection').style.display = 'block';
                generateSetRandomPassword();
            }
        });
    });

    // Initialize set password modal
    document.getElementById('setPasswordModal').addEventListener('show.bs.modal', function() {
        generateSetRandomPassword();
    });

    // Toggle new password visibility
    document.getElementById('toggleNewPassword').addEventListener('click', function() {
        const passwordInput = document.getElementById('newPasswordInput');
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
    });

    // Show credentials modal if exists
    @if(session('credentials'))
    $(document).ready(function() {
        const credentialsModal = new bootstrap.Modal(document.getElementById('credentialsModal'));
        credentialsModal.show();
    });
    @endif

    function copyToClipboard(elementId) {
        const element = document.getElementById(elementId);
        element.select();
        element.setSelectionRange(0, 99999);

        navigator.clipboard.writeText(element.value).then(() => {
            const toast = new bootstrap.Toast(document.getElementById('copyToast'));
            toast.show();
        });
    }

    function printCredentials() {
        const { email, password, employee_id } = @json(session('credentials') ?? []);

        const printContent = `
            <html>
            <head>
                <title>Employee Login Credentials</title>
                <style>
                    body { font-family: Arial, sans-serif; padding: 30px; max-width: 500px; margin: 0 auto; }
                    .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 15px; }
                    h2 { color: #2c3e50; margin: 0; }
                    .company { color: #7f8c8d; font-size: 14px; }
                    .credentials-container { margin: 20px 0; }
                    .credential-item { margin: 15px 0; padding: 12px; background: #f8f9fa; border-radius: 6px; border-left: 4px solid #3498db; }
                    .label { font-weight: bold; color: #7f8c8d; font-size: 12px; text-transform: uppercase; margin-bottom: 5px; }
                    .value { color: #2c3e50; font-size: 16px; font-family: monospace; }
                    .footer { margin-top: 30px; padding-top: 15px; border-top: 1px solid #ddd; font-size: 12px; color: #95a5a6; text-align: center; }
                    @media print {
                        body { padding: 0; }
                        .no-print { display: none; }
                    }
                </style>
            </head>
            <body>
                <div class="header">
                    <h2>EMPLOYEE LOGIN CREDENTIALS</h2>
                    <div class="company">{{ config('app.name', 'Employee Management System') }}</div>
                </div>

                <div class="alert no-print" style="background: #fff3cd; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
                    <i class="fas fa-exclamation-triangle"></i> Keep this document secure. Destroy after sharing.
                </div>

                <div class="credentials-container">
                    <div class="credential-item">
                        <div class="label">Email Address</div>
                        <div class="value">${email}</div>
                    </div>

                    <div class="credential-item">
                        <div class="label">Temporary Password</div>
                        <div class="value">${password}</div>
                    </div>

                    <div class="credential-item">
                        <div class="label">Employee ID</div>
                        <div class="value">${employee_id}</div>
                    </div>
                </div>

                <div class="instructions" style="margin: 25px 0; padding: 15px; background: #e8f4fc; border-radius: 6px;">
                    <strong>Login Instructions:</strong>
                    <ol style="margin: 10px 0 0 0; padding-left: 20px;">
                        <li>Go to: <strong>${window.location.origin}/login</strong></li>
                        <li>Enter your email and password</li>
                        <li>Change your password after first login (recommended)</li>
                        <li>Contact IT support if you encounter issues</li>
                    </ol>
                </div>

                <div class="footer">
                    Generated on: ${new Date().toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    })}
                </div>
            </body>
            </html>
        `;

        const printWindow = window.open('', '_blank');
        printWindow.document.write(printContent);
        printWindow.document.close();
        printWindow.focus();

        setTimeout(() => {
            printWindow.print();
            printWindow.close();
        }, 250);
    }

    function showAttendanceModal() {
        const attendanceModal = new bootstrap.Modal(document.getElementById('attendanceModal'));
        attendanceModal.show();
    }

    function submitManualAttendance() {
        const form = document.getElementById('manualAttendanceForm');
        const formData = new FormData(form);

        fetch('/attendance/manual', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                $('#attendanceModal').modal('hide');
                showToast('success', data.message);
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast('error', data.message || 'Failed to save attendance');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('error', 'Network error. Please try again.');
        });
    }

    function sendCredentials() {
        alert('This feature will send credentials via email. Implementation depends on your email setup.');
    }

    function showAccessHistory() {
        alert('Access history feature would show login/logout history of the employee.');
    }

    function showToast(type, message) {
        const toastHtml = `
            <div class="toast align-items-center text-bg-${type} border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle me-2"></i>
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;

        const container = document.querySelector('.toast-container');
        if (container) {
            container.insertAdjacentHTML('beforeend', toastHtml);
            const toast = container.lastElementChild;
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();

            setTimeout(() => {
                bsToast.hide();
                setTimeout(() => toast.remove(), 300);
            }, 5000);
        }
    }

    function showCredentialsModal(credentials) {
        // Create modal dynamically
        const modalHtml = `
            <div class="modal fade" id="dynamicCredentialsModal" tabindex="-1" aria-labelledby="dynamicCredentialsModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title" id="dynamicCredentialsModalLabel">
                                <i class="fas fa-key me-2"></i>New Password Set
                            </h5>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Please save these new credentials.
                            </div>

                            <div class="credentials-box p-3 bg-light rounded">
                                <div class="mb-3">
                                    <label class="form-label text-muted">Email</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="dynamicEmailField"
                                               value="${credentials.email}" readonly>
                                        <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('dynamicEmailField')">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label text-muted">New Password</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="dynamicPasswordField"
                                               value="${credentials.password}" readonly>
                                        <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('dynamicPasswordField')">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted">Share this new password with the employee</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label text-muted">Employee ID</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="dynamicEmployeeIdField"
                                               value="${credentials.employee_id}" readonly>
                                        <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('dynamicEmployeeIdField')">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-warning mt-3 mb-0">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Important:</strong> Employee must use this new password to login.
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary" onclick="printDynamicCredentials()">
                                <i class="fas fa-print me-2"></i>Print
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Add modal to body
        document.body.insertAdjacentHTML('beforeend', modalHtml);

        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('dynamicCredentialsModal'));
        modal.show();

        // Remove modal on hide
        document.getElementById('dynamicCredentialsModal').addEventListener('hidden.bs.modal', function() {
            this.remove();
        });
    }

    function printDynamicCredentials() {
        const email = document.getElementById('dynamicEmailField').value;
        const password = document.getElementById('dynamicPasswordField').value;
        const employeeId = document.getElementById('dynamicEmployeeIdField').value;

        const printContent = `
            <html>
            <head>
                <title>Employee Login Credentials</title>
                <style>
                    body { font-family: Arial, sans-serif; padding: 30px; max-width: 500px; margin: 0 auto; }
                    .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 15px; }
                    h2 { color: #2c3e50; margin: 0; }
                    .company { color: #7f8c8d; font-size: 14px; }
                    .credentials-container { margin: 20px 0; }
                    .credential-item { margin: 15px 0; padding: 12px; background: #f8f9fa; border-radius: 6px; border-left: 4px solid #3498db; }
                    .label { font-weight: bold; color: #7f8c8d; font-size: 12px; text-transform: uppercase; margin-bottom: 5px; }
                    .value { color: #2c3e50; font-size: 16px; font-family: monospace; }
                    .footer { margin-top: 30px; padding-top: 15px; border-top: 1px solid #ddd; font-size: 12px; color: #95a5a6; text-align: center; }
                    .warning { background: #fff3cd; padding: 10px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #ffc107; }
                    @media print {
                        body { padding: 0; }
                        .no-print { display: none; }
                    }
                </style>
            </head>
            <body>
                <div class="header">
                    <h2>EMPLOYEE PASSWORD UPDATE</h2>
                    <div class="company">{{ config('app.name', 'Employee Management System') }}</div>
                </div>

                <div class="warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>PASSWORD HAS BEEN UPDATED</strong><br>
                    Previous password is no longer valid.
                </div>

                <div class="credentials-container">
                    <div class="credential-item">
                        <div class="label">Email Address</div>
                        <div class="value">${email}</div>
                    </div>

                    <div class="credential-item">
                        <div class="label">New Password</div>
                        <div class="value">${password}</div>
                    </div>

                    <div class="credential-item">
                        <div class="label">Employee ID</div>
                        <div class="value">${employeeId}</div>
                    </div>
                </div>

                <div class="instructions" style="margin: 25px 0; padding: 15px; background: #e8f4fc; border-radius: 6px;">
                    <strong>Login Instructions:</strong>
                    <ol style="margin: 10px 0 0 0; padding-left: 20px;">
                        <li>Go to: <strong>${window.location.origin}/login</strong></li>
                        <li>Enter your email and <strong>NEW PASSWORD</strong></li>
                        <li>Change your password after first login (recommended)</li>
                        <li>Contact IT support if you encounter issues</li>
                    </ol>
                </div>

                <div class="footer">
                    Password updated on: ${new Date().toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    })}
                </div>
            </body>
            </html>
        `;

        const printWindow = window.open('', '_blank');
        printWindow.document.write(printContent);
        printWindow.document.close();
        printWindow.focus();

        setTimeout(() => {
            printWindow.print();
            printWindow.close();
        }, 250);
    }
</script>
@endpush
@endsection
