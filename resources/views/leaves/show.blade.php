{{-- resources/views/leaves/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Detail Pengajuan Cuti')

@section('content')
<div class="container-fluid py-4">
    <!-- Toast Notification -->
    @if(session('success'))
        <div class="position-fixed top-0 end-0 p-3" style="z-index: 9999;">
            <div class="toast align-items-center text-bg-success border-0 show" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fas fa-check-circle me-2"></i>
                        {{ session('success') }}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="position-fixed top-0 end-0 p-3" style="z-index: 9999;">
            <div class="toast align-items-center text-bg-danger border-0 show" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        {{ session('error') }}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        </div>
    @endif

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Detail Pengajuan Cuti</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="{{ $role === 'employee' ? route('leaves.my') : route('leaves.index') }}">
                            {{ $role === 'employee' ? 'Pengajuan Cuti Saya' : 'Manajemen Cuti' }}
                        </a>
                    </li>
                    <li class="breadcrumb-item active">Detail</li>
                </ol>
            </nav>
        </div>
        <a href="{{ $role === 'employee' ? route('leaves.my') : route('leaves.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Kembali
        </a>
    </div>

    <div class="row">
        <!-- Informasi Pengajuan -->
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">Informasi Pengajuan</h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <strong class="text-muted">Karyawan</strong>
                            <p class="mb-0 fs-5">{{ $employee['name'] }}</p>
                        </div>
                        <div class="col-md-6">
                            <strong class="text-muted">Departemen</strong>
                            <p class="mb-0 fs-5">{{ $employee['department'] ?? '-' }}</p>
                        </div>
                        <div class="col-md-6">
                            <strong class="text-muted">Jenis Cuti</strong>
                            <p class="mb-0 fs-5">{{ $leaveTypeName }}</p>
                        </div>
                        <div class="col-md-6">
                            <strong class="text-muted">Jumlah Hari</strong>
                            <p class="mb-0 fs-5">{{ $days }} hari</p>
                        </div>
                        <div class="col-md-6">
                            <strong class="text-muted">Tanggal Mulai</strong>
                            <p class="mb-0 fs-5">{{ $startDate->format('d F Y') }}</p>
                        </div>
                        <div class="col-md-6">
                            <strong class="text-muted">Tanggal Selesai</strong>
                            <p class="mb-0 fs-5">{{ $endDate->format('d F Y') }}</p>
                        </div>
                        <div class="col-12">
                            <strong class="text-muted">Alasan Cuti</strong>
                            <p class="mb-0 mt-2 p-3 bg-light rounded">{{ $leave->reason }}</p>
                        </div>
                        <div class="col-12">
                            <strong class="text-muted">Kontak Selama Cuti</strong>
                            <p class="mb-0 mt-2">{{ $leave->contact_during_leave }}</p>
                        </div>

                        <!-- 🔥 BUKTI PENDUKUNG 🔥 -->
                        <div class="col-12">
                            <strong class="text-muted">Bukti Pendukung</strong>
                            <div class="mt-2">
                                @if($leave->proof_url)
                                    <div class="card border">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0">
                                                    @php
                                                        $fileExt = strtolower(pathinfo($leave->proof_url, PATHINFO_EXTENSION));
                                                        $icon = 'fa-file-image';
                                                        if (in_array($fileExt, ['pdf'])) {
                                                            $icon = 'fa-file-pdf';
                                                        } elseif (in_array($fileExt, ['doc', 'docx'])) {
                                                            $icon = 'fa-file-word';
                                                        }
                                                    @endphp
                                                    <i class="fas {{ $icon }} fa-3x text-primary"></i>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <h6 class="mb-1">{{ $leave->proof_filename ?? 'bukti_cuti.' . $fileExt }}</h6>
                                                    <small class="text-muted">
                                                        Format: {{ strtoupper($fileExt) }}
                                                        @php
                                                            // Try to get file size from Cloudinary URL (optional)
                                                            $fileSize = null;
                                                            if (str_contains($leave->proof_url, 'cloudinary.com')) {
                                                                // Cloudinary biasanya ada info size di URL parameter
                                                                $fileSize = 'Uploaded ke Cloudinary';
                                                            }
                                                        @endphp
                                                        @if($fileSize)
                                                            • {{ $fileSize }}
                                                        @endif
                                                    </small>
                                                    <div class="mt-2">
                                                        <a href="{{ $leave->proof_url }}" 
                                                           target="_blank" 
                                                           class="btn btn-outline-primary btn-sm"
                                                           title="Lihat bukti di tab baru">
                                                            <i class="fas fa-external-link-alt me-1"></i> Buka di Tab Baru
                                                        </a>
                                                        <button type="button" 
                                                                class="btn btn-outline-secondary btn-sm ms-1"
                                                                onclick="previewProof('{{ $leave->proof_url }}')"
                                                                title="Preview gambar">
                                                            <i class="fas fa-eye me-1"></i> Preview
                                                        </button>
                                                        <a href="{{ $leave->proof_url }}" 
                                                           download="{{ $leave->proof_filename ?? 'bukti_cuti.' . $fileExt }}"
                                                           class="btn btn-outline-success btn-sm ms-1"
                                                           title="Download bukti">
                                                            <i class="fas fa-download me-1"></i> Download
                                                        </a>
                                                        @if(in_array($role, ['admin', 'manager']))
                                                        <button type="button" 
                                                                class="btn btn-outline-info btn-sm ms-1"
                                                                onclick="copyProofUrl('{{ $leave->proof_url }}')"
                                                                title="Salin link bukti">
                                                            <i class="fas fa-copy me-1"></i> Salin Link
                                                        </button>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="alert alert-light">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0">
                                                <i class="fas fa-info-circle text-muted"></i>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <p class="mb-0">Tidak ada bukti pendukung yang diunggah</p>
                                                <small class="text-muted">
                                                    Bukti pendukung bersifat opsional. Karyawan dapat mengunggah bukti seperti surat dokter, undangan, atau dokumen pendukung lainnya.
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="col-md-6">
                            <strong class="text-muted">Status</strong><br>
                            @switch($leave->status)
                                @case('pending')
                                    <span class="badge bg-warning text-dark fs-5 px-3 py-2">Menunggu Persetujuan</span>
                                    @break
                                @case('approved')
                                    <span class="badge bg-success fs-5 px-3 py-2">Disetujui</span>
                                    @break
                                @case('rejected')
                                    <span class="badge bg-danger fs-5 px-3 py-2">Ditolak</span>
                                    @break
                                @case('canceled')
                                    <span class="badge bg-secondary fs-5 px-3 py-2">Dibatalkan</span>
                                    @break
                                @default
                                    <span class="badge bg-info fs-5 px-3 py-2">{{ ucfirst($leave->status) }}</span>
                            @endswitch
                        </div>
                        <div class="col-md-6">
                            <strong class="text-muted">Diajukan Pada</strong>
                            <p class="mb-0 fs-5">{{ $leave->created_at->format('d F Y H:i') }}</p>
                        </div>

                        <!-- Approval/Rejection Info -->
                        @if($leave->status === 'approved' && $leave->approved_by)
                            <div class="col-12">
                                <div class="alert alert-success">
                                    <strong><i class="fas fa-check-circle me-2"></i>Disetujui oleh:</strong> {{ $leave->approved_by }}
                                    @if(isset($leave->approved_at))
                                        <br><small class="text-muted">Pada: {{ \Carbon\Carbon::parse($leave->approved_at)->format('d F Y H:i') }}</small>
                                    @endif
                                </div>
                            </div>
                        @elseif($leave->status === 'rejected' && $leave->rejection_reason)
                            <div class="col-12">
                                <div class="alert alert-danger">
                                    <strong><i class="fas fa-times-circle me-2"></i>Ditolak dengan alasan:</strong><br>
                                    {{ $leave->rejection_reason }}
                                    @if(isset($leave->rejected_by))
                                        <br><small class="text-muted">Oleh: {{ $leave->rejected_by }} • 
                                        @if(isset($leave->rejected_at))
                                            {{ \Carbon\Carbon::parse($leave->rejected_at)->format('d F Y H:i') }}
                                        @endif
                                        </small>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Aksi Persetujuan -->
        <div class="col-lg-4">
            @php
                $user = session('user');
                $role = $user['role'] ?? 'employee';
                $isApprover = in_array($role, ['admin', 'manager']);
            @endphp

            @if($leave->status === 'pending' && $isApprover)
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="fas fa-gavel me-2"></i>Aksi Persetujuan</h6>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('leaves.approve', $leave->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-success btn-lg w-100 mb-3 shadow-sm"
                                onclick="return confirm('Yakin ingin MENYETUJUI pengajuan cuti ini?')">
                                <i class="fas fa-check-circle fa-lg me-2"></i>
                                Setujui Cuti
                            </button>
                        </form>

                        <button type="button" class="btn btn-danger btn-lg w-100 shadow-sm"
                            data-bs-toggle="modal" data-bs-target="#rejectModal">
                            <i class="fas fa-times-circle fa-lg me-2"></i>
                            Tolak Cuti
                        </button>
                    </div>
                </div>
            @elseif($leave->status !== 'pending')
                <div class="card shadow-sm border-0">
                    <div class="card-body text-center py-5">
                        @if($leave->status === 'approved')
                            <i class="fas fa-check-double text-success fa-4x mb-4"></i>
                            <h5 class="text-success">Cuti Telah Disetujui</h5>
                            <p class="text-success">
                                <strong>Disetujui oleh:</strong><br>
                                {{ $leave->approved_by ?? '-' }}
                            </p>
                        @elseif($leave->status === 'rejected')
                            <i class="fas fa-times-circle text-danger fa-4x mb-4"></i>
                            <h5 class="text-danger">Cuti Telah Ditolak</h5>
                            <p class="text-danger">
                                <strong>Alasan penolakan:</strong><br>
                                {{ $leave->rejection_reason ?? '-' }}
                            </p>
                        @else
                            <i class="fas fa-ban text-secondary fa-4x mb-4"></i>
                            <h5 class="text-secondary">Pengajuan Telah Diproses</h5>
                        @endif
                    </div>
                </div>
            @else
                <!-- Untuk karyawan biasa yang melihat cuti sendiri -->
                <div class="card shadow-sm border-0">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-hourglass-half text-warning fa-4x mb-4"></i>
                        <h5 class="text-warning">Menunggu Persetujuan</h5>
                        <p class="text-muted">Pengajuan cuti Anda sedang ditinjau oleh atasan.</p>
                        
                        @if($leave->proof_url)
                            <div class="alert alert-info mt-3">
                                <i class="fas fa-check-circle me-2"></i>
                                <strong>Bukti telah diunggah</strong><br>
                                <small>Bukti pendukung Anda telah diterima.</small>
                            </div>
                        @else
                            <div class="alert alert-light mt-3">
                                <i class="fas fa-info-circle me-2"></i>
                                <small>Anda tidak mengunggah bukti pendukung.</small>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Quick Actions -->
            @if($role === 'employee' && $leave->status === 'pending')
                <div class="card shadow-sm border-0 mt-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-cog me-2"></i>Aksi Lainnya</h6>
                    </div>
                    <div class="card-body">
                        <button type="button" class="btn btn-outline-warning w-100 mb-2"
                                data-bs-toggle="modal" data-bs-target="#editModal">
                            <i class="fas fa-edit me-2"></i>Edit Pengajuan
                        </button>
                        <form action="{{ route('leaves.cancel', $leave->id) }}" method="POST" class="d-inline w-100">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger w-100"
                                    onclick="return confirm('Yakin ingin membatalkan pengajuan cuti ini?')">
                                <i class="fas fa-times me-2"></i>Batalkan Pengajuan
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal Tolak Cuti -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('leaves.reject', $leave->id) }}" method="POST">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="rejectModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i>Tolak Pengajuan Cuti
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <strong>Perhatian:</strong> Penolakan tidak dapat dibatalkan. Pastikan alasan jelas.
                    </div>
                    <p><strong>Karyawan:</strong> {{ $employee['name'] }}</p>
                    <p><strong>Periode Cuti:</strong> {{ $startDate->format('d F Y') }} s/d {{ $endDate->format('d F Y') }}</p>
                    <p><strong>Jenis Cuti:</strong> {{ $leaveTypeName }}</p>
                    
                    @if($leave->proof_url)
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Catatan:</strong> Pengajuan ini memiliki bukti pendukung.
                        </div>
                    @endif

                    <div class="mt-4">
                        <label for="rejection_reason" class="form-label fw-bold">
                            Alasan Penolakan <span class="text-danger">*</span>
                        </label>
                        <textarea name="rejection_reason" id="rejection_reason" class="form-control" rows="6" required
                            placeholder="Tuliskan alasan penolakan secara lengkap dan jelas agar karyawan memahami..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-lg" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Batal
                    </button>
                    <button type="submit" class="btn btn-danger btn-lg">
                        <i class="fas fa-ban me-2"></i>Tolak Pengajuan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Preview Proof -->
<div class="modal fade" id="proofPreviewModal" tabindex="-1" aria-labelledby="proofPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="proofPreviewModalLabel">
                    <i class="fas fa-eye me-2"></i>Preview Bukti Pendukung
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <div id="proofPreviewContent">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3">Memuat gambar...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Tutup
                </button>
                <button type="button" class="btn btn-primary" id="downloadProofBtn">
                    <i class="fas fa-download me-2"></i>Download
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .proof-card {
            transition: all 0.3s;
            border-left: 4px solid #0d6efd;
        }
        .proof-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        #proofPreviewContent img {
            max-width: 100%;
            max-height: 70vh;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
    </style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Auto show & hide toast
        document.querySelectorAll('.toast').forEach(toast => {
            new bootstrap.Toast(toast, { delay: 5000 }).show();
        });

        // Handle proof preview
        window.previewProof = function(proofUrl) {
            const modal = new bootstrap.Modal(document.getElementById('proofPreviewModal'));
            const contentDiv = document.getElementById('proofPreviewContent');
            const downloadBtn = document.getElementById('downloadProofBtn');
            
            // Set download button href
            downloadBtn.onclick = function() {
                window.open(proofUrl, '_blank');
            };
            
            // Clear previous content
            contentDiv.innerHTML = `
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3">Memuat gambar...</p>
            `;
            
            // Check if it's an image
            const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
            const urlExtension = proofUrl.split('.').pop().split('?')[0].toLowerCase();
            
            if (imageExtensions.includes(urlExtension)) {
                // It's an image - load preview
                const img = new Image();
                img.onload = function() {
                    contentDiv.innerHTML = `
                        <img src="${proofUrl}" 
                             alt="Bukti Pendukung" 
                             class="img-fluid"
                             onerror="this.onerror=null; this.src='https://via.placeholder.com/800x600?text=Gagal+Memuat+Gambar';">
                        <div class="mt-3">
                            <a href="${proofUrl}" target="_blank" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-external-link-alt me-1"></i> Buka di Tab Baru
                            </a>
                        </div>
                    `;
                };
                img.onerror = function() {
                    contentDiv.innerHTML = `
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Gagal memuat gambar</strong>
                            <p class="mb-0 mt-2">Silakan buka file di tab baru atau download.</p>
                        </div>
                        <div class="mt-3">
                            <a href="${proofUrl}" target="_blank" class="btn btn-primary">
                                <i class="fas fa-external-link-alt me-2"></i> Buka di Tab Baru
                            </a>
                        </div>
                    `;
                };
                img.src = proofUrl;
            } else {
                // Not an image - show download option
                contentDiv.innerHTML = `
                    <div class="alert alert-info">
                        <i class="fas fa-file me-2"></i>
                        <strong>File tidak dapat dipreview</strong>
                        <p class="mb-0 mt-2">Format file ini tidak mendukung preview. Silakan download untuk melihat.</p>
                    </div>
                    <div class="mt-4">
                        <a href="${proofUrl}" target="_blank" class="btn btn-primary btn-lg">
                            <i class="fas fa-external-link-alt me-2"></i> Buka di Tab Baru
                        </a>
                        <a href="${proofUrl}" download class="btn btn-success btn-lg ms-2">
                            <i class="fas fa-download me-2"></i> Download File
                        </a>
                    </div>
                `;
            }
            
            modal.show();
        };

        // Copy proof URL to clipboard
        window.copyProofUrl = function(proofUrl) {
            navigator.clipboard.writeText(proofUrl).then(() => {
                // Show success message
                const toastHtml = `
                    <div class="toast align-items-center text-bg-success border-0 show" role="alert">
                        <div class="d-flex">
                            <div class="toast-body">
                                <i class="fas fa-check-circle me-2"></i>
                                Link bukti berhasil disalin ke clipboard!
                            </div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                        </div>
                    </div>
                `;
                
                const toastContainer = document.createElement('div');
                toastContainer.className = 'position-fixed top-0 end-0 p-3';
                toastContainer.style.zIndex = '9999';
                toastContainer.innerHTML = toastHtml;
                document.body.appendChild(toastContainer);
                
                // Auto remove after 3 seconds
                setTimeout(() => {
                    toastContainer.remove();
                }, 3000);
            }).catch(err => {
                alert('Gagal menyalin link: ' + err);
            });
        };

        // Auto-close modals on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const modals = document.querySelectorAll('.modal.show');
                modals.forEach(modal => {
                    const modalInstance = bootstrap.Modal.getInstance(modal);
                    if (modalInstance) {
                        modalInstance.hide();
                    }
                });
            }
        });
    });
</script>
@endpush