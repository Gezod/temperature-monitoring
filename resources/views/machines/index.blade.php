@extends('layouts.app')

@section('title', 'Machine Management')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="bi bi-cpu text-primary"></i> Manajemen Mesin
    </h1>
    <div class="btn-group">
        <a href="{{ route('machines.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Add Machine
        </a>
        {{-- <button class="btn btn-warning" onclick="runGlobalAnomalyCheck()">
            <i class="bi bi-search"></i> Run Anomaly Check
        </button> --}}
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
            <div class="stat-value">{{ $stats['active'] }}</div>
            <div class="stat-label">Active Machines</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);">
            <div class="stat-value">{{ $stats['with_anomalies'] }}</div>
            <div class="stat-label">With Anomalies</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
            <div class="stat-value">{{ $stats['needs_maintenance'] }}</div>
            <div class="stat-label">Needs Maintenance</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
            <div class="stat-value">{{ $stats['total'] }}</div>
            <div class="stat-label">Total Machines</div>
        </div>
    </div>
</div>

<!-- Filter Section -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label for="branch_id" class="form-label">Branch</label>
                <select name="branch_id" id="branch_id" class="form-select">
                    <option value="">All Branches</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}"
                            {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="type" class="form-label">Type</label>
                <select name="type" id="type" class="form-select">
                    <option value="">All Types</option>
                    @foreach($types as $type)
                        <option value="{{ $type }}"
                            {{ request('type') == $type ? 'selected' : '' }}>
                            {{ $type }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select name="status" id="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="bi bi-search"></i> Filter
                </button>
                <a href="{{ route('machines.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Machines Table -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-list-ul"></i> Machine List
            <span class="badge bg-secondary ms-2">{{ $machines->total() }} total</span>
        </h5>
    </div>
    <div class="card-body">
        @if($machines->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Machine</th>
                            <th>Branch</th>
                            <th>Type</th>
                            <th>Temperature Range</th>
                            <th>Current Status</th>
                            <th>Anomalies</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($machines as $machine)
                            <tr>
                                <td>
                                    <strong>{{ $machine->name }}</strong><br>
                                    @if($machine->model)
                                        <small class="text-muted">{{ $machine->model }}</small><br>
                                    @endif
                                    @if($machine->serial_number)
                                        <small class="text-muted">SN: {{ $machine->serial_number }}</small>
                                    @endif
                                </td>
                                <td>{{ $machine->branch->name }}</td>
                                <td>
                                    <span class="badge bg-info">{{ $machine->type }}</span>
                                </td>
                                <td>
                                    <small>
                                        <strong>Normal:</strong> {{ $machine->temp_min_normal }}°C to {{ $machine->temp_max_normal }}°C<br>
                                        <strong>Critical:</strong> {{ $machine->temp_critical_min }}°C to {{ $machine->temp_critical_max }}°C
                                    </small>
                                </td>
                                <td>
                                    @php
                                        $latest = $machine->latest_temperature;
                                        $status = $machine->current_status;
                                    @endphp
                                    @if($latest)
                                        <span class="badge bg-{{ $status == 'critical' ? 'danger' : ($status == 'warning' ? 'warning' : 'success') }}">
                                            {{ number_format($latest->temperature, 1) }}°C
                                        </span><br>
                                        <small class="text-muted">{{ $latest->recorded_at->diffForHumans() }}</small>
                                    @else
                                        <span class="badge bg-secondary">No Data</span>
                                    @endif
                                </td>
                                <td>
                                    @if($machine->active_anomalies_count > 0)
                                        <span class="badge bg-danger">{{ $machine->active_anomalies_count }}</span>
                                    @else
                                        <span class="badge bg-success">0</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $machine->is_active ? 'success' : 'secondary' }}">
                                        {{ $machine->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('machines.show', $machine) }}"
                                           class="btn btn-outline-info" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        {{-- <a href="{{ route('machines.temperature-history', $machine) }}"
                                           class="btn btn-outline-success" title="Temperature History">
                                            <i class="bi bi-graph-up"></i>
                                        </a> --}}
                                        {{-- <button class="btn btn-outline-warning"
                                                onclick="runAnomalyCheck({{ $machine->id }})"
                                                title="Run Anomaly Check">
                                            <i class="bi bi-search"></i>
                                        </button> --}}
                                        <a href="{{ route('machines.edit', $machine) }}"
                                           class="btn btn-outline-secondary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center mt-3">
                {{ $machines->withQueryString()->links() }}
            </div>
        @else
            <div class="text-center py-5">
                <i class="bi bi-cpu display-4 text-muted"></i>
                <p class="text-muted mt-2">No machines found with current filters.</p>
                <a href="{{ route('machines.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i> Add First Machine
                </a>
            </div>
        @endif
    </div>
</div>

@endsection

@push('scripts')
<script>
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
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error: ' + error.message);
        });
    }
}

function runGlobalAnomalyCheck() {
    if (confirm('This will run anomaly detection on all active machines. This may take a few minutes. Continue?')) {
        // Implementation would call a global anomaly check endpoint
        alert('Global anomaly check started. You will be notified when complete.');
    }
}
</script>
@endpush
