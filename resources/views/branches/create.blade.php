@extends('layouts.app')

@section('title', 'Add New Branch')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="bi bi-plus-circle text-primary"></i> Add New Branch
    </h1>
    <a href="{{ route('branches.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back to Branches
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-building"></i> Branch Information
        </h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('branches.store') }}">
            @csrf

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="name" class="form-label">Branch Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="name"
                           class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name') }}" required
                           placeholder="e.g., Jakarta Pusat, Surabaya Timur">
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="code" class="form-label">Branch Code <span class="text-danger">*</span></label>
                    <input type="text" name="code" id="code"
                           class="form-control @error('code') is-invalid @enderror"
                           value="{{ old('code') }}" required
                           placeholder="e.g., JKT-01, SBY-02">
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
                        <option value="Jakarta" {{ old('city') == 'Jakarta' ? 'selected' : '' }}>Jakarta</option>
                        <option value="Surabaya" {{ old('city') == 'Surabaya' ? 'selected' : '' }}>Surabaya</option>
                        <option value="Bandung" {{ old('city') == 'Bandung' ? 'selected' : '' }}>Bandung</option>
                        <option value="Medan" {{ old('city') == 'Medan' ? 'selected' : '' }}>Medan</option>
                        <option value="Semarang" {{ old('city') == 'Semarang' ? 'selected' : '' }}>Semarang</option>
                        <option value="Makassar" {{ old('city') == 'Makassar' ? 'selected' : '' }}>Makassar</option>
                        <option value="Palembang" {{ old('city') == 'Palembang' ? 'selected' : '' }}>Palembang</option>
                        <option value="Batam" {{ old('city') == 'Batam' ? 'selected' : '' }}>Batam</option>
                        <option value="Pekanbaru" {{ old('city') == 'Pekanbaru' ? 'selected' : '' }}>Pekanbaru</option>
                        <option value="Bandar Lampung" {{ old('city') == 'Bandar Lampung' ? 'selected' : '' }}>Bandar Lampung</option>
                        <option value="Yogyakarta" {{ old('city') == 'Yogyakarta' ? 'selected' : '' }}>Yogyakarta</option>
                        <option value="Malang" {{ old('city') == 'Malang' ? 'selected' : '' }}>Malang</option>
                        <option value="Denpasar" {{ old('city') == 'Denpasar' ? 'selected' : '' }}>Denpasar</option>
                        <option value="Balikpapan" {{ old('city') == 'Balikpapan' ? 'selected' : '' }}>Balikpapan</option>
                        <option value="Manado" {{ old('city') == 'Manado' ? 'selected' : '' }}>Manado</option>
                    </select>
                    @error('city')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="region" class="form-label">Region</label>
                    <select name="region" id="region" class="form-select @error('region') is-invalid @enderror">
                        <option value="">Select Region</option>
                        <option value="Jawa" {{ old('region') == 'Jawa' ? 'selected' : '' }}>Jawa</option>
                        <option value="Sumatera" {{ old('region') == 'Sumatera' ? 'selected' : '' }}>Sumatera</option>
                        <option value="Kalimantan" {{ old('region') == 'Kalimantan' ? 'selected' : '' }}>Kalimantan</option>
                        <option value="Sulawesi" {{ old('region') == 'Sulawesi' ? 'selected' : '' }}>Sulawesi</option>
                        <option value="Bali & Nusa Tenggara" {{ old('region') == 'Bali & Nusa Tenggara' ? 'selected' : '' }}>Bali & Nusa Tenggara</option>
                        <option value="Maluku & Papua" {{ old('region') == 'Maluku & Papua' ? 'selected' : '' }}>Maluku & Papua</option>
                    </select>
                    @error('region')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-3">
                <label for="address" class="form-label">Address</label>
                <textarea name="address" id="address" rows="3"
                          class="form-control @error('address') is-invalid @enderror"
                          placeholder="Complete branch address">{{ old('address') }}</textarea>
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
                               value="{{ old('phone') }}"
                               placeholder="e.g., +62 21 1234567">
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" name="email" id="email"
                               class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email') }}"
                               placeholder="e.g., branch@company.com">
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
                               value="{{ old('manager') }}"
                               placeholder="Manager name">
                        @error('manager')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="operating_hours" class="form-label">Operating Hours</label>
                        <input type="text" name="operating_hours" id="operating_hours"
                               class="form-control @error('operating_hours') is-invalid @enderror"
                               value="{{ old('operating_hours') }}"
                               placeholder="e.g., 08:00 - 17:00">
                        @error('operating_hours')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('branches.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Create Branch
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Auto-generate branch code based on city
document.getElementById('city').addEventListener('change', function() {
    const city = this.value;
    const codeInput = document.getElementById('code');

    if (city && !codeInput.value) {
        let code = '';
        switch(city) {
            case 'Jakarta': code = 'JKT-'; break;
            case 'Surabaya': code = 'SBY-'; break;
            case 'Bandung': code = 'BDG-'; break;
            case 'Medan': code = 'MDN-'; break;
            case 'Semarang': code = 'SMG-'; break;
            case 'Makassar': code = 'MKS-'; break;
            case 'Palembang': code = 'PLB-'; break;
            case 'Batam': code = 'BTM-'; break;
            case 'Pekanbaru': code = 'PKB-'; break;
            case 'Bandar Lampung': code = 'BDL-'; break;
            case 'Yogyakarta': code = 'YGY-'; break;
            case 'Malang': code = 'MLG-'; break;
            case 'Denpasar': code = 'DPS-'; break;
            case 'Balikpapan': code = 'BPP-'; break;
            case 'Manado': code = 'MND-'; break;
            default: code = city.substring(0, 3).toUpperCase() + '-';
        }
        codeInput.value = code + '01';
    }
});
</script>
@endpush
