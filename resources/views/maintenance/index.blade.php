@extends('layouts.app')

@section('title', 'Maintenance Management')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="bi bi-tools text-primary"></i> Manajemen Pemeliharaan
    </h1>
    <div class="btn-group">
        <a href="{{ route('maintenance.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Add Recommendation
        </a>
        <button class="btn btn-success" onclick="exportMaintenance()">
            <i class="bi bi-download"></i> Export Report
        </button>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
            <div class="stat-value">{{ $stats['pending'] }}</div>
            <div class="stat-label">Pending</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
            <div class="stat-value">{{ $stats['scheduled'] }}</div>
            <div class="stat-label">Scheduled</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);">
            <div class="stat-value">{{ $stats['overdue'] }}</div>
            <div class="stat-label">Overdue</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
            <div class="stat-value">{{ $stats['completed'] }}</div>
            <div class="stat-label">Completed</div>
        </div>
    </div>
</div>

<!-- Filter Section -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-2">
                <label for="status" class="form-label">Status</label>
                <select name="status" id="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="scheduled" {{ request('status') == 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                    <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="priority" class="form-label">Priority</label>
                <select name="priority" id="priority" class="form-select">
                    <option value="">All Priorities</option>
                    <option value="critical" {{ request('priority') == 'critical' ? 'selected' : '' }}>Critical</option>
                    <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>High</option>
                    <option value="medium" {{ request('priority') == 'medium' ? 'selected' : '' }}>Medium</option>
                    <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>Low</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="type" class="form-label">Type</label>
                <select name="type" id="type" class="form-select">
                    <option value="">All Types</option>
                    <option value="preventive" {{ request('type') == 'preventive' ? 'selected' : '' }}>Preventive</option>
                    <option value="predictive" {{ request('type') == 'predictive' ? 'selected' : '' }}>Predictive</option>
                    <option value="corrective" {{ request('type') == 'corrective' ? 'selected' : '' }}>Corrective</option>
                    <option value="emergency" {{ request('type') == 'emergency' ? 'selected' : '' }}>Emergency</option>
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
            <div class="col-md-1 d-flex align-items-end">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Maintenance Table -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-list-ul"></i> Maintenance Recommendations
            <span class="badge bg-secondary ms-2">{{ $recommendations->total() }} total</span>
        </h5>
    </div>
    <div class="card-body">
        @if($recommendations->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Machine</th>
                            <th>Branch</th>
                            <th>Type</th>
                            <th>Priority</th>
                            <th>Title</th>
                            <th>Recommended Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recommendations as $recommendation)
                            <tr class="{{ $recommendation->is_overdue ? 'table-warning' : '' }}">
                                <td>
                                    <strong>{{ $recommendation->machine->name }}</strong><br>
                                    <small class="text-muted">{{ $recommendation->machine->type }}</small>
                                </td>
                                <td>{{ $recommendation->machine->branch->name }}</td>
                                <td>
                                    <span class="badge bg-info">{{ $recommendation->type_name }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $recommendation->priority_color }}">
                                        {{ ucfirst($recommendation->priority) }}
                                    </span>
                                    @if($recommendation->is_overdue)
                                        <i class="bi bi-exclamation-triangle text-warning" title="Overdue"></i>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ $recommendation->title }}</strong><br>
                                    <small class="text-muted">{{ Str::limit($recommendation->description, 50) }}</small>
                                </td>
                                <td>
                                    <strong>{{ $recommendation->recommended_date->format('d/m/Y') }}</strong><br>
                                    @if($recommendation->scheduled_date)
                                        <small class="text-info">Scheduled: {{ $recommendation->scheduled_date->format('d/m/Y') }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $recommendation->status_color }}">
                                        {{ ucfirst(str_replace('_', ' ', $recommendation->status)) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('maintenance.show', $recommendation) }}"
                                           class="btn btn-outline-info" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @if($recommendation->status === 'pending')
                                            <button class="btn btn-outline-primary"
                                                    onclick="scheduleMaintenace({{ $recommendation->id }})"
                                                    title="Schedule">
                                                <i class="bi bi-calendar-plus"></i>
                                            </button>
                                        @endif
                                        @if(in_array($recommendation->status, ['scheduled', 'in_progress']))
                                            <button class="btn btn-outline-success"
                                                    onclick="completeMaintenance({{ $recommendation->id }})"
                                                    title="Mark Complete">
                                                <i class="bi bi-check-circle"></i>
                                            </button>
                                        @endif
                                        <a href="{{ route('maintenance.edit', $recommendation) }}"
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
                {{ $recommendations->withQueryString()->links() }}
            </div>
        @else
            <div class="text-center py-5">
                <i class="bi bi-tools display-4 text-muted"></i>
                <p class="text-muted mt-2">No maintenance recommendations found.</p>
                <a href="{{ route('maintenance.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i> Create First Recommendation
                </a>
            </div>
        @endif
    </div>
</div>

<!-- Schedule Modal -->
<div class="modal fade" id="scheduleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Schedule Maintenance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="scheduleForm">
                    <div class="mb-3">
                        <label for="scheduled_date" class="form-label">Scheduled Date</label>
                        <input type="date" class="form-control" id="scheduled_date" required
                               min="{{ date('Y-m-d') }}">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitSchedule()">Schedule</button>
            </div>
        </div>
    </div>
</div>

<!-- Complete Modal -->
<div class="modal fade" id="completeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Complete Maintenance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="completeForm">
                    <div class="mb-3">
                        <label for="completion_notes" class="form-label">Completion Notes</label>
                        <textarea class="form-control" id="completion_notes" rows="4" required
                                  placeholder="Describe what was done during maintenance..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="submitComplete()">Mark Complete</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
let currentMaintenanceId = null;

function scheduleMaintenace(maintenanceId) {
    currentMaintenanceId = maintenanceId;
    document.getElementById('scheduled_date').value = '';
    new bootstrap.Modal(document.getElementById('scheduleModal')).show();
}

function completeMaintenance(maintenanceId) {
    currentMaintenanceId = maintenanceId;
    document.getElementById('completion_notes').value = '';
    new bootstrap.Modal(document.getElementById('completeModal')).show();
}

function submitSchedule() {
    const scheduledDate = document.getElementById('scheduled_date').value;

    if (!scheduledDate) {
        alert('Please select a scheduled date.');
        return;
    }

    fetch(`/maintenance/${currentMaintenanceId}/schedule`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            scheduled_date: scheduledDate
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error scheduling maintenance: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error: ' + error.message);
    });
}

function submitComplete() {
    const completionNotes = document.getElementById('completion_notes').value;

    if (!completionNotes) {
        alert('Please enter completion notes.');
        return;
    }

    fetch(`/maintenance/${currentMaintenanceId}/complete`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            completion_notes: completionNotes
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error completing maintenance: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error: ' + error.message);
    });
}

function exportMaintenance() {
    const params = new URLSearchParams(window.location.search);
    window.open('/maintenance/export/pdf?' + params.toString(), '_blank');
}
</script>
@endpush
