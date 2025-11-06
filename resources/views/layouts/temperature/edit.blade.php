@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Edit Temperature Reading</h3>
                    <div class="card-tools">
                        <a href="{{ route('temperature.show', $temperature->id) }}" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Details
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('temperature.update', $temperature->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="machine_id">Machine *</label>
                                    <select name="machine_id" id="machine_id" class="form-control @error('machine_id') is-invalid @enderror" required>
                                        <option value="">Select Machine</option>
                                        @foreach($machines as $machine)
                                            <option value="{{ $machine->id }}"
                                                {{ old('machine_id', $temperature->machine_id) == $machine->id ? 'selected' : '' }}>
                                                {{ $machine->name }} - {{ $machine->branch->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('machine_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="timestamp">Timestamp *</label>
                                    <input type="datetime-local" name="timestamp" id="timestamp"
                                           class="form-control @error('timestamp') is-invalid @enderror"
                                           value="{{ old('timestamp', $temperature->timestamp->format('Y-m-d\TH:i')) }}" required>
                                    @error('timestamp')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="temperature_value">Temperature (°C) *</label>
                                    <input type="number" step="0.1" name="temperature_value" id="temperature_value"
                                           class="form-control @error('temperature_value') is-invalid @enderror"
                                           value="{{ old('temperature_value', $temperature->temperature_value) }}" required>
                                    @error('temperature_value')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="validation_status">Validation Status *</label>
                                    <select name="validation_status" id="validation_status" class="form-control @error('validation_status') is-invalid @enderror" required>
                                        <option value="">Select Status</option>
                                        @foreach($validationStatusOptions as $value => $label)
                                            <option value="{{ $value }}"
                                                {{ old('validation_status', $temperature->validation_status) == $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('validation_status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="validation_notes">Notes</label>
                            <textarea name="validation_notes" id="validation_notes" rows="3"
                                      class="form-control @error('validation_notes') is-invalid @enderror">{{ old('validation_notes', $temperature->notes) }}</textarea>
                            @error('validation_notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Reading
                            </button>
                            <a href="{{ route('temperature.show', $temperature->id) }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Additional client-side validation if needed
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        form.addEventListener('submit', function(e) {
            const tempValue = parseFloat(document.getElementById('temperature_value').value);
            if (tempValue < -50 || tempValue > 50) {
                e.preventDefault();
                alert('Temperature must be between -50°C and 50°C');
                return false;
            }
        });
    });
</script>
@endsection
