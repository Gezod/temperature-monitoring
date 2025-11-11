@extends('layouts.app')

@section('title', 'Add New Machine')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="bi bi-plus-circle text-primary"></i> Add New Machine
    </h1>
    <a href="{{ route('machines.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back to Machines
    </a>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-cpu"></i> Machine Information
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('machines.store') }}">
                    @csrf

                    <!-- Basic Information -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="branch_id" class="form-label">Branch <span class="text-danger">*</span></label>
                            <select name="branch_id" id="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required>
                                <option value="">Select Branch</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }} ({{ $branch->city }})
                                    </option>
                                @endforeach
                            </select>
                            @error('branch_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="name" class="form-label">Machine Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name') }}" required
                                   placeholder="e.g., Chiller Unit A1, Compressor Main">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="type" class="form-label">Machine Type <span class="text-danger">*</span></label>
                            <select name="type" id="type" class="form-select @error('type') is-invalid @enderror" required>
                                <option value="">Select Type</option>
                                <option value="Chiller" {{ old('type') == 'Chiller' ? 'selected' : '' }}>Chiller</option>
                                <option value="Compressor" {{ old('type') == 'Compressor' ? 'selected' : '' }}>Compressor</option>
                                <option value="Freezer" {{ old('type') == 'Freezer' ? 'selected' : '' }}>Freezer</option>
                                <option value="Cooler" {{ old('type') == 'Cooler' ? 'selected' : '' }}>Cooler</option>
                                <option value="HVAC" {{ old('type') == 'HVAC' ? 'selected' : '' }}>HVAC</option>
                                <option value="Other" {{ old('type') == 'Other' ? 'selected' : '' }}>Other</option>
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="model" class="form-label">Model</label>
                            <input type="text" name="model" id="model"
                                   class="form-control @error('model') is-invalid @enderror"
                                   value="{{ old('model') }}"
                                   placeholder="e.g., XYZ-2000">
                            @error('model')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="serial_number" class="form-label">Serial Number</label>
                            <input type="text" name="serial_number" id="serial_number"
                                   class="form-control @error('serial_number') is-invalid @enderror"
                                   value="{{ old('serial_number') }}"
                                   placeholder="e.g., SN123456789">
                            @error('serial_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="installation_date" class="form-label">Installation Date</label>
                            <input type="date" name="installation_date" id="installation_date"
                                   class="form-control @error('installation_date') is-invalid @enderror"
                                   value="{{ old('installation_date') }}">
                            @error('installation_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" checked>
                                <label class="form-check-label" for="is_active">
                                    Machine is Active
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Temperature Ranges -->
                    <div class="card bg-light mb-4">
                        <div class="card-body">
                            <h6 class="card-title mb-3">
                                <i class="bi bi-thermometer-half"></i> Temperature Ranges (°C)
                            </h6>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label text-success">Normal Operating Range</label>
                                    <div class="row">
                                        <div class="col-6">
                                            <label for="temp_min_normal" class="form-label">Min <span class="text-danger">*</span></label>
                                            <input type="number" name="temp_min_normal" id="temp_min_normal" step="0.1"
                                                   class="form-control @error('temp_min_normal') is-invalid @enderror"
                                                   value="{{ old('temp_min_normal', -20) }}" required>
                                            @error('temp_min_normal')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-6">
                                            <label for="temp_max_normal" class="form-label">Max <span class="text-danger">*</span></label>
                                            <input type="number" name="temp_max_normal" id="temp_max_normal" step="0.1"
                                                   class="form-control @error('temp_max_normal') is-invalid @enderror"
                                                   value="{{ old('temp_max_normal', 5) }}" required>
                                            @error('temp_max_normal')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label text-danger">Critical Range</label>
                                    <div class="row">
                                        <div class="col-6">
                                            <label for="temp_critical_min" class="form-label">Min <span class="text-danger">*</span></label>
                                            <input type="number" name="temp_critical_min" id="temp_critical_min" step="0.1"
                                                   class="form-control @error('temp_critical_min') is-invalid @enderror"
                                                   value="{{ old('temp_critical_min', -25) }}" required>
                                            @error('temp_critical_min')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-6">
                                            <label for="temp_critical_max" class="form-label">Max <span class="text-danger">*</span></label>
                                            <input type="number" name="temp_critical_max" id="temp_critical_max" step="0.1"
                                                   class="form-control @error('temp_critical_max') is-invalid @enderror"
                                                   value="{{ old('temp_critical_max', 10) }}" required>
                                            @error('temp_critical_max')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i>
                                <strong>Temperature Range Guidelines:</strong>
                                <ul class="mb-0 mt-2">
                                    <li>Normal range: Optimal operating temperatures</li>
                                    <li>Critical range: Temperatures that require immediate attention</li>
                                    <li>System will generate alerts when temperatures exceed normal range</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Optional Specifications -->
                    <div class="mb-4">
                        <label for="specifications" class="form-label">Technical Specifications (Optional)</label>
                        <textarea name="specifications" id="specifications" rows="3"
                                  class="form-control @error('specifications') is-invalid @enderror"
                                  placeholder='{"power": "5kW", "capacity": "100L", "voltage": "220V", "refrigerant": "R134a"}'>{{ old('specifications') }}</textarea>
                        @error('specifications')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Enter specifications in JSON format (optional)</div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('machines.index') }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Create Machine
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
                    <i class="bi bi-question-circle"></i> Help & Guidelines
                </h5>
            </div>
            <div class="card-body">
                <h6>Machine Types</h6>
                <ul class="small">
                    <li><strong>Chiller:</strong> Cooling systems for industrial use</li>
                    <li><strong>Compressor:</strong> Air or refrigeration compressors</li>
                    <li><strong>Freezer:</strong> Commercial freezing units</li>
                    <li><strong>Cooler:</strong> Cooling storage units</li>
                    <li><strong>HVAC:</strong> Heating, ventilation, and air conditioning</li>
                </ul>

                <hr>

                <h6>Temperature Settings</h6>
                <div class="small">
                    <p><strong>Normal Range:</strong> Set the optimal operating temperature range for this machine type.</p>
                    <p><strong>Critical Range:</strong> Set the absolute minimum and maximum temperatures before critical alerts are triggered.</p>
                </div>

                <hr>

                <h6>Common Ranges by Type</h6>
                <div class="small">
                    <ul>
                        <li><strong>Chiller:</strong> -10°C to 5°C</li>
                        <li><strong>Freezer:</strong> -25°C to -15°C</li>
                        <li><strong>Cooler:</strong> 0°C to 8°C</li>
                        <li><strong>HVAC:</strong> 18°C to 25°C</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-lightbulb"></i> Quick Tips
                </h5>
            </div>
            <div class="card-body">
                <ul class="small">
                    <li>Choose descriptive names for easy identification</li>
                    <li>Serial numbers help with maintenance tracking</li>
                    <li>Set realistic temperature ranges based on equipment specs</li>
                    <li>Installation date helps with predictive maintenance</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// JSON validation for specifications
document.getElementById('specifications').addEventListener('blur', function() {
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
    } else {
        this.classList.remove('is-valid', 'is-invalid');
    }
});

// Auto-set temperature ranges based on machine type
document.getElementById('type').addEventListener('change', function() {
    const type = this.value;
    const ranges = {
        'Chiller': { min_normal: -10, max_normal: 5, min_critical: -15, max_critical: 10 },
        'Freezer': { min_normal: -25, max_normal: -15, min_critical: -30, max_critical: -10 },
        'Cooler': { min_normal: 0, max_normal: 8, min_critical: -5, max_critical: 15 },
        'HVAC': { min_normal: 18, max_normal: 25, min_critical: 10, max_critical: 35 },
        'Compressor': { min_normal: -20, max_normal: 5, min_critical: -25, max_critical: 10 }
    };

    if (ranges[type]) {
        document.getElementById('temp_min_normal').value = ranges[type].min_normal;
        document.getElementById('temp_max_normal').value = ranges[type].max_normal;
        document.getElementById('temp_critical_min').value = ranges[type].min_critical;
        document.getElementById('temp_critical_max').value = ranges[type].max_critical;
    }
});

// Validate temperature ranges
function validateTemperatureRanges() {
    const minNormal = parseFloat(document.getElementById('temp_min_normal').value);
    const maxNormal = parseFloat(document.getElementById('temp_max_normal').value);
    const minCritical = parseFloat(document.getElementById('temp_critical_min').value);
    const maxCritical = parseFloat(document.getElementById('temp_critical_max').value);

    let isValid = true;

    if (minNormal >= maxNormal) {
        alert('Normal minimum temperature must be less than normal maximum temperature.');
        isValid = false;
    }

    if (minCritical >= maxCritical) {
        alert('Critical minimum temperature must be less than critical maximum temperature.');
        isValid = false;
    }

    if (minCritical > minNormal) {
        alert('Critical minimum should be lower than normal minimum.');
        isValid = false;
    }

    if (maxCritical < maxNormal) {
        alert('Critical maximum should be higher than normal maximum.');
        isValid = false;
    }

    return isValid;
}

// Form validation on submit
document.querySelector('form').addEventListener('submit', function(e) {
    if (!validateTemperatureRanges()) {
        e.preventDefault();
        return false;
    }
});
</script>
@endpush
