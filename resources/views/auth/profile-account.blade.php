@extends('layouts.app')

@section('title', 'Add New Machine')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="bi bi-plus-circle text-primary"></i> Akun Profile Anda
        </h1>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="bi bi-person-circle me-2"></i> Akun Anda
            </h5>
        </div>

        <div class="card-body">
            <div class="row mb-2">
                <div class="col-4 text-muted">Nama</div>
                <div class="col-8 fw-semibold">
                    {{ auth()->user()->name }}
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-4 text-muted">Email</div>
                <div class="col-8">
                    {{ auth()->user()->email }}
                </div>
            </div>

            <div class="row">
                <div class="col-4 text-muted">Login sejak</div>
                <div class="col-8">
                    {{ auth()->user()->created_at->format('d M Y') }}
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="bi bi-cpu"></i> Edit Akun
            </h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('profile.update') }}">
                @csrf
                @method('PUT')

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Nama</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name', $user->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                            value="{{ old('email', $user->email) }}" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Password Baru</label>
                        <input type="password" name="password"  class="form-control @error('password') is-invalid @enderror"
                            placeholder="Kosongkan jika tidak diganti">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Konfirmasi Password</label>
                        <input type="password" name="password_confirmation" class="form-control">
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="reset" class="btn btn-danger">Reset</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Simpan
                    </button>
                </div>
            </form>

        </div>
    </div>

@endsection

@push('scripts')
    <script>
        // JSON validation for specifications
        document.getElementById('specifications').addEventListener('blur', function () {
            const value = this.value.trim();
            if (value && value !== '') {
                try {
                    JSON.parse(value);
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                } catch (e) {
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                }
            }
        });
    </script>
@endpush