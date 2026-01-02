@extends('layouts.app')
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Employee Management</h1>
        <a href="{{ route('employees.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Add Employee
        </a>
    </div>

    @if(isset($error))
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle me-2"></i>
        {{ $error }}
    </div>
    @endif

    <div class="card">
        <div class="card-body">
            <!-- Filters -->
            <div class="row mb-3">
                <div class="col-md-3">
                    <input type="text" class="form-control" placeholder="Search..." id="searchInput">
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="departmentFilter">
                        <option value="">All Departments</option>
                        <option value="IT">IT</option>
                        <option value="HR">HR</option>
                        <option value="Finance">Finance</option>
                        <option value="Marketing">Marketing</option>
                        <option value="Operations">Operations</option>
                        <option value="Sales">Sales</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="statusFilter">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-outline-secondary w-100" onclick="resetFilters()">
                        <i class="fas fa-redo me-2"></i>Reset
                    </button>
                </div>
            </div>

            <!-- Employees Table -->
            <div class="table-responsive">
                <table class="table table-hover" id="employeesTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Department</th>
                            <th>Position</th>
                            <th>Status</th>
                            <th>Join Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $counter = 0;
                        @endphp

                        @forelse($employees as $id => $employee)
                            @php
                                $counter++;
                                // SAFE ACCESS dengan null coalescing
                                $name = $employee['name'] ?? 'Unknown';
                                $email = $employee['email'] ?? '-';
                                $phone = $employee['phone'] ?? $employee['phoneNumber'] ?? '-';
                                $department = $employee['department'] ?? $employee['dept'] ?? 'N/A';
                                $position = $employee['position'] ?? $employee['jobTitle'] ?? 'N/A';
                                $status = $employee['status'] ?? 'active'; // DEFAULT VALUE
                                $joinDate = $employee['joinDate'] ?? $employee['hire_date'] ?? $employee['createdAt'] ?? '-';

                                // Format join date jika ada
                                if ($joinDate != '-') {
                                    try {
                                        $joinDate = \Carbon\Carbon::parse($joinDate)->format('d M Y');
                                    } catch (\Exception $e) {
                                        $joinDate = 'Invalid date';
                                    }
                                }

                                // Status badge color
                                $statusColor = 'secondary';
                                if ($status == 'active') $statusColor = 'success';
                                if ($status == 'inactive') $statusColor = 'danger';
                                if ($status == 'pending') $statusColor = 'warning';
                            @endphp

                            <tr>
                                <td>{{ $id }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-light rounded-circle me-2">
                                            <i class="fas fa-user text-primary p-2"></i>
                                        </div>
                                        <div>
                                            <strong>{{ $name }}</strong>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $email }}</td>
                                <td>{{ $phone }}</td>
                                <td>
                                    <span class="badge bg-info">{{ $department }}</span>
                                </td>
                                <td>{{ $position }}</td>
                                <td>
                                    <span class="badge bg-{{ $statusColor }}">
                                        {{ ucfirst($status) }}
                                    </span>
                                </td>
                                <td>{{ $joinDate }}</td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('employees.show', $id) }}" class="btn btn-sm btn-info" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('employees.edit', $id) }}" class="btn btn-sm btn-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button onclick="deleteEmployee('{{ $id }}', '{{ addslashes($name) }}')"
                                                class="btn btn-sm btn-danger" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fas fa-users fa-2x mb-3"></i>
                                        <p>No employees found. Add your first employee!</p>
                                        <a href="{{ route('employees.create') }}" class="btn btn-primary">
                                            <i class="fas fa-user-plus me-2"></i>Add Employee
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse

                        @if($counter > 0)
                            <tr>
                                <td colspan="9" class="text-end text-muted">
                                    <small>Total: {{ $counter }} employee(s)</small>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function deleteEmployee(id, name) {
        if (confirm(`Are you sure you want to delete "${name}"?`)) {
            fetch(`/employees/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => {
                if (response.redirected) {
                    window.location.href = response.url;
                } else {
                    return response.json();
                }
            })
            .then(data => {
                if (data && data.success) {
                    showToast('success', data.message || 'Employee deleted successfully');
                    setTimeout(() => location.reload(), 1000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'Failed to delete employee');
            });
        }
    }

    // Table filtering
    $('#searchInput').on('keyup', function() {
        const value = $(this).val().toLowerCase();
        $('#employeesTable tbody tr').each(function() {
            const rowText = $(this).text().toLowerCase();
            $(this).toggle(rowText.indexOf(value) > -1);
        });
    });

    $('#departmentFilter').on('change', function() {
        const dept = $(this).val();
        if (!dept) {
            $('#employeesTable tbody tr').show();
            return;
        }

        $('#employeesTable tbody tr').each(function() {
            const rowDept = $(this).find('td:eq(4)').text();
            $(this).toggle(rowDept.includes(dept));
        });
    });

    $('#statusFilter').on('change', function() {
        const status = $(this).val();
        if (!status) {
            $('#employeesTable tbody tr').show();
            return;
        }

        $('#employeesTable tbody tr').each(function() {
            const rowStatus = $(this).find('td:eq(6)').text().toLowerCase();
            $(this).toggle(rowStatus.includes(status));
        });
    });

    function resetFilters() {
        $('#searchInput').val('');
        $('#departmentFilter').val('');
        $('#statusFilter').val('');
        $('#employeesTable tbody tr').show();
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

        $('.toast-container').append(toastHtml);
        $('.toast').toast('show');

        setTimeout(() => {
            $('.toast').toast('hide');
            setTimeout(() => $('.toast').remove(), 300);
        }, 3000);
    }
</script>
@endpush
@endsection
