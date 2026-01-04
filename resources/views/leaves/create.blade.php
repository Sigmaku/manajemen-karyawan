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

                    <form action="{{ route('leaves.store') }}" method="POST">
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
                                       value="{{ old('start_date') }}" required>
                                @error('start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="end_date" class="form-label">Tanggal Selesai <span class="text-danger">*</span></label>
                                <input type="date" name="end_date" id="end_date"
                                       class="form-control @error('end_date') is-invalid @enderror"
                                       value="{{ old('end_date') }}" required>
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
                                      placeholder="Jelaskan alasan pengajuan cuti..." required>{{ old('reason') }}</textarea>
                            @error('reason')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Kontak Selama Cuti -->
                        <div class="mb-4">
                            <label for="contact_during_leave" class="form-label">Kontak Selama Cuti <span class="text-danger">*</span></label>
                            <input type="text" name="contact_during_leave" id="contact_during_leave"
                                   class="form-control @error('contact_during_leave') is-invalid @enderror"
                                   value="{{ old('contact_during_leave') }}"
                                   placeholder="Nomor HP / Email yang bisa dihubungi" required>
                            @error('contact_during_leave')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-between mt-5">
                            <a href="{{ route('leaves.index') }}" class="btn btn-outline-secondary">Kembali</a>
                            <button type="submit" class="btn btn-primary">
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
@endpush
