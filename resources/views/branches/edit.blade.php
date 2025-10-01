@extends('layouts.app')

@section('title', 'Edit Branch')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="bi bi-pencil text-primary"></i> Edit Branch
    </h1>
    <div class="btn-group">
        <a href="{{ route('branches.show', $branch) }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Details
        </a>
        <a href="{{ route('branches.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-list"></i> All Branches
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-building"></i> Edit Branch: {{ $branch->name }}
        </h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('branches.update', $branch) }}">
            @csrf
            @method('PUT')

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="name" class="form-label">Branch Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="name"
                           class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $branch->name) }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="code" class="form-label">Branch Code <span class="text-danger">*</span></label>
                    <input type="text" name="code" id="code"
                           class="form-control @error('code') is-invalid @enderror"
                           value="{{ old('code', $branch->code) }}" required>
                    @error('code')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="city" class="form-label">City <span class="text-danger">*</span></label>
                    <select name="city" id="city" class="form-select @error('city') is-invalid @enderror" required>
                        <option value="">Select City</option>
                        <option value="Jakarta" {{ old('city', $branch->city) == 'Jakarta' ? 'selected' : '' }}>Jakarta</option>
                        <option value="Surabaya" {{ old('city', $branch->city) == 'Surabaya' ? 'selected' : '' }}>Surabaya</option>
                        <option value="Bandung" {{ old('city', $branch->city) == 'Bandung' ? 'selected' : '' }}>Bandung</option>
                        <option value="Medan" {{ old('city', $branch->city) == 'Medan' ? 'selected' : '' }}>Medan</option>
                        <option value="Semarang" {{ old('city', $branch->city) == 'Semarang' ? 'selected' : '' }}>Semarang</option>
                        <option value="Makassar" {{ old('city', $branch->city) == 'Makassar' ? 'selected' : '' }}>Makassar</option>
                        <option value="Palembang" {{ old('city', $branch->city) == 'Palembang' ? 'selected' : '' }}>Palembang</option>
                        <option value="Batam" {{ old('city', $branch->city) == 'Batam' ? 'selected' : '' }}>Batam</option>
                        <option value="Pekanbaru" {{ old('city', $branch->city) == 'Pekanbaru' ? 'selected' : '' }}>Pekanbaru</option>
                        <option value="Bandar Lampung" {{ old('city', $branch->city) == 'Bandar Lampung' ? 'selected' : '' }}>Bandar Lampung</option>
                        <option value="Yogyakarta" {{ old('city', $branch->city) == 'Yogyakarta' ? 'selected' : '' }}>Yogyakarta</option>
                        <option value="Malang" {{ old('city', $branch->city) == 'Malang' ? 'selected' : '' }}>Malang</option>
                        <option value="Denpasar" {{ old('city', $branch->city) == 'Denpasar' ? 'selected' : '' }}>Denpasar</option>
                        <option value="Balikpapan" {{ old('city', $branch->city) == 'Balikpapan' ? 'selected' : '' }}>Balikpapan</option>
                        <option value="Manado" {{ old('city', $branch->city) == 'Manado' ? 'selected' : '' }}>Manado</option>
                    </select>
                    @error('city')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="region" class="form-label">Region</label>
                    <select name="region" id="region" class="form-select @error('region') is-invalid @enderror">
                        <option value="">Select Region</option>
                        <option value="Jawa" {{ old('region', $branch->region) == 'Jawa' ? 'selected' : '' }}>Jawa</option>
                        <option value="Sumatera" {{ old('region', $branch->region) == 'Sumatera' ? 'selected' : '' }}>Sumatera</option>
                        <option value="Kalimantan" {{ old('region', $branch->region) == 'Kalimantan' ? 'selected' : '' }}>Kalimantan</option>
                        <option value="Sulawesi" {{ old('region', $branch->region) == 'Sulawesi' ? 'selected' : '' }}>Sulawesi</option>
                        <option value="Bali & Nusa Tenggara" {{ old('region', $branch->region) == 'Bali & Nusa Tenggara' ? 'selected' : '' }}>Bali & Nusa Tenggara</option>
                        <option value="Maluku & Papua" {{ old('region', $branch->region) == 'Maluku & Papua' ? 'selected' : '' }}>Maluku & Papua</option>
                    </select>
                    @error('region')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-3">
                <label for="address" class="form-label">Address</label>
                <textarea name="address" id="address" rows="3"
                          class="form-control @error('address') is-invalid @enderror">{{ old('address', $branch->address) }}</textarea>
                @error('address')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-section">
                <h6 class="mb-3">Contact Information</h6>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="text" name="phone" id="phone"
                               class="form-control @error('phone') is-invalid @enderror"
                               value="{{ old('phone', $branch->contact_info['phone'] ?? '') }}">
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" name="email" id="email"
                               class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email', $branch->contact_info['email'] ?? '') }}">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="manager" class="form-label">Branch Manager</label>
                        <input type="text" name="manager" id="manager"
                               class="form-control @error('manager') is-invalid @enderror"
                               value="{{ old('manager', $branch->contact_info['manager'] ?? '') }}">
                        @error('manager')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="operating_hours" class="form-label">Operating Hours</label>
                        <input type="text" name="operating_hours" id="operating_hours"
                               class="form-control @error('operating_hours') is-invalid @enderror"
                               value="{{ old('operating_hours', $branch->contact_info['operating_hours'] ?? '') }}">
                        @error('operating_hours')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                       {{ old('is_active', $branch->is_active) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active">
                    Branch is Active
                </label>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('branches.show', $branch) }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Update Branch
                </button>
            </div>
        </form>
    </div>
</div>

@endsection
