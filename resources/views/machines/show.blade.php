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
                    <input type="text" name="type" id="type"
                           class="form-control @error('type') is-invalid @enderror"
                           value="{{ old('type', $machine->type) }}" required>
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

            <div class="row mb-3">
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
                    <label for="specifications" class="form-label">Specifications (JSON)</label>
                    <textarea name="specifications" id="specifications" rows="3"
                              class="form-control @error('specifications') is-invalid @enderror">{{ old('specifications', $machine->specifications ? json_encode($machine->specifications, JSON_PRETTY_PRINT) : '') }}</textarea>
                    @error('specifications')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="form-section">
                <h6 class="mb-3">Temperature Ranges (°C)</h6>
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label for="temp_min_normal" class="form-label">Normal Min <span class="text-danger">*</span></label>
                        <input type="number" name="temp_min_normal" id="temp_min_normal" step="0.1"
                               class="form-control @error('temp_min_normal') is-invalid @enderror"
                               value="{{ old('temp_min_normal', $machine->temp_min_normal) }}" required>
                        @error('temp_min_normal')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label for="temp_max_normal" class="form-label">Normal Max <span class="text-danger">*</span></label>
                        <input type="number" name="temp_max_normal" id="temp_max_normal" step="0.1"
                               class="form-control @error('temp_max_normal') is-invalid @enderror"
                               value="{{ old('temp_max_normal', $machine->temp_max_normal) }}" required>
                        @error('temp_max_normal')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label for="temp_critical_min" class="form-label">Critical Min <span class="text-danger">*</span></label>
                        <input type="number" name="temp_critical_min" id="temp_critical_min" step="0.1"
                               class="form-control @error('temp_critical_min') is-invalid @enderror"
                               value="{{ old('temp_critical_min', $machine->temp_critical_min) }}" required>
                        @error('temp_critical_min')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label for="temp_critical_max" class="form-label">Critical Max <span class="text-danger">*</span></label>
                        <input type="number" name="temp_critical_max" id="temp_critical_max" step="0.1"
                               class="form-control @error('temp_critical_max') is-invalid @enderror"
                               value="{{ old('temp_critical_max', $machine->temp_critical_max) }}" required>
                        @error('temp_critical_max')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                       {{ old('is_active', $machine->is_active) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active">
                    Machine is Active
                </label>
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
    }
});
</script>
@endpush
