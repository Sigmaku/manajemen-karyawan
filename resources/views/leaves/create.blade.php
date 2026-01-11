@extends('layouts.app')

@section('title', 'Ajukan Cuti Baru')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h4 class="mb-0">Form Pengajuan Cuti</h4>
                </div>

                <div class="card-body">
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form action="{{ route('leaves.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <!-- Pilih Karyawan (jika admin) -->
                        @if(auth()->user()->is_admin ?? false)
                        <div class="mb-4">
                            <label for="employee_id" class="form-label">Pilih Karyawan <span class="text-danger">*</span></label>
                            <select name="employee_id" id="employee_id" class="form-select @error('employee_id') is-invalid @enderror" required>
                                <option value="">-- Pilih Karyawan --</option>
                                @foreach($employees as $id => $employee)
                                    <option value="{{ $id }}" {{ old('employee_id') == $id ? 'selected' : '' }}>
                                        {{ $employee['name'] ?? 'Unknown' }} ({{ $employee['department'] ?? '-' }})
                                    </option>
                                @endforeach
                            </select>
                            @error('employee_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        @else
                            <!-- Jika karyawan biasa, kirim employee_id dari session/user -->
                            <input type="hidden" name="employee_id" value="{{ session('user')['employee_id'] ?? '' }}">
                        @endif

                        <!-- Jenis Cuti -->
                        <div class="mb-4">
                            <label for="leave_type" class="form-label">Jenis Cuti <span class="text-danger">*</span></label>
                            <select name="leave_type" id="leave_type" class="form-select @error('leave_type') is-invalid @enderror" required>
                                <option value="">-- Pilih Jenis Cuti --</option>
                                @foreach($leaveTypes as $key => $name)
                                    <option value="{{ $key }}" {{ old('leave_type') == $key ? 'selected' : '' }}>
                                        {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('leave_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Tanggal Mulai & Selesai -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="start_date" class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                                <input type="date" name="start_date" id="start_date"
                                       class="form-control @error('start_date') is-invalid @enderror"
                                       value="{{ old('start_date') }}" 
                                       min="{{ date('Y-m-d') }}" 
                                       required>
                                @error('start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="end_date" class="form-label">Tanggal Selesai <span class="text-danger">*</span></label>
                                <input type="date" name="end_date" id="end_date"
                                       class="form-control @error('end_date') is-invalid @enderror"
                                       value="{{ old('end_date') }}" 
                                       min="{{ date('Y-m-d') }}"
                                       required>
                                @error('end_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Alasan Cuti -->
                        <div class="mb-4">
                            <label for="reason" class="form-label">Alasan Cuti <span class="text-danger">*</span></label>
                            <textarea name="reason" id="reason" rows="4"
                                      class="form-control @error('reason') is-invalid @enderror"
                                      placeholder="Jelaskan alasan pengajuan cuti..." 
                                      maxlength="500"
                                      required>{{ old('reason') }}</textarea>
                            <small class="text-muted">Maksimal 500 karakter</small>
                            @error('reason')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Kontak Selama Cuti -->
                        <div class="mb-4">
                            <label for="contact_during_leave" class="form-label">Kontak Selama Cuti <span class="text-danger">*</span></label>
                            <input type="text" name="contact_during_leave" id="contact_during_leave"
                                   class="form-control @error('contact_during_leave') is-invalid @enderror"
                                   value="{{ old('contact_during_leave', session('user')['phone'] ?? '') }}"
                                   placeholder="Nomor HP / Email yang bisa dihubungi" 
                                   required>
                            @error('contact_during_leave')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- 🔥 BUKTI CUTI (NEW) 🔥 -->
                        <div class="mb-4">
                            <label for="proof" class="form-label">
                                Bukti Pendukung <span class="text-danger">*</span> <!-- Tambah asterisk -->
                            </label>
                            
                            <div class="alert alert-warning mb-3">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-info-circle fa-lg mt-1"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="alert-heading">Informasi Upload Bukti</h6>
                                        <p class="mb-1">Format file yang diterima: <strong>JPG, JPEG, PNG</strong></p>
                                        <p class="mb-0">Ukuran maksimal: <strong>2MB</strong></p>
                                        <small class="d-block mt-1">Contoh: Surat dokter, undangan, tiket perjalanan, atau dokumen pendukung lainnya.</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="input-group">
                                <input type="file" 
                                       name="proof" 
                                       id="proof" 
                                       class="form-control @error('proof') is-invalid @enderror"
                                       accept=".jpg,.jpeg,.png,image/jpg,image/jpeg,image/png">
                                <label class="input-group-text" for="proof">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                </label>
                            </div>
                            
                            @error('proof')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            
                            <!-- File Info -->
                            <div id="fileInfo" class="mt-2 text-muted small" style="display: none;">
                                <i class="fas fa-file me-1"></i>
                                <span id="fileName"></span> 
                                <span id="fileSize" class="ms-2"></span>
                            </div>
                            
                            <!-- Preview Image -->
                            <div class="mt-3" id="proofPreview" style="display: none;">
                                <div class="card border">
                                    <div class="card-body p-2">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0">
                                                <img id="previewImage" class="rounded" style="width: 80px; height: 80px; object-fit: cover;">
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="mb-1" id="previewFileName"></h6>
                                                <small class="text-muted" id="previewFileSize"></small>
                                            </div>
                                            <div class="flex-shrink-0">
                                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeProof()">
                                                    <i class="fas fa-trash"></i> Hapus
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-5">
                            <a href="{{ session('user')['role'] === 'employee' ? route('leaves.my') : route('leaves.index') }}" 
                               class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Kembali
                            </a>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="fas fa-paper-plane me-2"></i>Ajukan Cuti
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .file-upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            background-color: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s;
        }
        .file-upload-area:hover {
            border-color: #0d6efd;
            background-color: #e9f2ff;
        }
        .file-upload-area.dragover {
            border-color: #0d6efd;
            background-color: #d1e7ff;
        }
        #previewImage {
            max-width: 100%;
            max-height: 200px;
            border-radius: 6px;
        }
    </style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const proofInput = document.getElementById('proof');
    proofInput.required = true; // Client-side validation
    const fileInfo = document.getElementById('fileInfo');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    const proofPreview = document.getElementById('proofPreview');
    const previewImage = document.getElementById('previewImage');
    const previewFileName = document.getElementById('previewFileName');
    const previewFileSize = document.getElementById('previewFileSize');
    const submitBtn = document.getElementById('submitBtn');

    // Format bytes to readable size
    function formatBytes(bytes, decimals = 2) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }

    // Handle file selection
    proofInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        handleFileSelection(file);
    });

    // Handle drag and drop
    const dropArea = document.querySelector('.file-upload-area');
    if (dropArea) {
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, preventDefaults, false);
        });

        ['dragenter', 'dragover'].forEach(eventName => {
            dropArea.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, unhighlight, false);
        });

        dropArea.addEventListener('drop', handleDrop, false);
    }

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    function highlight() {
        dropArea.classList.add('dragover');
    }

    function unhighlight() {
        dropArea.classList.remove('dragover');
    }

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const file = dt.files[0];
        proofInput.files = dt.files;
        handleFileSelection(file);
    }

    function handleFileSelection(file) {
        if (!file) return;

        // Validation: File type
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
        if (!allowedTypes.includes(file.type)) {
            alert('Format file tidak didukung. Hanya JPG, JPEG, dan PNG yang diperbolehkan.');
            proofInput.value = '';
            return;
        }

        // Validation: File size (2MB = 2097152 bytes)
        if (file.size > 2097152) {
            alert('Ukuran file terlalu besar. Maksimal 2MB.');
            proofInput.value = '';
            return;
        }

        // Show file info
        fileName.textContent = file.name;
        fileSize.textContent = `(${formatBytes(file.size)})`;
        fileInfo.style.display = 'block';

        // Show preview for images
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImage.src = e.target.result;
                previewFileName.textContent = file.name;
                previewFileSize.textContent = formatBytes(file.size);
                proofPreview.style.display = 'block';
            }
            reader.readAsDataURL(file);
        }

        // Enable submit button
        if (submitBtn) {
            submitBtn.disabled = false;
        }
    }

    // Remove proof file
    window.removeProof = function() {
        proofInput.value = '';
        fileInfo.style.display = 'none';
        proofPreview.style.display = 'none';
        previewImage.src = '';
        
        if (submitBtn) {
            submitBtn.disabled = false;
        }
    }

    // Date validation: end date must be after start date
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');

    function validateDates() {
        const startDate = new Date(startDateInput.value);
        const endDate = new Date(endDateInput.value);
        
        if (startDate && endDate && endDate < startDate) {
            endDateInput.setCustomValidity('Tanggal selesai harus setelah tanggal mulai');
        } else {
            endDateInput.setCustomValidity('');
        }
    }

    if (startDateInput && endDateInput) {
        startDateInput.addEventListener('change', validateDates);
        endDateInput.addEventListener('change', validateDates);
    }

    // Form submission validation
    // Form submission validation
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
    const proofFile = proofInput.files[0];
    
    // Check if proof is uploaded
    if (!proofFile) {
        e.preventDefault();
        alert('⚠️ Bukti pendukung wajib diunggah!');
        proofInput.focus();
        return;
    }
    
    // Double-check file size on submission
    if (proofFile.size > 2097152) {
        e.preventDefault();
        alert('⚠️ Ukuran file terlalu besar. Maksimal 2MB.');
        return;
    }
    
    // Double-check file type
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
    if (!allowedTypes.includes(proofFile.type)) {
        e.preventDefault();
        alert('⚠️ Format file tidak didukung. Hanya JPG, JPEG, dan PNG yang diperbolehkan.');
        return;
    }
    
    // Show loading state
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Mengajukan...';
    }
});
</script>
@endpush