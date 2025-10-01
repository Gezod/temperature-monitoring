@extends('layouts.app')

@section('title', 'Create Maintenance Recommendation')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="bi bi-plus-circle text-primary"></i> Create Maintenance Recommendation
    </h1>
    <a href="{{ route('maintenance.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back to Maintenance
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-tools"></i> Maintenance Recommendation Details
        </h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('maintenance.store') }}">
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
                    <label for="type" class="form-label">Maintenance Type <span class="text-danger">*</span></label>
                    <select name="type" id="type" class="form-select @error('type') is-invalid @enderror" required>
                        <option value="">Select Type</option>
                        <option value="preventive" {{ old('type') == 'preventive' ? 'selected' : '' }}>Preventive</option>
                        <option value="predictive" {{ old('type') == 'predictive' ? 'selected' : '' }}>Predictive</option>
                        <option value="corrective" {{ old('type') == 'corrective' ? 'selected' : '' }}>Corrective</option>
                        <option value="emergency" {{ old('type') == 'emergency' ? 'selected' : '' }}>Emergency</option>
                    </select>
                    @error('type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="priority" class="form-label">Priority <span class="text-danger">*</span></label>
                    <select name="priority" id="priority" class="form-select @error('priority') is-invalid @enderror" required>
                        <option value="">Select Priority</option>
                        <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>Low</option>
                        <option value="medium" {{ old('priority') == 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>High</option>
                        <option value="critical" {{ old('priority') == 'critical' ? 'selected' : '' }}>Critical</option>
                    </select>
                    @error('priority')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="recommended_date" class="form-label">Recommended Date <span class="text-danger">*</span></label>
                    <input type="date" name="recommended_date" id="recommended_date"
                           class="form-control @error('recommended_date') is-invalid @enderror"
                           value="{{ old('recommended_date') }}" required>
                    @error('recommended_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-3">
                <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                <input type="text" name="title" id="title"
                       class="form-control @error('title') is-invalid @enderror"
                       value="{{ old('title') }}" required
                       placeholder="e.g., Replace cooling system filters">
                @error('title')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                <textarea name="description" id="description" rows="4"
                          class="form-control @error('description') is-invalid @enderror"
                          required placeholder="Detailed description of the maintenance work required">{{ old('description') }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="reason" class="form-label">Reason <span class="text-danger">*</span></label>
                <textarea name="reason" id="reason" rows="3"
                          class="form-control @error('reason') is-invalid @enderror"
                          required placeholder="Why is this maintenance needed?">{{ old('reason') }}</textarea>
                @error('reason')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="estimated_duration_hours" class="form-label">Estimated Duration (Hours)</label>
                    <input type="number" name="estimated_duration_hours" id="estimated_duration_hours"
                           class="form-control @error('estimated_duration_hours') is-invalid @enderror"
                           value="{{ old('estimated_duration_hours') }}" min="1"
                           placeholder="e.g., 4">
                    @error('estimated_duration_hours')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="estimated_cost" class="form-label">Estimated Cost (IDR)</label>
                    <input type="number" name="estimated_cost" id="estimated_cost" step="0.01"
                           class="form-control @error('estimated_cost') is-invalid @enderror"
                           value="{{ old('estimated_cost') }}" min="0"
                           placeholder="e.g., 1500000">
                    @error('estimated_cost')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-3">
                <label for="required_parts" class="form-label">Required Parts (JSON)</label>
                <textarea name="required_parts" id="required_parts" rows="4"
                          class="form-control @error('required_parts') is-invalid @enderror"
                          placeholder='[{"name": "Filter", "quantity": 2, "part_number": "F123"}, {"name": "Gasket", "quantity": 1, "part_number": "G456"}]'>{{ old('required_parts') }}</textarea>
                @error('required_parts')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text">
                    Optional: JSON format for required parts list
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('maintenance.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Create Recommendation
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
// JSON validation for required parts
document.getElementById('required_parts').addEventListener('blur', function() {
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

// Auto-set recommended date based on priority
document.getElementById('priority').addEventListener('change', function() {
    const priority = this.value;
    const dateInput = document.getElementById('recommended_date');

    if (priority && !dateInput.value) {
        const today = new Date();
        let recommendedDate = new Date(today);

        switch(priority) {
            case 'critical':
                recommendedDate.setDate(today.getDate() + 1); // Tomorrow
                break;
            case 'high':
                recommendedDate.setDate(today.getDate() + 7); // 1 week
                break;
            case 'medium':
                recommendedDate.setDate(today.getDate() + 30); // 1 month
                break;
            case 'low':
                recommendedDate.setDate(today.getDate() + 90); // 3 months
                break;
        }

        dateInput.value = recommendedDate.toISOString().split('T')[0];
    }
});
</script>
@endpush
