@extends('layouts.app')

@section('title', 'Edit Anomaly')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="bi bi-pencil text-primary"></i> Edit Anomaly
    </h1>
    <div class="btn-group">
        <a href="{{ route('anomalies.show', $anomaly) }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Details
        </a>
        <a href="{{ route('anomalies.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-list"></i> All Anomalies
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-exclamation-triangle"></i> Edit Anomaly #{{ $anomaly->id }}
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('anomalies.update', $anomaly) }}">
                    @csrf
                    @method('PUT')

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="severity" class="form-label">Severity</label>
                            <select name="severity" id="severity" class="form-select @error('severity') is-invalid @enderror">
                                <option value="low" {{ old('severity', $anomaly->severity) == 'low' ? 'selected' : '' }}>Low</option>
                                <option value="medium" {{ old('severity', $anomaly->severity) == 'medium' ? 'selected' : '' }}>Medium</option>
                                <option value="high" {{ old('severity', $anomaly->severity) == 'high' ? 'selected' : '' }}>High</option>
                                <option value="critical" {{ old('severity', $anomaly->severity) == 'critical' ? 'selected' : '' }}>Critical</option>
                            </select>
                            @error('severity')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-select @error('status') is-invalid @enderror">
                                <option value="new" {{ old('status', $anomaly->status) == 'new' ? 'selected' : '' }}>New</option>
                                <option value="acknowledged" {{ old('status', $anomaly->status) == 'acknowledged' ? 'selected' : '' }}>Acknowledged</option>
                                <option value="investigating" {{ old('status', $anomaly->status) == 'investigating' ? 'selected' : '' }}>Investigating</option>
                                <option value="resolved" {{ old('status', $anomaly->status) == 'resolved' ? 'selected' : '' }}>Resolved</option>
                                <option value="false_positive" {{ old('status', $anomaly->status) == 'false_positive' ? 'selected' : '' }}>False Positive</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea name="description" id="description" rows="3"
                                  class="form-control @error('description') is-invalid @enderror"
                                  placeholder="Describe the anomaly...">{{ old('description', $anomaly->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="resolution_notes" class="form-label">Resolution Notes</label>
                        <textarea name="resolution_notes" id="resolution_notes" rows="4"
                                  class="form-control"
                                  placeholder="Add notes about the resolution or investigation...">{{ old('resolution_notes', $anomaly->resolution_notes) }}</textarea>
                        <div class="form-text">Add any additional information about how this anomaly was handled or resolved.</div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <div>
                            <form method="POST" action="{{ route('anomalies.destroy', $anomaly) }}"
                                  onsubmit="return confirm('Are you sure you want to delete this anomaly?')"
                                  style="display: inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">
                                    <i class="bi bi-trash"></i> Delete Anomaly
                                </button>
                            </form>
                        </div>
                        <div class="gap-2 d-flex">
                            <a href="{{ route('anomalies.show', $anomaly) }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Update Anomaly
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Current Anomaly Info -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-info-circle"></i> Current Information
                </h5>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td><strong>Machine:</strong></td>
                        <td>{{ $anomaly->machine->name }}</td>
                    </tr>
                    <tr>
                        <td><strong>Branch:</strong></td>
                        <td>{{ $anomaly->machine->branch->name }}</td>
                    </tr>
                    <tr>
                        <td><strong>Type:</strong></td>
                        <td><span class="badge bg-info">{{ $anomaly->type_name }}</span></td>
                    </tr>
                    <tr>
                        <td><strong>Detected:</strong></td>
                        <td>{{ $anomaly->detected_at->format('d/m/Y H:i:s') }}</td>
                    </tr>
                    @if($anomaly->acknowledged_at)
                    <tr>
                        <td><strong>Acknowledged:</strong></td>
                        <td>{{ $anomaly->acknowledged_at->format('d/m/Y H:i:s') }}</td>
                    </tr>
                    @endif
                    @if($anomaly->resolved_at)
                    <tr>
                        <td><strong>Resolved:</strong></td>
                        <td>{{ $anomaly->resolved_at->format('d/m/Y H:i:s') }}</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-lightning"></i> Quick Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('machines.show', $anomaly->machine) }}" class="btn btn-outline-primary">
                        <i class="bi bi-cpu"></i> View Machine Details
                    </a>
                    @if($anomaly->temperature_reading_id)
                        <a href="{{ route('temperature.show', $anomaly->temperature_reading_id) }}" class="btn btn-outline-info">
                            <i class="bi bi-thermometer"></i> View Temperature Reading
                        </a>
                    @endif
                    <a href="{{ route('machines.temperature-history', $anomaly->machine) }}" class="btn btn-outline-secondary">
                        <i class="bi bi-graph-up"></i> Temperature History
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
