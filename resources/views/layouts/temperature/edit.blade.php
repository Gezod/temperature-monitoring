@extends('layouts.app')

@section('title', 'Edit Temperature Reading')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="bi bi-pencil text-primary"></i> Edit Temperature Reading
    </h1>
    <div class="btn-group">
        <a href="{{ route('temperature.show', $reading) }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Details
        </a>
        <a href="{{ route('temperature.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-list"></i> Back to List
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-thermometer"></i> Edit Temperature Reading
        </h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('temperature.update', $reading) }}">
            @csrf
            @method('PUT')

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="machine_id" class="form-label">Machine <span class="text-danger">*</span></label>
                    <select name="machine_id" id="machine_id" class="form-select @error('machine_id') is-invalid @enderror" required>
                        <option value="">Select Machine</option>
                        @foreach($machines->groupBy('branch.name') as $branchName => $branchMachines)
                            <optgroup label="{{ $branchName }}">
                                @foreach($branchMachines as $machine)
                                    <option value="{{ $machine->id }}"
                                        {{ (old('machine_id', $reading->machine_id) == $machine->id) ? 'selected' : '' }}>
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
                           value="{{ old('recorded_at', $reading->recorded_at->format('Y-m-d\TH:i')) }}" required>
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
                           value="{{ old('temperature', $reading->temperature) }}" required>
                    @error('temperature')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="reading_type" class="form-label">Reading Type</label>
                    <select name="reading_type" id="reading_type" class="form-select">
                        <option value="manual" {{ old('reading_type', $reading->reading_type) == 'manual' ? 'selected' : '' }}>Manual</option>
                        <option value="automatic" {{ old('reading_type', $reading->reading_type) == 'automatic' ? 'selected' : '' }}>Automatic</option>
                        <option value="imported" {{ old('reading_type', $reading->reading_type) == 'imported' ? 'selected' : '' }}>Imported</option>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label for="metadata" class="form-label">Additional Notes (Optional)</label>
                <textarea name="metadata" id="metadata" rows="3" class="form-control"
                          placeholder="Any additional information about this reading...">{{ old('metadata', is_string($reading->metadata) ? $reading->metadata : json_encode($reading->metadata)) }}</textarea>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('temperature.show', $reading) }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Update Reading
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Current Reading Info -->
<div class="card mt-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-info-circle"></i> Current Reading Information
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p><strong>Original Date:</strong> {{ $reading->recorded_at->format('d/m/Y H:i:s') }}</p>
                <p><strong>Original Temperature:</strong> {{ number_format($reading->temperature, 1) }}°C</p>
                <p><strong>Current Status:</strong>
                    <span class="badge bg-{{ $reading->status == 'critical' ? 'danger' : ($reading->status == 'warning' ? 'warning' : 'success') }}">
                        {{ ucfirst($reading->status) }}
                    </span>
                </p>
            </div>
            <div class="col-md-6">
                <p><strong>Machine:</strong> {{ $reading->machine->name }}</p>
                <p><strong>Branch:</strong> {{ $reading->machine->branch->name }}</p>
                <p><strong>Reading Type:</strong> {{ ucfirst($reading->reading_type) }}</p>
            </div>
        </div>

        @if($reading->is_anomaly)
        <div class="alert alert-warning mt-3">
            <i class="bi bi-exclamation-triangle"></i>
            <strong>Note:</strong> This reading is currently flagged as an anomaly. Updating it may change its anomaly status.
        </div>
        @endif
    </div>
</div>

@endsection

@push('scripts')
<script>
// Temperature validation based on selected machine
document.getElementById('temperature').addEventListener('input', function() {
    const temperature = parseFloat(this.value);
    const machineId = document.getElementById('machine_id').value;

    if (machineId && !isNaN(temperature)) {
        this.classList.remove('is-valid', 'is-invalid');

        // Basic validation - you can enhance this with actual machine limits
        if (temperature >= -50 && temperature <= 50) {
            this.classList.add('is-valid');
        } else {
            this.classList.add('is-invalid');
        }
    }
});
</script>
@endpush
