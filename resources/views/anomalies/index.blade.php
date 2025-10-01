@extends('layouts.app')

@section('title', 'Anomaly Management')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="bi bi-exclamation-triangle text-warning"></i> Manajemen Anomali
    </h1>
    <div class="btn-group">
        <button class="btn btn-info" onclick="runGlobalAnomalyCheck()">
            <i class="bi bi-search"></i> Run Anomaly Check
        </button>
        <button class="btn btn-success" onclick="exportAnomalies()">
            <i class="bi bi-download"></i> Export Report
        </button>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);">
            <div class="stat-value">{{ $stats['critical'] }}</div>
            <div class="stat-label">Critical Anomalies</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
            <div class="stat-value">{{ $stats['new'] }}</div>
            <div class="stat-label">New Anomalies</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
            <div class="stat-value">{{ $stats['investigating'] }}</div>
            <div class="stat-label">Under Investigation</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
            <div class="stat-value">{{ $stats['resolved'] }}</div>
            <div class="stat-label">Resolved</div>
        </div>
    </div>
</div>

<!-- Filter Section -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-2">
                <label for="severity" class="form-label">Severity</label>
                <select name="severity" id="severity" class="form-select">
                    <option value="">All Severities</option>
                    <option value="critical" {{ request('severity') == 'critical' ? 'selected' : '' }}>Critical</option>
                    <option value="high" {{ request('severity') == 'high' ? 'selected' : '' }}>High</option>
                    <option value="medium" {{ request('severity') == 'medium' ? 'selected' : '' }}>Medium</option>
                    <option value="low" {{ request('severity') == 'low' ? 'selected' : '' }}>Low</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="status" class="form-label">Status</label>
                <select name="status" id="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="new" {{ request('status') == 'new' ? 'selected' : '' }}>New</option>
                    <option value="acknowledged" {{ request('status') == 'acknowledged' ? 'selected' : '' }}>Acknowledged</option>
                    <option value="investigating" {{ request('status') == 'investigating' ? 'selected' : '' }}>Investigating</option>
                    <option value="resolved" {{ request('status') == 'resolved' ? 'selected' : '' }}>Resolved</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="machine_id" class="form-label">Machine</label>
                <select name="machine_id" id="machine_id" class="form-select">
                    <option value="">All Machines</option>
                    @foreach($machines->groupBy('branch.name') as $branchName => $branchMachines)
                        <optgroup label="{{ $branchName }}">
                            @foreach($branchMachines as $machine)
                                <option value="{{ $machine->id }}"
                                    {{ request('machine_id') == $machine->id ? 'selected' : '' }}>
                                    {{ $machine->name }}
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="date_from" class="form-label">From Date</label>
                <input type="date" name="date_from" id="date_from" class="form-control"
                       value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2">
                <label for="date_to" class="form-label">To Date</label>
                <input type="date" name="date_to" id="date_to" class="form-control"
                       value="{{ request('date_to') }}">
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Anomalies Table -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-list-ul"></i> Daftar Anomali
            <span class="badge bg-secondary ms-2">{{ $anomalies->total() }} total</span>
        </h5>
    </div>
    <div class="card-body">
        @if($anomalies->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Detected At</th>
                            <th>Machine</th>
                            <th>Branch</th>
                            <th>Type</th>
                            <th>Severity</th>
                            <th>Temperature</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($anomalies as $anomaly)
                            <tr class="anomaly-row" data-severity="{{ $anomaly->severity }}">
                                <td>
                                    <strong>{{ $anomaly->detected_at->format('d/m/Y H:i') }}</strong><br>
                                    <small class="text-muted">{{ $anomaly->detected_at->diffForHumans() }}</small>
                                </td>
                                <td>
                                    <strong>{{ $anomaly->machine->name }}</strong><br>
                                    <small class="text-muted">{{ $anomaly->machine->type }}</small>
                                </td>
                                <td>{{ $anomaly->machine->branch->name }}</td>
                                <td>
                                    <span class="badge bg-info">{{ $anomaly->type_name }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $anomaly->severity_color }}">
                                        {{ ucfirst($anomaly->severity) }}
                                    </span>
                                </td>
                                <td>
                                    <strong class="text-{{ $anomaly->temperatureReading->status == 'critical' ? 'danger' : ($anomaly->temperatureReading->status == 'warning' ? 'warning' : 'success') }}">
                                        {{ number_format($anomaly->temperatureReading->temperature, 1) }}°C
                                    </strong>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $anomaly->status_color }}">
                                        {{ ucfirst(str_replace('_', ' ', $anomaly->status)) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('anomalies.show', $anomaly) }}"
                                           class="btn btn-outline-info" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @if($anomaly->status === 'new')
                                            <button class="btn btn-outline-warning"
                                                    onclick="acknowledgeAnomaly({{ $anomaly->id }})"
                                                    title="Acknowledge">
                                                <i class="bi bi-check"></i>
                                            </button>
                                        @endif
                                        @if(in_array($anomaly->status, ['new', 'acknowledged', 'investigating']))
                                            <button class="btn btn-outline-success"
                                                    onclick="resolveAnomaly({{ $anomaly->id }})"
                                                    title="Resolve">
                                                <i class="bi bi-check-circle"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center mt-3">
                {{ $anomalies->withQueryString()->links() }}
            </div>
        @else
            <div class="text-center py-5">
                <i class="bi bi-shield-check display-4 text-success"></i>
                <p class="text-muted mt-2">No anomalies found with current filters.</p>
            </div>
        @endif
    </div>
</div>

<!-- Acknowledge Modal -->
<div class="modal fade" id="acknowledgeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Acknowledge Anomaly</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="acknowledgeForm">
                    <div class="mb-3">
                        <label for="acknowledged_by" class="form-label">Acknowledged By</label>
                        <input type="text" class="form-control" id="acknowledged_by" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" onclick="submitAcknowledge()">Acknowledge</button>
            </div>
        </div>
    </div>
</div>

<!-- Resolve Modal -->
<div class="modal fade" id="resolveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Resolve Anomaly</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="resolveForm">
                    <div class="mb-3">
                        <label for="resolution_notes" class="form-label">Resolution Notes</label>
                        <textarea class="form-control" id="resolution_notes" rows="4" required
                                  placeholder="Describe how the anomaly was resolved..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="submitResolve()">Resolve</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
let currentAnomalyId = null;

function acknowledgeAnomaly(anomalyId) {
    currentAnomalyId = anomalyId;
    document.getElementById('acknowledged_by').value = '';
    new bootstrap.Modal(document.getElementById('acknowledgeModal')).show();
}

function resolveAnomaly(anomalyId) {
    currentAnomalyId = anomalyId;
    document.getElementById('resolution_notes').value = '';
    new bootstrap.Modal(document.getElementById('resolveModal')).show();
}

function submitAcknowledge() {
    const acknowledgedBy = document.getElementById('acknowledged_by').value;

    if (!acknowledgedBy) {
        alert('Please enter who is acknowledging this anomaly.');
        return;
    }

    fetch(`/anomalies/${currentAnomalyId}/acknowledge`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            acknowledged_by: acknowledgedBy
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error acknowledging anomaly: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error: ' + error.message);
    });
}

function submitResolve() {
    const resolutionNotes = document.getElementById('resolution_notes').value;

    if (!resolutionNotes) {
        alert('Please enter resolution notes.');
        return;
    }

    fetch(`/anomalies/${currentAnomalyId}/resolve`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            resolution_notes: resolutionNotes
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error resolving anomaly: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error: ' + error.message);
    });
}

function runGlobalAnomalyCheck() {
    if (confirm('This will run anomaly detection on all active machines. This may take a few minutes. Continue?')) {
        // Implementation would call a global anomaly check endpoint
        alert('Global anomaly check started. You will be notified when complete.');
    }
}

function exportAnomalies() {
    const params = new URLSearchParams(window.location.search);
    window.open('/anomalies/export?' + params.toString(), '_blank');
}
</script>
@endpush
