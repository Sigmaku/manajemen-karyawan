@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Edit Employee: {{ $employee['name'] }}</h4>
                    <div>
                        <a href="{{ route('employees.show', $employee['id']) }}" class="btn btn-sm btn-info me-2">
                            <i class="fas fa-eye me-1"></i> View
                        </a>
                        <a href="{{ route('employees.index') }}" class="btn btn-sm btn-secondary">
                            <i class="fas fa-times me-1"></i> Cancel
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Edit Employee Form -->
                    <form action="{{ route('employees.update', $employee['id']) }}" method="POST" id="editEmployeeForm">
                        @csrf
                        @method('PUT')

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name *</label>
                                <input type="text" class="form-control" name="name" value="{{ $employee['name'] }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email *</label>
                                <input type="email" class="form-control" name="email" value="{{ $employee['email'] }}" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Phone Number</label>
                                <input type="text" class="form-control" name="phone" value="{{ $employee['phone'] ?? '' }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status *</label>
                                <select class="form-select" name="status" required>
                                    <option value="active" {{ ($employee['status'] ?? 'active') == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ ($employee['status'] ?? 'active') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    <option value="suspended" {{ ($employee['status'] ?? 'active') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                                    <option value="resigned" {{ ($employee['status'] ?? 'active') == 'resigned' ? 'selected' : '' }}>Resigned</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Department *</label>
                                <select class="form-select" name="department" required>
                                    <option value="">Select Department</option>
                                    @foreach($departments as $dept)
                                    <option value="{{ $dept }}" {{ ($employee['department'] ?? '') == $dept ? 'selected' : '' }}>{{ $dept }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Position *</label>
                                <select class="form-select" name="position" required>
                                    <option value="">Select Position</option>
                                    @foreach($positions as $pos)
                                    <option value="{{ $pos }}" {{ ($employee['position'] ?? '') == $pos ? 'selected' : '' }}>{{ $pos }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address" rows="2">{{ $employee['address'] ?? '' }}</textarea>
                        </div>

                        @if(isset($employee['salary']))
                        <div class="mb-3">
                            <label class="form-label">Salary</label>
                            <input type="number" class="form-control" name="salary" value="{{ $employee['salary'] }}">
                        </div>
                        @endif

                        <div class="d-flex justify-content-between mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Employee
                            </button>
                        </div>
                    </form>

                    <!-- Account Management Section -->
                    @if(isset($employee['uid']) && !empty($employee['uid']))
                    <hr class="my-4">

                    <div class="card border">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Account Management</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                This employee has a login account. You can manage their password here.
                            </div>

                            <!-- Change Password Form -->
                            <form id="changePasswordForm">
                                @csrf
                                <input type="hidden" name="employee_id" value="{{ $employee['id'] }}">

                                <h6 class="mb-3">Change Password</h6>

                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Password Option</label>
                                        <div class="mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="password_option" id="editRandomPassword" value="random" checked>
                                                <label class="form-check-label" for="editRandomPassword">
                                                    Generate Random Password
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="password_option" id="editCustomPassword" value="custom">
                                                <label class="form-check-label" for="editCustomPassword">
                                                    Set Custom Password
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-8">
                                        <label class="form-label">Password Strength</label>
                                        <div class="progress mb-1">
                                            <div class="progress-bar bg-success" role="progressbar" id="editPasswordStrengthBar" style="width: 0%"></div>
                                        </div>
                                        <small id="editPasswordStrengthText" class="text-muted">Enter password to check strength</small>
                                    </div>
                                </div>

                                <div class="row mb-3" id="editCustomPasswordSection" style="display: none;">
                                    <div class="col-md-6">
                                        <label class="form-label">New Password *</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" name="new_password" id="editPasswordInput" placeholder="Enter new password">
                                            <button class="btn btn-outline-secondary" type="button" id="toggleEditPassword">
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
                                        <input type="password" class="form-control" name="new_password_confirmation" id="editPasswordConfirm" placeholder="Confirm password">
                                        <div id="editPasswordMatch" class="form-text"></div>
                                    </div>
                                </div>

                                <div class="row mb-4" id="editRandomPasswordSection">
                                    <div class="col-md-12">
                                        <div class="alert alert-info">
                                            <i class="fas fa-key me-2"></i>
                                            <strong>Random Password:</strong>
                                            <span id="editRandomPasswordPreview" class="fw-bold"></span>
                                            <button type="button" class="btn btn-sm btn-outline-primary ms-2" onclick="generateEditRandomPassword()">
                                                <i class="fas fa-sync-alt me-1"></i> Regenerate
                                            </button>
                                            <input type="hidden" name="random_password" id="editRandomPassword">
                                        </div>
                                        <small class="text-muted">A random password will be generated and shown after update</small>
                                    </div>
                                </div>

                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Note:</strong> Changing password will log the employee out of all devices.
                                </div>

                                <div class="d-flex justify-content-between">
                                    <button type="button" class="btn btn-secondary" onclick="cancelPasswordChange()">
                                        <i class="fas fa-times me-2"></i>Cancel
                                    </button>
                                    <button type="button" class="btn btn-primary" onclick="updateEmployeePassword()">
                                        <i class="fas fa-key me-2"></i>Change Password
                                    </button>
                                </div>
                            </form>

                            <!-- Additional Account Actions -->
                            <div class="mt-4">
                                <h6 class="mb-3">Quick Actions</h6>
                                <div class="d-flex gap-2 flex-wrap">
                                    <form action="{{ route('employees.reset-password', $employee['id']) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-info btn-sm" onclick="return confirm('Reset password for {{ $employee['name'] }}?')">
                                            <i class="fas fa-redo me-1"></i> Reset Password
                                        </button>
                                    </form>

                                    <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#forcePasswordChangeModal">
                                        <i class="fas fa-exclamation-triangle me-1"></i> Force Password Change
                                    </button>

                                    <button type="button" class="btn btn-success btn-sm" onclick="showPasswordHistory()">
                                        <i class="fas fa-history me-1"></i> Password History
                                    </button>

                                    <button type="button" class="btn btn-danger btn-sm" onclick="disableAccount()">
                                        <i class="fas fa-user-slash me-1"></i> Disable Account
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    @else
                    <!-- No Account Section -->
                    <hr class="my-4">

                    <div class="card border">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Account Management</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                This employee does not have a login account.
                            </div>

                            <form action="{{ route('employees.create-account', $employee['id']) }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label">Create Login Account</label>
                                    <p class="text-muted">Create a login account for this employee with email: <strong>{{ $employee['email'] }}</strong></p>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Password Option</label>
                                    <div class="mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="password_option" id="createRandomPassword" value="random" checked>
                                            <label class="form-check-label" for="createRandomPassword">
                                                Generate Random Password
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="password_option" id="createCustomPassword" value="custom">
                                            <label class="form-check-label" for="createCustomPassword">
                                                Set Custom Password
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3" id="createCustomPasswordSection" style="display: none;">
                                    <div class="col-md-6">
                                        <label class="form-label">Password *</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" name="password" id="createPasswordInput" placeholder="Enter password">
                                            <button class="btn btn-outline-secondary" type="button" id="toggleCreatePassword">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Confirm Password *</label>
                                        <input type="password" class="form-control" name="password_confirmation" id="createPasswordConfirm" placeholder="Confirm password">
                                    </div>
                                </div>

                                <div class="alert alert-info" id="createRandomPasswordSection">
                                    <i class="fas fa-key me-2"></i>
                                    A random password will be generated and shown after account creation.
                                </div>

                                <button type="submit" class="btn btn-success" onclick="return confirm('Create login account for {{ $employee['name'] }}?')">
                                    <i class="fas fa-user-plus me-2"></i> Create Login Account
                                </button>
                            </form>
                        </div>
                    </div>
                    @endif

                    <!-- Delete Form -->
                    <div class="mt-4">
                        <div class="card border-danger">
                            <div class="card-header bg-danger text-white">
                                <h5 class="mb-0">Danger Zone</h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted">Once you delete an employee, there is no going back. Please be certain.</p>
                                <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                                    <i class="fas fa-trash me-2"></i>Delete Employee
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Force Password Change Modal -->
<div class="modal fade" id="forcePasswordChangeModal" tabindex="-1" aria-labelledby="forcePasswordChangeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="forcePasswordChangeModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>Force Password Change
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong>Warning:</strong> This will generate a temporary password and force the employee to change it on next login.
                </div>

                <p>Are you sure you want to force a password change for <strong>{{ $employee['name'] }}</strong>?</p>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="notifyEmployee">
                    <label class="form-check-label" for="notifyEmployee">
                        Notify employee via email (if email service is configured)
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" onclick="forcePasswordChange()">
                    <i class="fas fa-key me-2"></i>Force Password Change
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Password History Modal -->
<div class="modal fade" id="passwordHistoryModal" tabindex="-1" aria-labelledby="passwordHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="passwordHistoryModalLabel">
                    <i class="fas fa-history me-2"></i>Password History for {{ $employee['name'] }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="passwordHistoryContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading password history...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        generateEditRandomPassword();
    });

    // For edit form - generate random password
    function generateEditRandomPassword() {
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

        document.getElementById('editRandomPasswordPreview').textContent = password;
        document.getElementById('editRandomPassword').value = password;

        // Show strength
        checkEditPasswordStrength(password);
    }

    // Toggle password visibility for edit form
    document.getElementById('toggleEditPassword').addEventListener('click', function() {
        const passwordInput = document.getElementById('editPasswordInput');
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
    });

    // Password option toggle for edit form
    document.querySelectorAll('#changePasswordForm input[name="password_option"]').forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'custom') {
                document.getElementById('editCustomPasswordSection').style.display = 'flex';
                document.getElementById('editRandomPasswordSection').style.display = 'none';
                document.getElementById('editPasswordInput').required = true;
                document.getElementById('editPasswordConfirm').required = true;
            } else {
                document.getElementById('editCustomPasswordSection').style.display = 'none';
                document.getElementById('editRandomPasswordSection').style.display = 'block';
                document.getElementById('editPasswordInput').required = false;
                document.getElementById('editPasswordConfirm').required = false;
                generateEditRandomPassword();
            }
        });
    });

    // Check password strength for edit form
    function checkEditPasswordStrength(password) {
        let strength = 0;
        const bar = document.getElementById('editPasswordStrengthBar');
        const text = document.getElementById('editPasswordStrengthText');

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

    // Real-time password strength checking for edit form
    document.getElementById('editPasswordInput').addEventListener('input', function() {
        checkEditPasswordStrength(this.value);
        checkEditPasswordMatch();
    });

    // Check password match for edit form
    function checkEditPasswordMatch() {
        const password = document.getElementById('editPasswordInput').value;
        const confirm = document.getElementById('editPasswordConfirm').value;
        const matchDiv = document.getElementById('editPasswordMatch');

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

    document.getElementById('editPasswordConfirm').addEventListener('input', checkEditPasswordMatch);

    // Update employee password
    function updateEmployeePassword() {
        const employeeId = '{{ $employee["id"] }}';
        const passwordOption = document.querySelector('#changePasswordForm input[name="password_option"]:checked').value;
        let password = '';

        // Validate
        if (passwordOption === 'custom') {
            const newPassword = document.getElementById('editPasswordInput').value;
            const confirmPassword = document.getElementById('editPasswordConfirm').value;

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
            password = document.getElementById('editRandomPassword').value;
            if (!password) {
                generateEditRandomPassword();
                password = document.getElementById('editRandomPassword').value;
            }
        }

        // Send AJAX request
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
                showToast('success', data.message);

                // Show credentials modal
                if (data.credentials) {
                    showEditCredentialsModal(data.credentials);
                }

                // Reset form
                document.getElementById('changePasswordForm').reset();
                generateEditRandomPassword();
                document.getElementById('editCustomPasswordSection').style.display = 'none';
                document.getElementById('editRandomPasswordSection').style.display = 'block';
                document.querySelector('#editRandomPassword').checked = true;
            } else {
                showToast('error', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('error', 'Network error. Please try again.');
        });
    }

    // Force password change
    function forcePasswordChange() {
        const employeeId = '{{ $employee["id"] }}';
        const notify = document.getElementById('notifyEmployee').checked;

        fetch('/employees/' + employeeId + '/force-password-change', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                notify: notify
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('forcePasswordChangeModal'));
                modal.hide();

                showToast('success', data.message);

                // Show temporary password
                if (data.temporary_password) {
                    showTemporaryPasswordModal(data.temporary_password);
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

    // Show password history
    function showPasswordHistory() {
        const employeeId = '{{ $employee["id"] }}';

        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('passwordHistoryModal'));
        modal.show();

        // Load history
        fetch('/employees/' + employeeId + '/password-history')
            .then(response => response.json())
            .then(data => {
                const content = document.getElementById('passwordHistoryContent');

                if (data.success && data.data && Object.keys(data.data).length > 0) {
                    let html = '<div class="table-responsive">';
                    html += '<table class="table table-hover">';
                    html += '<thead><tr><th>Date</th><th>Changed By</th><th>Type</th><th>Method</th></tr></thead>';
                    html += '<tbody>';

                    // Sort by timestamp (newest first)
                    const entries = Object.entries(data.data).sort((a, b) => b[0] - a[0]);

                    entries.forEach(([timestamp, entry]) => {
                        const date = new Date(parseInt(timestamp) * 1000);
                        html += '<tr>';
                        html += `<td>${date.toLocaleString()}</td>`;
                        html += `<td>${entry.changed_by || 'System'}</td>`;
                        html += `<td><span class="badge bg-info">${entry.type || 'unknown'}</span></td>`;
                        html += `<td>${entry.method || 'manual'}</td>`;
                        html += '</tr>';
                    });

                    html += '</tbody></table></div>';
                    content.innerHTML = html;
                } else {
                    content.innerHTML = `
                        <div class="text-center py-5">
                            <i class="fas fa-history fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No password history found</p>
                            <small>Password changes will appear here</small>
                        </div>
                    `;
                }
            })
            .catch(error => {
                document.getElementById('passwordHistoryContent').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Failed to load password history
                    </div>
                `;
            });
    }

    // Cancel password change
    function cancelPasswordChange() {
        document.getElementById('changePasswordForm').reset();
        generateEditRandomPassword();
        document.getElementById('editCustomPasswordSection').style.display = 'none';
        document.getElementById('editRandomPasswordSection').style.display = 'block';
        document.querySelector('#editRandomPassword').checked = true;
        showToast('info', 'Password change cancelled');
    }

    // Disable account
    function disableAccount() {
        if (confirm('Are you sure you want to disable this account?\nThe employee will not be able to login until re-enabled.')) {
            showToast('info', 'Account disable feature would be implemented here');
            // Implementation would call API to disable account
        }
    }

    // Show credentials modal after password change
    function showEditCredentialsModal(credentials) {
        const modalHtml = `
            <div class="modal fade" id="editCredentialsModal" tabindex="-1" aria-labelledby="editCredentialsModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title" id="editCredentialsModalLabel">
                                <i class="fas fa-key me-2"></i>Password Updated
                            </h5>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                Password has been successfully updated!
                            </div>

                            <div class="credentials-box p-3 bg-light rounded">
                                <div class="mb-3">
                                    <label class="form-label text-muted">Employee</label>
                                    <p class="mb-0"><strong>{{ $employee['name'] }}</strong></p>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label text-muted">Email</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="editEmailField"
                                               value="${credentials.email}" readonly>
                                        <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('editEmailField')">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label text-muted">New Password</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="editPasswordField"
                                               value="${credentials.password}" readonly>
                                        <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('editPasswordField')">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted">Share this new password with the employee</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label text-muted">Password Type</label>
                                    <p class="mb-0">
                                        <span class="badge bg-${credentials.password_type === 'custom' ? 'primary' : 'info'}">
                                            ${credentials.password_type === 'custom' ? 'Custom Password' : 'Random Password'}
                                        </span>
                                    </p>
                                </div>
                            </div>

                            <div class="alert alert-warning mt-3 mb-0">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Important:</strong> Employee must use this new password to login.
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary" onclick="printEditCredentials()">
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
        const modal = new bootstrap.Modal(document.getElementById('editCredentialsModal'));
        modal.show();

        // Remove modal on hide
        document.getElementById('editCredentialsModal').addEventListener('hidden.bs.modal', function() {
            this.remove();
        });
    }

    // Show temporary password modal
    function showTemporaryPasswordModal(tempPassword) {
        const modalHtml = `
            <div class="modal fade" id="tempPasswordModal" tabindex="-1" aria-labelledby="tempPasswordModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-warning">
                            <h5 class="modal-title" id="tempPasswordModalLabel">
                                <i class="fas fa-exclamation-triangle me-2"></i>Temporary Password Generated
                            </h5>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                Employee must change this temporary password on next login.
                            </div>

                            <div class="credentials-box p-3 bg-light rounded">
                                <div class="mb-3">
                                    <label class="form-label text-muted">Employee</label>
                                    <p class="mb-0"><strong>{{ $employee['name'] }}</strong></p>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label text-muted">Email</label>
                                    <p class="mb-0">{{ $employee['email'] }}</p>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label text-muted">Temporary Password</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="tempPasswordField"
                                               value="${tempPassword}" readonly>
                                        <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('tempPasswordField')">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted">This password is valid for one login only</small>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary" onclick="printTempPassword()">
                                <i class="fas fa-print me-2"></i>Print
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHtml);
        const modal = new bootstrap.Modal(document.getElementById('tempPasswordModal'));
        modal.show();

        document.getElementById('tempPasswordModal').addEventListener('hidden.bs.modal', function() {
            this.remove();
        });
    }

    // Copy to clipboard
    function copyToClipboard(elementId) {
        const element = document.getElementById(elementId);
        element.select();
        element.setSelectionRange(0, 99999);

        navigator.clipboard.writeText(element.value).then(() => {
            showToast('success', 'Copied to clipboard!');
        });
    }

    // Print credentials
    function printEditCredentials() {
        const email = document.getElementById('editEmailField').value;
        const password = document.getElementById('editPasswordField').value;

        const printContent = `
            <html>
            <head>
                <title>Password Updated - {{ $employee['name'] }}</title>
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
                    <h2>PASSWORD UPDATED</h2>
                    <div class="company">{{ config('app.name', 'Employee Management System') }}</div>
                </div>

                <div class="warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>PASSWORD HAS BEEN UPDATED</strong><br>
                    Previous password is no longer valid.
                </div>

                <div class="credentials-container">
                    <div class="credential-item">
                        <div class="label">Employee Name</div>
                        <div class="value">{{ $employee['name'] }}</div>
                    </div>

                    <div class="credential-item">
                        <div class="label">Email Address</div>
                        <div class="value">${email}</div>
                    </div>

                    <div class="credential-item">
                        <div class="label">New Password</div>
                        <div class="value">${password}</div>
                    </div>
                </div>

                <div class="instructions" style="margin: 25px 0; padding: 15px; background: #e8f4fc; border-radius: 6px;">
                    <strong>Login Instructions:</strong>
                    <ol style="margin: 10px 0 0 0; padding-left: 20px;">
                        <li>Go to: <strong>${window.location.origin}/login</strong></li>
                        <li>Enter your email and <strong>NEW PASSWORD</strong></li>
                        <li>You will be prompted to change your password</li>
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

    // Print temporary password
    function printTempPassword() {
        const tempPassword = document.getElementById('tempPasswordField').value;

        const printContent = `
            <html>
            <head>
                <title>Temporary Password - {{ $employee['name'] }}</title>
                <style>
                    body { font-family: Arial, sans-serif; padding: 30px; max-width: 500px; margin: 0 auto; }
                    .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 15px; }
                    h2 { color: #2c3e50; margin: 0; }
                    .company { color: #7f8c8d; font-size: 14px; }
                    .credentials-container { margin: 20px 0; }
                    .credential-item { margin: 15px 0; padding: 12px; background: #f8f9fa; border-radius: 6px; border-left: 4px solid #ffc107; }
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
                    <h2>TEMPORARY PASSWORD</h2>
                    <div class="company">{{ config('app.name', 'Employee Management System') }}</div>
                </div>

                <div class="warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>TEMPORARY PASSWORD - ONE TIME USE ONLY</strong><br>
                    This password is valid for one login only. User must change it immediately.
                </div>

                <div class="credentials-container">
                    <div class="credential-item">
                        <div class="label">Employee Name</div>
                        <div class="value">{{ $employee['name'] }}</div>
                    </div>

                    <div class="credential-item">
                        <div class="label">Email Address</div>
                        <div class="value">{{ $employee['email'] }}</div>
                    </div>

                    <div class="credential-item">
                        <div class="label">Temporary Password</div>
                        <div class="value">${tempPassword}</div>
                    </div>
                </div>

                <div class="instructions" style="margin: 25px 0; padding: 15px; background: #e8f4fc; border-radius: 6px;">
                    <strong>Important Instructions:</strong>
                    <ol style="margin: 10px 0 0 0; padding-left: 20px;">
                        <li>Go to: <strong>${window.location.origin}/login</strong></li>
                        <li>Enter your email and this <strong>TEMPORARY PASSWORD</strong></li>
                        <li>You <strong>MUST</strong> change your password immediately</li>
                        <li>This password will expire after first use</li>
                        <li>Contact IT support if you encounter issues</li>
                    </ol>
                </div>

                <div class="footer">
                    Temporary password generated on: ${new Date().toLocaleDateString('en-US', {
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

    // Toast notification
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
        if (!container) {
            // Create container if it doesn't exist
            const toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            document.body.appendChild(toastContainer);
        }

        const toastContainer = document.querySelector('.toast-container');
        toastContainer.insertAdjacentHTML('beforeend', toastHtml);
        const toast = toastContainer.lastElementChild;
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();

        setTimeout(() => {
            bsToast.hide();
            setTimeout(() => toast.remove(), 300);
        }, 5000);
    }

    // Delete confirmation
    function confirmDelete() {
        if (confirm('Are you sure you want to delete this employee?\nThis action cannot be undone.\n\nNote: If the employee has a login account, it will also be deleted.')) {
            // Create delete form
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("employees.destroy", $employee["id"]) }}';
            form.innerHTML = `
                @csrf
                @method('DELETE')
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    // Toggle for create account section (for employees without account)
    @if(!isset($employee['uid']) || empty($employee['uid']))
    document.querySelectorAll('input[name="password_option"]').forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'custom') {
                document.getElementById('createCustomPasswordSection').style.display = 'flex';
                document.getElementById('createRandomPasswordSection').style.display = 'none';
                document.getElementById('createPasswordInput').required = true;
                document.getElementById('createPasswordConfirm').required = true;
            } else {
                document.getElementById('createCustomPasswordSection').style.display = 'none';
                document.getElementById('createRandomPasswordSection').style.display = 'block';
                document.getElementById('createPasswordInput').required = false;
                document.getElementById('createPasswordConfirm').required = false;
            }
        });
    });

    document.getElementById('toggleCreatePassword').addEventListener('click', function() {
        const passwordInput = document.getElementById('createPasswordInput');
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
    });
    @endif
</script>
@endpush

<!-- Delete Form (hidden) -->
<form id="deleteForm" action="{{ route('employees.destroy', $employee['id']) }}" method="POST" class="d-none">
    @csrf
    @method('DELETE')
</form>
@endsection
