@extends('layouts.app')

@section('title', 'Machine Details')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="bi bi-cpu text-primary"></i> Machine Details
    </h1>
    <div class="btn-group">
        <a href="{{ route('machines.edit', $machine) }}" class="btn btn-primary">
            <i class="bi bi-pencil"></i> Edit
        </a>
        <a href="{{ route('machines.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-list"></i> All Machines
        </a>
    </div>
</div>

<div class="row">
    <!-- Machine Details Card -->
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-info-circle"></i> Machine Information
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-sm mb-0">
                            <tr>
                                <th width="40%">Name:</th>
                                <td>{{ $machine->name }}</td>
                            </tr>
                            <tr>
                                <th>Type:</th>
                                <td>{{ $machine->type }}</td>
                            </tr>
                            <tr>
                                <th>Model:</th>
                                <td>{{ $machine->model ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Serial Number:</th>
                                <td>{{ $machine->serial_number ?? 'N/A' }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm mb-0">
                            <tr>
                                <th width="40%">Branch:</th>
                                <td>{{ $machine->branch->name }} ({{ $machine->branch->city }})</td>
                            </tr>
                            <tr>
                                <th>Installation Date:</th>
                                <td>{{ $machine->installation_date?->format('Y-m-d') ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Status:</th>
                                <td>
                                    @if($machine->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-danger">Inactive</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Current Status:</th>
                                <td>
                                    @if($machine->current_status === 'normal')
                                        <span class="badge bg-success">Normal</span>
                                    @elseif($machine->current_status === 'warning')
                                        <span class="badge bg-warning text-dark">Warning</span>
                                    @elseif($machine->current_status === 'critical')
                                        <span class="badge bg-danger">Critical</span>
                                    @else
                                        <span class="badge bg-secondary">Unknown</span>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Temperature Ranges -->
                <div class="mt-4 pt-3 border-top">
                    <h6><i class="bi bi-thermometer"></i> Temperature Ranges (°C)</h6>
                    <div class="row g-2">
                        <div class="col-sm-6 col-md-3">
                            <div class="card border-primary">
                                <div class="card-body p-2 text-center">
                                    <small class="text-muted d-block">Normal Min</small>
                                    <div class="h5 mb-0 text-primary">{{ $machine->temp_min_normal }}°C</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="card border-primary">
                                <div class="card-body p-2 text-center">
                                    <small class="text-muted d-block">Normal Max</small>
                                    <div class="h5 mb-0 text-primary">{{ $machine->temp_max_normal }}°C</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="card border-danger">
                                <div class="card-body p-2 text-center">
                                    <small class="text-muted d-block">Critical Min</small>
                                    <div class="h5 mb-0 text-danger">{{ $machine->temp_critical_min }}°C</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="card border-danger">
                                <div class="card-body p-2 text-center">
                                    <small class="text-muted d-block">Critical Max</small>
                                    <div class="h5 mb-0 text-danger">{{ $machine->temp_critical_max }}°C</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-md-3">
                <div class="card bg-primary bg-opacity-10 border-primary">
                    <div class="card-body p-3 text-center">
                        <div class="h2 mb-1 text-primary">{{ $stats['total_readings'] }}</div>
                        <small class="text-muted">Total Readings</small>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="card bg-info bg-opacity-10 border-info">
                    <div class="card-body p-3 text-center">
                        <div class="h2 mb-1 text-info">{{ number_format($stats['avg_temperature'] ?? 0, 1) }}°C</div>
                        <small class="text-muted">Avg Temperature</small>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="card bg-warning bg-opacity-10 border-warning">
                    <div class="card-body p-3 text-center">
                        <div class="h2 mb-1 text-warning">{{ $stats['active_anomalies'] }}</div>
                        <small class="text-muted">Active Anomalies</small>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="card bg-danger bg-opacity-10 border-danger">
                    <div class="card-body p-3 text-center">
                        <div class="h2 mb-1 text-danger">{{ $stats['pending_maintenance'] }}</div>
                        <small class="text-muted">Pending Maintenance</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Readings -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-thermometer-half"></i> Recent Temperature Readings
                </h5>
                <a href="{{ route('machines.temperature-history', $machine) }}" class="btn btn-sm btn-outline-primary">
                    View All
                </a>
            </div>
            <div class="card-body p-0">
                @if($recentReadings->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Date & Time</th>
                                    <th>Temperature (°C)</th>
                                    <th>Status</th>
                                    <th>Anomaly</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentReadings as $reading)
                                    <tr>
                                        <td class="ps-3">{{ $reading->recorded_at->format('M d, H:i') }}</td>
                                        <td class="fw-bold">{{ $reading->temperature }}</td>
                                        <td>
                                            @if($reading->temperature < $machine->temp_critical_min)
                                                <span class="badge bg-danger">Below Critical</span>
                                            @elseif($reading->temperature < $machine->temp_min_normal)
                                                <span class="badge bg-warning text-dark">Below Normal</span>
                                            @elseif($reading->temperature > $machine->temp_critical_max)
                                                <span class="badge bg-danger">Above Critical</span>
                                            @elseif($reading->temperature > $machine->temp_max_normal)
                                                <span class="badge bg-warning text-dark">Above Normal</span>
                                            @else
                                                <span class="badge bg-success">Normal</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($reading->is_anomaly)
                                                <span class="badge bg-danger">Yes</span>
                                            @else
                                                <span class="badge bg-success">No</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="bi bi-thermometer display-4 text-muted opacity-50"></i>
                        <p class="text-muted mt-3">No temperature readings available</p>
                        <a href="{{ route('machines.temperature-history', $machine) }}" class="btn btn-outline-primary btn-sm mt-2">
                            Check Temperature History
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Quick Actions -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-lightning"></i> Quick Actions</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('machines.temperature-history', $machine) }}" class="btn btn-outline-primary text-start">
                        <i class="bi bi-thermometer-half me-2"></i> Temperature History
                    </a>
                    <a href="{{ route('machines.maintenance-history', $machine) }}" class="btn btn-outline-success text-start">
                        <i class="bi bi-tools me-2"></i> Maintenance History
                    </a>
                    <button class="btn btn-outline-warning text-start" onclick="runAnomalyCheck()">
                        <i class="bi bi-exclamation-triangle me-2"></i> Run Anomaly Check
                    </button>
                </div>
            </div>
        </div>

        <!-- Recent Anomalies -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-exclamation-triangle text-warning me-1"></i> Recent Anomalies</h6>
                <span class="badge bg-warning">{{ $recentAnomalies->count() }}</span>
            </div>
            <div class="card-body p-0">
                @if($recentAnomalies->count() > 0)
                    <div class="list-group list-group-flush">
                        @foreach($recentAnomalies as $anomaly)
                            <div class="list-group-item border-0">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        @if($anomaly->severity === 'high')
                                            <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
                                        @elseif($anomaly->severity === 'medium')
                                            <i class="bi bi-exclamation-triangle-fill text-warning fs-5"></i>
                                        @else
                                            <i class="bi bi-info-circle-fill text-info fs-5"></i>
                                        @endif
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <strong>ID : {{ $anomaly->temperature_reading_id }}</strong>
                                                <br>
                                                <small class="text-muted">
                                                    {{ $anomaly->detected_at->format('M d, H:i') }}
                                                </small>
                                            </div>
                                            <span class="badge bg-{{ $anomaly->severity === 'high' ? 'danger' : ($anomaly->severity === 'medium' ? 'warning' : 'info') }}">
                                                {{ ucfirst($anomaly->severity) }}
                                            </span>
                                        </div>
                                        @if($anomaly->description)
                                            <small class="text-muted d-block mt-1">{{ Str::limit($anomaly->description, 50) }}</small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="bi bi-check-circle display-4 text-success opacity-50"></i>
                        <p class="text-muted mt-3 mb-0">No recent anomalies</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Maintenance Recommendations -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-tools text-success me-1"></i> Maintenance</h6>
                <span class="badge bg-{{ $maintenanceRecommendations->where('status', 'pending')->count() > 0 ? 'danger' : 'success' }}">
                    {{ $maintenanceRecommendations->where('status', 'pending')->count() }} Pending
                </span>
            </div>
            <div class="card-body p-0">
                @if($maintenanceRecommendations->count() > 0)
                    <div class="list-group list-group-flush">
                        @foreach($maintenanceRecommendations as $maintenance)
                            <div class="list-group-item border-0">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-medium">{{ Str::limit($maintenance->description, 40) }}</div>
                                        <small class="text-muted d-block">
                                            {{ $maintenance->recommended_date->format('M d, Y') }}
                                        </small>
                                    </div>
                                    <span class="badge bg-{{ $maintenance->status === 'completed' ? 'success' : ($maintenance->status === 'in_progress' ? 'warning' : 'danger') }}">
                                        {{ ucfirst($maintenance->status) }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="bi bi-wrench display-4 text-muted opacity-50"></i>
                        <p class="text-muted mt-3 mb-0">No maintenance recommendations</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function runAnomalyCheck() {
    if (confirm('Run anomaly detection on this machine?')) {
        // Show loading
        const btn = event.target;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i> Processing...';
        btn.disabled = true;

        fetch("{{ route('machines.run-anomaly-check', $machine) }}")
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Anomaly check completed! Found ' + data.anomaly_count + ' anomalies.');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            })
            .catch(error => {
                alert('Network error occurred');
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
    }
}
</script>
@endpush
