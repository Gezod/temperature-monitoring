@extends('layouts.app')

@section('title', 'Create Anomaly')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="bi bi-plus-circle text-primary"></i> Create New Anomaly
    </h1>
    <a href="{{ route('anomalies.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back to List
    </a>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-exclamation-triangle"></i> Anomaly Information
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('anomalies.store') }}">
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
                            <label for="temperature_reading_id" class="form-label">Temperature Reading (Optional)</label>
                            <select name="temperature_reading_id" id="temperature_reading_id" class="form-select">
                                <option value="">Select Temperature Reading</option>
                                <!-- Will be populated via AJAX when machine is selected -->
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="type" class="form-label">Anomaly Type <span class="text-danger">*</span></label>
                            <select name="type" id="type" class="form-select @error('type') is-invalid @enderror" required>
                                <option value="">Select Type</option>
                                <option value="temperature_high" {{ old('type') == 'temperature_high' ? 'selected' : '' }}>Temperature Too High</option>
                                <option value="temperature_low" {{ old('type') == 'temperature_low' ? 'selected' : '' }}>Temperature Too Low</option>
                                <option value="rapid_change" {{ old('type') == 'rapid_change' ? 'selected' : '' }}>Rapid Temperature Change</option>
                                <option value="pattern_deviation" {{ old('type') == 'pattern_deviation' ? 'selected' : '' }}>Pattern Deviation</option>
                                <option value="consecutive_abnormal" {{ old('type') == 'consecutive_abnormal' ? 'selected' : '' }}>Consecutive Abnormal Readings</option>
                                <option value="sensor_error" {{ old('type') == 'sensor_error' ? 'selected' : '' }}>Sensor Error</option>
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="severity" class="form-label">Severity <span class="text-danger">*</span></label>
                            <select name="severity" id="severity" class="form-select @error('severity') is-invalid @enderror" required>
                                <option value="">Select Severity</option>
                                <option value="low" {{ old('severity') == 'low' ? 'selected' : '' }}>Low</option>
                                <option value="medium" {{ old('severity') == 'medium' ? 'selected' : '' }}>Medium</option>
                                <option value="high" {{ old('severity') == 'high' ? 'selected' : '' }}>High</option>
                                <option value="critical" {{ old('severity') == 'critical' ? 'selected' : '' }}>Critical</option>
                            </select>
                            @error('severity')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="detected_at" class="form-label">Detected At <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="detected_at" id="detected_at"
                               class="form-control @error('detected_at') is-invalid @enderror"
                               value="{{ old('detected_at', now()->format('Y-m-d\TH:i')) }}" required>
                        @error('detected_at')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea name="description" id="description" rows="3"
                                  class="form-control @error('description') is-invalid @enderror" required
                                  placeholder="Describe the anomaly detected...">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="possible_causes" class="form-label">Possible Causes</label>
                        <textarea name="possible_causes" id="possible_causes" rows="3"
                                  class="form-control"
                                  placeholder="List possible causes for this anomaly...">{{ old('possible_causes') }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label for="recommendations" class="form-label">Recommendations</label>
                        <textarea name="recommendations" id="recommendations" rows="3"
                                  class="form-control"
                                  placeholder="Recommended actions to resolve this anomaly...">{{ old('recommendations') }}</textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('anomalies.index') }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Create Anomaly
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Help Panel -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-question-circle"></i> Anomaly Types
                </h5>
            </div>
            <div class="card-body">
                <div class="small">
                    <p><strong>Temperature High/Low:</strong> Temperature readings outside normal operating range.</p>
                    <p><strong>Rapid Change:</strong> Sudden temperature changes in short time periods.</p>
                    <p><strong>Pattern Deviation:</strong> Temperature patterns that deviate from historical norms.</p>
                    <p><strong>Consecutive Abnormal:</strong> Multiple consecutive readings outside normal range.</p>
                    <p><strong>Sensor Error:</strong> Suspected sensor malfunction or invalid readings.</p>
                </div>
            </div>
        </div>

        <!-- Severity Guide -->
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-exclamation-triangle"></i> Severity Levels
                </h5>
            </div>
            <div class="card-body">
                <div class="small">
                    <p><span class="badge bg-success">Low</span> Minor deviations, monitoring recommended.</p>
                    <p><span class="badge bg-warning">Medium</span> Notable issues, investigation needed.</p>
                    <p><span class="badge bg-danger">High</span> Significant problems, prompt action required.</p>
                    <p><span class="badge bg-dark">Critical</span> Immediate attention needed, potential equipment damage.</p>
                </div>
            </div>
        </div>

        <!-- Machine Info -->
        <div class="card mt-3" id="machineInfoCard" style="display: none;">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-cpu"></i> Machine Information
                </h5>
            </div>
            <div class="card-body" id="machineInfoContent">
                <!-- Will be populated when machine is selected -->
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Auto-populate fields based on anomaly type
document.getElementById('type').addEventListener('change', function() {
    const type = this.value;
    const descriptionField = document.getElementById('description');
    const causesField = document.getElementById('possible_causes');
    const recommendationsField = document.getElementById('recommendations');

    const templates = {
        'temperature_high': {
            description: 'Temperature reading above normal operating range detected.',
            causes: 'Insufficient cooling capacity, blocked air vents, refrigerant issues, high ambient temperature, equipment malfunction.',
            recommendations: 'Check cooling system operation, clean air filters, verify refrigerant levels, inspect for blockages.'
        },
        'temperature_low': {
            description: 'Temperature reading below normal operating range detected.',
            causes: 'Excessive cooling, refrigerant leak, faulty temperature sensor, environmental factors.',
            recommendations: 'Check refrigerant levels, inspect insulation, verify sensor calibration, review cooling settings.'
        },
        'rapid_change': {
            description: 'Rapid temperature change detected in short time period.',
            causes: 'Door left open, equipment malfunction, power fluctuations, sensor error.',
            recommendations: 'Check door seals, inspect sensors, verify power supply stability, review maintenance activities.'
        },
        'pattern_deviation': {
            description: 'Temperature pattern deviates significantly from historical norms.',
            causes: 'Gradual equipment degradation, seasonal changes, operational changes, maintenance needed.',
            recommendations: 'Schedule preventive maintenance, review operational procedures, monitor trends closely.'
        },
        'consecutive_abnormal': {
            description: 'Multiple consecutive temperature readings outside normal range.',
            causes: 'Equipment malfunction, sensor drift, environmental changes, system failure.',
            recommendations: 'Immediate inspection required, check equipment status, verify sensor calibration.'
        },
        'sensor_error': {
            description: 'Suspected temperature sensor malfunction or invalid readings.',
            causes: 'Sensor failure, wiring issues, calibration drift, physical damage.',
            recommendations: 'Inspect sensor and wiring, perform calibration check, consider sensor replacement.'
        }
    };

    if (templates[type]) {
        if (!descriptionField.value) descriptionField.value = templates[type].description;
        if (!causesField.value) causesField.value = templates[type].causes;
        if (!recommendationsField.value) recommendationsField.value = templates[type].recommendations;
    }
});

// Load machine information when selected
document.getElementById('machine_id').addEventListener('change', function() {
    const machineId = this.value;
    const machineInfoCard = document.getElementById('machineInfoCard');
    const machineInfoContent = document.getElementById('machineInfoContent');
    const temperatureSelect = document.getElementById('temperature_reading_id');

    if (machineId) {
        // Show machine info
        fetch(`/api/machines/${machineId}/info`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const machine = data.machine;
                    machineInfoContent.innerHTML = `
                        <table class="table table-sm table-borderless">
                            <tr><td><strong>Branch:</strong></td><td>${machine.branch.name}</td></tr>
                            <tr><td><strong>Type:</strong></td><td>${machine.type}</td></tr>
                            <tr><td><strong>Normal Range:</strong></td><td>${machine.temp_min_normal}°C to ${machine.temp_max_normal}°C</td></tr>
                            <tr><td><strong>Critical Range:</strong></td><td>${machine.temp_critical_min}°C to ${machine.temp_critical_max}°C</td></tr>
                        </table>
                    `;
                    machineInfoCard.style.display = 'block';
                }
            });

        // Load recent temperature readings for this machine
        fetch(`/api/temperature-readings/${machineId}`)
            .then(response => response.json())
            .then(data => {
                temperatureSelect.innerHTML = '<option value="">Select Temperature Reading</option>';
                data.forEach(reading => {
                    const option = document.createElement('option');
                    option.value = reading.id;
                    option.textContent = `${reading.temperature}°C - ${new Date(reading.timestamp).toLocaleString()}`;
                    temperatureSelect.appendChild(option);
                });
            })
            .catch(error => console.error('Error loading temperature readings:', error));
    } else {
        machineInfoCard.style.display = 'none';
        temperatureSelect.innerHTML = '<option value="">Select Temperature Reading</option>';
    }
});
</script>
@endpush
