@extends('layouts.app')

@section('title', 'Branch Details')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="bi bi-building text-primary"></i> Branch Details
    </h1>
    <div class="btn-group">
        <a href="{{ route('branches.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to List
        </a>
        <a href="{{ route('branches.edit', $branch) }}" class="btn btn-warning">
            <i class="bi bi-pencil"></i> Edit
        </a>
        <a href="{{ route('branches.performance', $branch) }}" class="btn btn-info">
            <i class="bi bi-graph-up"></i> Performance
        </a>
        <a href="{{ route('branches.export-pdf', $branch) }}" class="btn btn-success">
            <i class="bi bi-download"></i> Export PDF
        </a>
    </div>
</div>

<div class="row">
    <!-- Branch Information -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-info-circle"></i> Branch Information
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Name:</strong></td>
                                <td>{{ $branch->name }}</td>
                            </tr>
                            <tr>
                                <td><strong>Code:</strong></td>
                                <td><code>{{ $branch->code }}</code></td>
                            </tr>
                            <tr>
                                <td><strong>City:</strong></td>
                                <td>{{ $branch->city }}</td>
                            </tr>
                            <tr>
                                <td><strong>Region:</strong></td>
                                <td>{{ $branch->region ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Status:</strong></td>
                                <td>
                                    <span class="badge bg-{{ $branch->is_active ? 'success' : 'secondary' }}">
                                        {{ $branch->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Total Machines:</strong></td>
                                <td>{{ $stats['total_machines'] }}</td>
                            </tr>
                            <tr>
                                <td><strong>Total Readings:</strong></td>
                                <td>{{ number_format($stats['total_readings']) }}</td>
                            </tr>
                            <tr>
                                <td><strong>Avg Temperature:</strong></td>
                                <td>{{ number_format($stats['avg_temperature'] ?? 0, 1) }}°C</td>
                            </tr>
                            <tr>
                                <td><strong>Anomalies:</strong></td>
                                <td>
                                    <span class="badge bg-{{ $stats['anomaly_count'] > 0 ? 'warning' : 'success' }}">
                                        {{ $stats['anomaly_count'] }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Pending Maintenance:</strong></td>
                                <td>
                                    <span class="badge bg-{{ $stats['pending_maintenance'] > 0 ? 'danger' : 'success' }}">
                                        {{ $stats['pending_maintenance'] }}
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                @if($branch->address)
                <div class="mt-3">
                    <h6>Address:</h6>
                    <p class="text-muted">{{ $branch->address }}</p>
                </div>
                @endif

                @if($branch->contact_info)
                <div class="mt-3">
                    <h6>Contact Information:</h6>
                    <div class="row">
                        @if(isset($branch->contact_info['phone']))
                        <div class="col-md-6">
                            <p><i class="bi bi-telephone"></i> {{ $branch->contact_info['phone'] }}</p>
                        </div>
                        @endif
                        @if(isset($branch->contact_info['email']))
                        <div class="col-md-6">
                            <p><i class="bi bi-envelope"></i> {{ $branch->contact_info['email'] }}</p>
                        </div>
                        @endif
                        @if(isset($branch->contact_info['manager']))
                        <div class="col-md-6">
                            <p><i class="bi bi-person"></i> Manager: {{ $branch->contact_info['manager'] }}</p>
                        </div>
                        @endif
                        @if(isset($branch->contact_info['operating_hours']))
                        <div class="col-md-6">
                            <p><i class="bi bi-clock"></i> Hours: {{ $branch->contact_info['operating_hours'] }}</p>
                        </div>
                        @endif
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Recent Temperature Readings -->
        @if($recentReadings->count() > 0)
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-thermometer"></i> Recent Temperature Readings
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Date/Time</th>
                                <th>Machine</th>
                                <th>Temperature</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentReadings->take(10) as $reading)
                            <tr>
                                <td>{{ $reading->recorded_at->format('d/m/Y H:i:s') }}</td>
                                <td>{{ $reading->machine->name }}</td>
                                <td>
                                    <strong class="text-{{ $reading->status == 'critical' ? 'danger' : ($reading->status == 'warning' ? 'warning' : 'success') }}">
                                        {{ number_format($reading->temperature, 1) }}°C
                                    </strong>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $reading->status == 'critical' ? 'danger' : ($reading->status == 'warning' ? 'warning' : 'success') }}">
                                        {{ ucfirst($reading->status) }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Sidebar -->
    <div class="col-md-4">
        <!-- Machines List -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-cpu"></i> Machines ({{ $branch->machines->count() }})
                </h5>
            </div>
            <div class="card-body">
                @if($branch->machines->count() > 0)
                    @foreach($branch->machines as $machine)
                    <div class="border-bottom pb-2 mb-2">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong>{{ $machine->name }}</strong><br>
                                <small class="text-muted">{{ $machine->type }}</small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-{{ $machine->is_active ? 'success' : 'secondary' }}">
                                    {{ $machine->is_active ? 'Active' : 'Inactive' }}
                                </span>
                                @if($machine->latest_temperature)
                                <br><small class="text-muted">{{ number_format($machine->latest_temperature->temperature, 1) }}°C</small>
                                @endif
                            </div>
                        </div>
                        <div class="mt-1">
                            <a href="{{ route('machines.show', $machine) }}" class="btn btn-sm btn-outline-primary">
                                View Details
                            </a>
                        </div>
                    </div>
                    @endforeach
                @else
                    <p class="text-muted text-center">No machines registered</p>
                @endif
                <div class="text-center mt-3">
                    <a href="{{ route('machines.create', ['branch_id' => $branch->id]) }}" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-plus"></i> Add Machine
                    </a>
                </div>
            </div>
        </div>

        <!-- Active Anomalies -->
        @if($activeAnomalies->count() > 0)
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-exclamation-triangle"></i> Active Anomalies
                </h5>
            </div>
            <div class="card-body">
                @foreach($activeAnomalies->take(5) as $anomaly)
                <div class="alert alert-{{ $anomaly->severity == 'critical' ? 'danger' : ($anomaly->severity == 'high' ? 'warning' : 'info') }} py-2 mb-2">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <strong>{{ $anomaly->machine->name }}</strong><br>
                            <small>{{ $anomaly->type_name }}: {{ number_format($anomaly->temperatureReading->temperature, 1) }}°C</small>
                        </div>
                        <small class="text-muted">{{ $anomaly->detected_at->diffForHumans() }}</small>
                    </div>
                </div>
                @endforeach
                <div class="text-center">
                    <a href="{{ route('anomalies.index', ['branch_id' => $branch->id]) }}" class="btn btn-outline-warning btn-sm">
                        View All Anomalies
                    </a>
                </div>
            </div>
        </div>
        @endif

        <!-- Quick Actions -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-lightning"></i> Quick Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('machines.create', ['branch_id' => $branch->id]) }}" class="btn btn-outline-primary">
                        <i class="bi bi-plus"></i> Add Machine
                    </a>
                    <a href="{{ route('branches.performance', $branch) }}" class="btn btn-outline-info">
                        <i class="bi bi-graph-up"></i> View Performance
                    </a>
                    <a href="{{ route('temperature.index', ['branch_id' => $branch->id]) }}" class="btn btn-outline-success">
                        <i class="bi bi-thermometer"></i> Temperature Data
                    </a>
                    @if(!$branch->is_active && $branch->machines->count() == 0)
                    <form method="POST" action="{{ route('branches.destroy', $branch) }}"
                          onsubmit="return confirm('Are you sure you want to delete this branch?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger">
                            <i class="bi bi-trash"></i> Delete Branch
                        </button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
