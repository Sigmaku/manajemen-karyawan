@extends('layouts.app')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Add New Employee</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('employees.store') }}" method="POST" id="employeeForm">
                        @csrf

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name *</label>
                                <input type="text" class="form-control" name="name" value="{{ old('name') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email Address *</label>
                                <input type="email" class="form-control" name="email" value="{{ old('email') }}" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Phone Number *</label>
                                <input type="text" class="form-control" name="phone" value="{{ old('phone') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Department *</label>
                                <select class="form-select" name="department" required>
                                    <option value="">Select Department</option>
                                    @foreach($departments as $dept)
                                    <option value="{{ $dept }}" {{ old('department') == $dept ? 'selected' : '' }}>{{ $dept }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Position *</label>
                                <select class="form-select" name="position" required>
                                    <option value="">Select Position</option>
                                    @foreach($positions as $pos)
                                    <option value="{{ $pos }}" {{ old('position') == $pos ? 'selected' : '' }}>{{ $pos }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Hire Date *</label>
                                <input type="date" class="form-control" name="hire_date" value="{{ old('hire_date', date('Y-m-d')) }}" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Salary</label>
                                <input type="number" class="form-control" name="salary" value="{{ old('salary') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="address" rows="2">{{ old('address') }}</textarea>
                            </div>
                        </div>

                        <!-- Login Account Section -->
                        <div class="card border mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Login Account Settings</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="create_account" value="1" id="createAccount" checked>
                                        <label class="form-check-label" for="createAccount">
                                            <strong>Create Login Account</strong>
                                        </label>
                                    </div>
                                    <small class="text-muted">Enable to create login credentials for the employee</small>
                                </div>

                                <div id="passwordSection">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Password Option *</label>
                                            <div class="mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="password_option" id="passwordOptionRandom" value="random" checked>
                                                    <label class="form-check-label" for="passwordOptionRandom">
                                                        Generate Random Password
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="password_option" id="passwordOptionCustom" value="custom">
                                                    <label class="form-check-label" for="passwordOptionCustom">
                                                        Set Custom Password
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Password Strength</label>
                                            <div class="progress mb-1">
                                                <div class="progress-bar bg-success" role="progressbar" id="passwordStrengthBar" style="width: 0%"></div>
                                            </div>
                                            <small id="passwordStrengthText" class="text-muted">Enter password to check strength</small>
                                        </div>
                                    </div>

                                    <div class="row mb-3" id="customPasswordSection" style="display: none;">
                                        <div class="col-md-6">
                                            <label class="form-label">Password *</label>
                                            <div class="input-group">
                                                <input type="password" class="form-control" name="password" id="passwordInput" placeholder="Enter custom password">
                                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                            <div class="form-text">
                                                <small>
                                                    <i class="fas fa-info-circle"></i> Minimum 8 characters with letters and numbers
                                                </small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Confirm Password *</label>
                                            <input type="password" class="form-control" name="password_confirmation" id="passwordConfirm" placeholder="Confirm password">
                                            <div id="passwordMatch" class="form-text"></div>
                                        </div>
                                    </div>

                                    <div class="row" id="randomPasswordSection">
                                        <div class="col-md-12">
                                            <div class="alert alert-info">
                                                <i class="fas fa-key me-2"></i>
                                                <strong>Random Password:</strong>
                                                <span id="randomPasswordPreview" class="fw-bold"></span>
                                                <button type="button" class="btn btn-sm btn-outline-primary ms-2" onclick="generateRandomPassword()">
                                                    <i class="fas fa-sync-alt me-1"></i> Regenerate
                                                </button>
                                                <input type="hidden" name="random_password" id="randomPassword">
                                            </div>
                                            <small class="text-muted">Password will be generated automatically and shown after employee creation</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('employees.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back
                            </a>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="fas fa-user-plus me-2"></i>Create Employee
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Credentials Modal -->
@if(session('credentials'))
<div class="modal fade" id="credentialsModal" tabindex="-1" aria-labelledby="credentialsModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="credentialsModalLabel">
                    <i class="fas fa-user-check me-2"></i>Login Account Created
                </h5>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Please save these credentials. They will not be shown again.
                </div>

                <div class="credentials-box p-3 bg-light rounded">
                    <div class="mb-3">
                        <label class="form-label text-muted">Email</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="emailField"
                                   value="{{ session('credentials.email') }}" readonly>
                            <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('emailField')">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted">Password</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="passwordField"
                                   value="{{ session('credentials.password') }}" readonly>
                            <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('passwordField')">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                        <small class="text-muted">Employee should change password on first login</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted">Employee ID</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="employeeIdField"
                                   value="{{ session('credentials.employee_id') }}" readonly>
                            <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('employeeIdField')">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="alert alert-warning mt-3 mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Important:</strong> Share these credentials securely with the employee.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="printCredentials()">
                    <i class="fas fa-print me-2"></i>Print
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Copy Toast -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
    <div id="copyToast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas fa-check-circle me-2"></i>
                Copied to clipboard!
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
    // Generate initial random password
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

        // Show strength
        checkPasswordStrength(password);
    }

    // Call on page load
    document.addEventListener('DOMContentLoaded', function() {
        generateRandomPassword();

        // Show credentials modal if exists
        @if(session('credentials'))
        const credentialsModal = new bootstrap.Modal(document.getElementById('credentialsModal'));
        credentialsModal.show();
        @endif
    });

    // Toggle password visibility
    document.getElementById('togglePassword').addEventListener('click', function() {
        const passwordInput = document.getElementById('passwordInput');
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
    });

    // Password option toggle
    document.querySelectorAll('input[name="password_option"]').forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'custom') {
                document.getElementById('customPasswordSection').style.display = 'flex';
                document.getElementById('randomPasswordSection').style.display = 'none';
                document.getElementById('passwordInput').required = true;
                document.getElementById('passwordConfirm').required = true;
            } else {
                document.getElementById('customPasswordSection').style.display = 'none';
                document.getElementById('randomPasswordSection').style.display = 'block';
                document.getElementById('passwordInput').required = false;
                document.getElementById('passwordConfirm').required = false;
                generateRandomPassword();
            }
        });
    });

    // Check password strength
    function checkPasswordStrength(password) {
        let strength = 0;
        const bar = document.getElementById('passwordStrengthBar');
        const text = document.getElementById('passwordStrengthText');

        if (password.length >= 8) strength += 25;
        if (/[a-z]/.test(password)) strength += 25;
        if (/[A-Z]/.test(password)) strength += 25;
        if (/[0-9]/.test(password)) strength += 15;
        if (/[!@#$%^&*]/.test(password)) strength += 10;

        bar.style.width = strength + '%';

        if (strength < 50) {
            bar.className = 'progress-bar bg-danger';
            text.textContent = 'Weak';
            text.className = 'text-danger';
        } else if (strength < 75) {
            bar.className = 'progress-bar bg-warning';
            text.textContent = 'Medium';
            text.className = 'text-warning';
        } else {
            bar.className = 'progress-bar bg-success';
            text.textContent = 'Strong';
            text.className = 'text-success';
        }
    }

    // Real-time password strength checking
    document.getElementById('passwordInput').addEventListener('input', function() {
        checkPasswordStrength(this.value);
        checkPasswordMatch();
    });

    // Check password match
    function checkPasswordMatch() {
        const password = document.getElementById('passwordInput').value;
        const confirm = document.getElementById('passwordConfirm').value;
        const matchDiv = document.getElementById('passwordMatch');

        if (!password || !confirm) {
            matchDiv.textContent = '';
            matchDiv.className = 'form-text';
            return;
        }

        if (password === confirm) {
            matchDiv.innerHTML = '<i class="fas fa-check-circle text-success me-1"></i> Passwords match';
            matchDiv.className = 'form-text text-success';
        } else {
            matchDiv.innerHTML = '<i class="fas fa-times-circle text-danger me-1"></i> Passwords do not match';
            matchDiv.className = 'form-text text-danger';
        }
    }

    document.getElementById('passwordConfirm').addEventListener('input', checkPasswordMatch);

    // Form validation
    document.getElementById('employeeForm').addEventListener('submit', function(e) {
        const createAccount = document.getElementById('createAccount').checked;
        const passwordOption = document.querySelector('input[name="password_option"]:checked').value;

        if (createAccount) {
            if (passwordOption === 'custom') {
                const password = document.getElementById('passwordInput').value;
                const confirm = document.getElementById('passwordConfirm').value;

                if (!password || password.length < 8) {
                    e.preventDefault();
                    alert('Password must be at least 8 characters long');
                    return;
                }

                if (password !== confirm) {
                    e.preventDefault();
                    alert('Passwords do not match');
                    return;
                }

                // Additional strength check
                if (password.length < 8 || !/[a-z]/.test(password) || !/[A-Z]/.test(password) || !/[0-9]/.test(password)) {
                    e.preventDefault();
                    alert('Password must contain at least:\n- 8 characters\n- One uppercase letter\n- One lowercase letter\n- One number');
                    return;
                }
            }
        }

        // Show loading
        document.getElementById('submitBtn').innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating...';
        document.getElementById('submitBtn').disabled = true;
    });

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
</script>
@endpush
@endsection
