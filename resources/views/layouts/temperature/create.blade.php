@extends('layouts.app')

@section('title', 'Add Temperature Reading')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="bi bi-plus-circle text-primary"></i> Add Temperature Reading
    </h1>
    <a href="{{ route('temperature.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back to List
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-thermometer"></i> Manual Temperature Entry
        </h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('temperature.store') }}">
            @csrf

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="machine_id" class="form-label">Machine <span class="text-danger">*</span></label>
                    <select name="machine_id" id="machine_id" class="form-select @error('machine_id') is-invalid @enderror" required>
                        <option value="">Select Machine</option>
                        @foreach($machines->groupBy('branch.name') as $branchName => $branchMachines)
                            <optgroup label="{{ $branchName }}">
                                @foreach($branchMachines as $machine)
                                    <option value="{{ $machine->id }}" {{ old('machine_id') == $machine->id ? 'selected' : '' }}>
                                        {{ $machine->name }} ({{ $machine->type }})
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    @error('machine_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="recorded_at" class="form-label">Date & Time <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="recorded_at" id="recorded_at"
                           class="form-control @error('recorded_at') is-invalid @enderror"
                           value="{{ old('recorded_at', now()->format('Y-m-d\TH:i')) }}" required>
                    @error('recorded_at')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="temperature" class="form-label">Temperature (°C) <span class="text-danger">*</span></label>
                    <input type="number" name="temperature" id="temperature" step="0.1"
                           class="form-control @error('temperature') is-invalid @enderror"
                           value="{{ old('temperature') }}" required>
                    @error('temperature')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="reading_type" class="form-label">Reading Type</label>
                    <select name="reading_type" id="reading_type" class="form-select">
                        <option value="manual" {{ old('reading_type') == 'manual' ? 'selected' : '' }}>Manual</option>
                        <option value="automatic" {{ old('reading_type') == 'automatic' ? 'selected' : '' }}>Automatic</option>
                        <option value="imported" {{ old('reading_type') == 'imported' ? 'selected' : '' }}>Imported</option>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label for="metadata" class="form-label">Additional Notes (Optional)</label>
                <textarea name="metadata" id="metadata" rows="3" class="form-control"
                          placeholder="Any additional information about this reading...">{{ old('metadata') }}</textarea>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('temperature.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Save Reading
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Machine Info Card -->
<div class="card mt-4" id="machineInfo" style="display: none;">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-info-circle"></i> Machine Information
        </h5>
    </div>
    <div class="card-body" id="machineInfoContent">
        <!-- Machine details will be loaded here -->
    </div>
</div>

@endsection

@push('scripts')
<script>
document.getElementById('machine_id').addEventListener('change', function() {
    const machineId = this.value;
    const machineInfo = document.getElementById('machineInfo');
    const machineInfoContent = document.getElementById('machineInfoContent');

    if (machineId) {
        // Show machine information
        fetch(`/api/machines/${machineId}/info`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const machine = data.machine;
                    machineInfoContent.innerHTML = `
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Branch:</strong> ${machine.branch.name}</p>
                                <p><strong>Type:</strong> ${machine.type}</p>
                                <p><strong>Model:</strong> ${machine.model || 'N/A'}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Normal Range:</strong> ${machine.temp_min_normal}°C to ${machine.temp_max_normal}°C</p>
                                <p><strong>Critical Range:</strong> ${machine.temp_critical_min}°C to ${machine.temp_critical_max}°C</p>
                                <p><strong>Status:</strong> <span class="badge bg-${machine.is_active ? 'success' : 'secondary'}">${machine.is_active ? 'Active' : 'Inactive'}</span></p>
                            </div>
                        </div>
                    `;
                    machineInfo.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error fetching machine info:', error);
            });
    } else {
        machineInfo.style.display = 'none';
    }
});

// Temperature validation based on selected machine
document.getElementById('temperature').addEventListener('input', function() {
    const temperature = parseFloat(this.value);
    const machineId = document.getElementById('machine_id').value;

    if (machineId && !isNaN(temperature)) {
        // You can add real-time validation here
        // For now, just visual feedback
        this.classList.remove('is-valid', 'is-invalid');

        // This would need machine data to validate properly
        // For demo purposes, just mark as valid if within reasonable range
        if (temperature >= -50 && temperature <= 50) {
            this.classList.add('is-valid');
        } else {
            this.classList.add('is-invalid');
        }
    }
});
</script>
@endpush
