@extends('layouts.app')

@section('title', 'Add New User')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="bi bi-person-plus text-primary"></i> Add New User
    </h1>
    <a href="{{ route('profile') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back to Users
    </a>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-person"></i> User Information
                </h5>
            </div>

            <div class="card-body">
                <form method="POST" action="{{ route('user.store') }}">
                    @csrf

                    <!-- Name -->
                    <div class="mb-3">
                        <label for="name" class="form-label">
                            Name <span class="text-danger">*</span>
                        </label>
                        <input
                            type="text"
                            name="name"
                            id="name"
                            class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name') }}"
                            required
                            placeholder="contoh: Muhammad Fatih"
                        >
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div class="mb-3">
                        <label for="email" class="form-label">
                            Email <span class="text-danger">*</span>
                        </label>
                        <input
                            type="email"
                            name="email"
                            id="email"
                            class="form-control @error('email') is-invalid @enderror"
                            value="{{ old('email') }}"
                            required
                            placeholder="contoh: muhammadfatih1453@gmail.com"
                        >
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- No telp -->
                    <div class="mb-3">
                        <label for="phone" class="form-label">
                            No Telepon <span class="text-danger">*</span>
                        </label>
                        <input
                            type="number"
                            name="phone"
                            id="phone"
                            class="form-control @error('phone') is-invalid @enderror"
                            value="{{ old('phone') }}"
                            required
                            placeholder="contoh: 0823617627182"
                        >
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div class="mb-3">
                        <label for="password" class="form-label">
                            Password <span class="text-danger">*</span>
                        </label>
                        <input
                            type="password"
                            name="password"
                            id="password"
                            class="form-control @error('password') is-invalid @enderror"
                            required
                        >
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Confirm Password -->
                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label">
                            Confirm Password <span class="text-danger">*</span>
                        </label>
                        <input
                            type="password"
                            name="password_confirmation"
                            id="password_confirmation"
                            class="form-control"
                            required
                        >
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="reset"class="btn btn-secondary">
                            Reset
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Create User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Help Panel -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-info-circle"></i> Panduan
                </h5>
            </div>
            <div class="card-body small">
                <ul>
                    <li>Harap gunakan nama asli.</li>
                    <li>Password minimum 8 characters.</li>
                    <li>Gunakan password yang kuat.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
