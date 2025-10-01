@extends('layouts.app')

@section('title', 'Temperature Reading Details')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="bi bi-thermometer text-primary"></i> Temperature Reading Details
    </h1>
    <div class="btn-group">
        <a href="{{ route('temperature.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to List
        </a>
        @if($reading->reading_type === 'manual')
            <a href="{{ route('temperature.edit', $reading) }}" class="btn btn-warning">
                <i class="bi bi-pencil"></i> Edit
            </a>
        @endif
    </div>
</div>

<div class="row">
    <!-- Reading Details -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-info-circle"></i> Reading Information
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Date & Time:</strong></td>
                                <td>{{ $reading->recorded_at->format('d/m/Y H:i:s') }}</td>
                            </tr>
                            <tr>
                                <td><strong>Temperature:</strong></td>
                                <td>
                                    <span class="h4 text-{{ $reading->status == 'critical' ? 'danger' : ($reading->status == 'warning' ? 'warning' : 'success') }}">
                                        {{ number_format($reading->temperature, 1) }}°C
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Status:</strong></td>
                                <td>
                                    <span class="badge bg-{{ $reading->status == 'critical' ? 'danger' : ($reading->status == 'warning' ? 'warning' : 'success') }}">
                                        {{ ucfirst($reading->status) }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Reading Type:</strong></td>
                                <td>
                                    <span class="badge bg-{{ $reading->reading_type == 'automatic' ? 'info' : ($reading->reading_type == 'imported' ? 'secondary' : 'primary') }}">
                                        {{ ucfirst($reading->reading_type) }}
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Machine:</strong></td>
                                <td>{{ $reading->machine->name }}</td>
                            </tr>
                            <tr>
                                <td><strong>Branch:</strong></td>
                                <td>{{ $reading->machine->branch->name }}</td>
                            </tr>
                            <tr>
                                <td><strong>Machine Type:</strong></td>
                                <td>{{ $reading->machine->type }}</td>
                            </tr>
                            @if($reading->source_file)
                            <tr>
                                <td><strong>Source File:</strong></td>
                                <td><small>{{ $reading->source_file }}</small></td>
                            </tr>
                            @endif
                        </table>
                    </div>
                </div>

                @if($reading->metadata)
                <div class="mt-3">
                    <h6>Additional Notes:</h6>
                    <div class="alert alert-info">
                        {{ is_string($reading->metadata) ? $reading->metadata : json_encode($reading->metadata, JSON_PRETTY_PRINT) }}
                    </div>
                </div>
                @endif

                @if($reading->is_anomaly)
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i>
                    <strong>Anomaly Detected!</strong> This reading has been flagged as an anomaly.
                </div>
                @endif
            </div>
        </div>

        <!-- Machine Temperature Ranges -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-thermometer-half"></i> Machine Temperature Ranges
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-success">Normal Range</h6>
                        <p class="mb-0">
                            <strong>{{ $reading->machine->temp_min_normal }}°C</strong> to
                            <strong>{{ $reading->machine->temp_max_normal }}°C</strong>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-danger">Critical Range</h6>
                        <p class="mb-0">
                            Below <strong>{{ $reading->machine->temp_critical_min }}°C</strong> or
                            Above <strong>{{ $reading->machine->temp_critical_max }}°C</strong>
                        </p>
                    </div>
                </div>

                <!-- Temperature Gauge -->
                <div class="mt-3">
                    <div class="progress" style="height: 30px;">
                        @php
                            $minRange = $reading->machine->temp_critical_min;
                            $maxRange = $reading->machine->temp_critical_max;
                            $currentTemp = $reading->temperature;
                            $position = (($currentTemp - $minRange) / ($maxRange - $minRange)) * 100;
                            $position = max(0, min(100, $position));
                        @endphp
                        <div class="progress-bar bg-success" style="width: {{ (($reading->machine->temp_min_normal - $minRange) / ($maxRange - $minRange)) * 100 }}%"></div>
                        <div class="progress-bar bg-warning" style="width: {{ (($reading->machine->temp_max_normal - $reading->machine->temp_min_normal) / ($maxRange - $minRange)) * 100 }}%"></div>
                        <div class="progress-bar bg-danger" style="width: {{ (($maxRange - $reading->machine->temp_max_normal) / ($maxRange - $minRange)) * 100 }}%"></div>
                    </div>
                    <div class="position-relative">
                        <div class="position-absolute" style="left: {{ $position }}%; top: -5px; transform: translateX(-50%);">
                            <i class="bi bi-caret-down-fill text-primary" style="font-size: 1.5rem;"></i>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mt-2">
                        <small>{{ $minRange }}°C</small>
                        <small><strong>Current: {{ number_format($currentTemp, 1) }}°C</strong></small>
                        <small>{{ $maxRange }}°C</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="col-md-4">
        <!-- Related Anomalies -->
        @if($reading->anomalies->count() > 0)
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-exclamation-triangle"></i> Related Anomalies
                </h5>
            </div>
            <div class="card-body">
                @foreach($reading->anomalies as $anomaly)
                <div class="alert alert-{{ $anomaly->severity == 'critical' ? 'danger' : ($anomaly->severity == 'high' ? 'warning' : 'info') }} mb-2">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <strong>{{ $anomaly->type_name }}</strong><br>
                            <small>{{ $anomaly->description }}</small>
                        </div>
                        <span class="badge bg-{{ $anomaly->severity_color }}">
                            {{ ucfirst($anomaly->severity) }}
                        </span>
                    </div>
                    <div class="mt-2">
                        <a href="{{ route('anomalies.show', $anomaly) }}" class="btn btn-sm btn-outline-primary">
                            View Details
                        </a>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Nearby Readings -->
        @if($nearbyReadings->count() > 0)
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-clock-history"></i> Nearby Readings
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Temperature</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($nearbyReadings as $nearby)
                            <tr>
                                <td>{{ $nearby->recorded_at->format('H:i:s') }}</td>
                                <td>{{ number_format($nearby->temperature, 1) }}°C</td>
                                <td>
                                    <span class="badge bg-{{ $nearby->status == 'critical' ? 'danger' : ($nearby->status == 'warning' ? 'warning' : 'success') }}">
                                        {{ ucfirst($nearby->status) }}
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

        <!-- Quick Actions -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-lightning"></i> Quick Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('machines.show', $reading->machine) }}" class="btn btn-outline-primary">
                        <i class="bi bi-cpu"></i> View Machine Details
                    </a>
                    <a href="{{ route('machines.temperature-history', $reading->machine) }}" class="btn btn-outline-info">
                        <i class="bi bi-graph-up"></i> Temperature History
                    </a>
                    @if($reading->reading_type === 'manual')
                    <form method="POST" action="{{ route('temperature.destroy', $reading) }}"
                          onsubmit="return confirm('Are you sure you want to delete this reading?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger">
                            <i class="bi bi-trash"></i> Delete Reading
                        </button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
