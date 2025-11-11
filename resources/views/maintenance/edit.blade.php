@extends('layouts.app')

@section('title', 'Edit Machine')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="bi bi-pencil text-primary"></i> Edit Machine
    </h1>
    <div class="btn-group">
        <a href="{{ route('machines.show', $machine) }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Details
        </a>
        <a href="{{ route('machines.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-list"></i> All Machines
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-cpu"></i> Edit Machine: {{ $machine->name }}
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('machines.update', $machine) }}">
                    @csrf
                    @method('PUT')

                    <!-- Basic Information -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="branch_id" class="form-label">Branch <span class="text-danger">*</span></label>
                            <select name="branch_id" id="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required>
                                <option value="">Select Branch</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}"
                                        {{ (old('branch_id', $machine->branch_id) == $branch->id) ? 'selected' : '' }}>
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
                                   value="{{ old('name', $machine->name) }}" required>
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
                                <option value="Chiller" {{ old('type', $machine->type) == 'Chiller' ? 'selected' : '' }}>Chiller</option>
                                <option value="Compressor" {{ old('type', $machine->type) == 'Compressor' ? 'selected' : '' }}>Compressor</option>
                                <option value="Freezer" {{ old('type', $machine->type) == 'Freezer' ? 'selected' : '' }}>Freezer</option>
                                <option value="Cooler" {{ old('type', $machine->type) == 'Cooler' ? 'selected' : '' }}>Cooler</option>
                                <option value="HVAC" {{ old('type', $machine->type) == 'HVAC' ? 'selected' : '' }}>HVAC</option>
                                <option value="Other" {{ old('type', $machine->type) == 'Other' ? 'selected' : '' }}>Other</option>
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="model" class="form-label">Model</label>
                            <input type="text" name="model" id="model"
                                   class="form-control @error('model') is-invalid @enderror"
                                   value="{{ old('model', $machine->model) }}">
                            @error('model')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="serial_number" class="form-label">Serial Number</label>
                            <input type="text" name="serial_number" id="serial_number"
                                   class="form-control @error('serial_number') is-invalid @enderror"
                                   value="{{ old('serial_number', $machine->serial_number) }}">
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
                                   value="{{ old('installation_date', $machine->installation_date?->format('Y-m-d')) }}">
                            @error('installation_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                                       {{ old('is_active', $machine->is_active) ? 'checked' : '' }}>
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
                                                   value="{{ old('temp_min_normal', $machine->temp_min_normal) }}" required>
                                            @error('temp_min_normal')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-6">
                                            <label for="temp_max_normal" class="form-label">Max <span class="text-danger">*</span></label>
                                            <input type="number" name="temp_max_normal" id="temp_max_normal" step="0.1"
                                                   class="form-control @error('temp_max_normal') is-invalid @enderror"
                                                   value="{{ old('temp_max_normal', $machine->temp_max_normal) }}" required>
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
                                                   value="{{ old('temp_critical_min', $machine->temp_critical_min) }}" required>
                                            @error('temp_critical_min')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-6">
                                            <label for="temp_critical_max" class="form-label">Max <span class="text-danger">*</span></label>
                                            <input type="number" name="temp_critical_max" id="temp_critical_max" step="0.1"
                                                   class="form-control @error('temp_critical_max') is-invalid @enderror"
                                                   value="{{ old('temp_critical_max', $machine->temp_critical_max) }}" required>
                                            @error('temp_critical_max')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Visual Range Indicator -->
                            <div class="mt-3">
                                <label class="form-label">Temperature Range Visualization</label>
                                <div class="progress" style="height: 30px;">
                                    <div class="progress-bar bg-danger" style="width: 20%;" title="Critical Low"></div>
                                    <div class="progress-bar bg-success" style="width: 60%;" title="Normal Range"></div>
                                    <div class="progress-bar bg-danger" style="width: 20%;" title="Critical High"></div>
                                </div>
                                <div class="d-flex justify-content-between mt-1">
                                    <small class="text-danger">Critical Low</small>
                                    <small class="text-success">Normal Range</small>
                                    <small class="text-danger">Critical High</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Optional Specifications -->
                    <div class="mb-4">
                        <label for="specifications" class="form-label">Technical Specifications (Optional)</label>
                        <textarea name="specifications" id="specifications" rows="4"
                                  class="form-control @error('specifications') is-invalid @enderror">{{ old('specifications', $machine->specifications ? json_encode($machine->specifications, JSON_PRETTY_PRINT) : '') }}</textarea>
                        @error('specifications')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Enter specifications in JSON format (optional)</div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('machines.show', $machine) }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Update Machine
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Machine Info Panel -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-info-circle"></i> Current Machine Info
                </h5>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td><strong>Current Status:</strong></td>
                        <td>
                            <span class="badge bg-{{ $machine->is_active ? 'success' : 'secondary' }}">
                                {{ $machine->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Branch:</strong></td>
                        <td>{{ $machine->branch->name }}</td>
                    </tr>
                    <tr>
                        <td><strong>Type:</strong></td>
                        <td>{{ $machine->type }}</td>
                    </tr>
                    <tr>
                        <td><strong>Installation:</strong></td>
                        <td>{{ $machine->installation_date?->format('d M Y') ?? 'Not set' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Active Anomalies:</strong></td>
                        <td>
                            <span class="badge bg-{{ $machine->active_anomalies_count > 0 ? 'warning' : 'success' }}">
                                {{ $machine->active_anomalies_count }}
                            </span>
                        </td>
                    </tr>
                    @if($machine->latest_temperature)
                    <tr>
                        <td><strong>Last Reading:</strong></td>
                        <td>
                            {{ number_format($machine->latest_temperature->temperature, 1) }}°C<br>
                            <small class="text-muted">{{ $machine->latest_temperature->recorded_at->diffForHumans() }}</small>
                        </td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-lightning"></i> Quick Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('machines.temperature-history', $machine) }}" class="btn btn-outline-primary">
                        <i class="bi bi-graph-up"></i> View Temperature History
                    </a>
                    <a href="{{ route('machines.maintenance-history', $machine) }}" class="btn btn-outline-info">
                        <i class="bi bi-tools"></i> Maintenance History
                    </a>
                    <button class="btn btn-outline-warning" onclick="runAnomalyCheck({{ $machine->id }})">
                        <i class="bi bi-search"></i> Run Anomaly Check
                    </button>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-exclamation-triangle"></i> Important Notes
                </h5>
            </div>
            <div class="card-body">
                <ul class="small">
                    <li>Changing temperature ranges will affect future anomaly detection</li>
                    <li>Deactivating a machine will stop temperature monitoring</li>
                    <li>Moving to different branch may require reconfiguration</li>
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

// Auto-set temperature ranges based on machine type (if current ranges are default)
document.getElementById('type').addEventListener('change', function() {
    const type = this.value;

    // Only auto-set if user confirms
    if (confirm('Do you want to update temperature ranges based on the selected machine type?')) {
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

// Run anomaly check function
function runAnomalyCheck(machineId) {
    if (confirm('Run anomaly check for this machine?')) {
        fetch(`/machines/${machineId}/run-anomaly-check`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error: ' + error.message);
        });
    }
}
</script>
@endpush
