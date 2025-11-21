@extends('layouts.app')

@section('title', 'Anomaly Details')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="bi bi-exclamation-triangle text-warning"></i> Anomaly Details
        </h1>
        <div class="btn-group">
            <a href="{{ route('anomalies.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to List
            </a>
            @if ($anomaly->status === 'new')
                <button class="btn btn-warning" onclick="acknowledgeAnomaly({{ $anomaly->id }})">
                    <i class="bi bi-check"></i> Acknowledge
                </button>
            @endif
            @if (in_array($anomaly->status, ['new', 'acknowledged', 'investigating']))
                <button class="btn btn-success" onclick="resolveAnomaly({{ $anomaly->id }})">
                    <i class="bi bi-check-circle"></i> Resolve
                </button>
            @endif
        </div>
    </div>

    <div class="row">
        <!-- Anomaly Details -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-info-circle"></i> Anomaly Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Detected At:</strong></td>
                                    <td>{{ $anomaly->detected_at->format('d/m/Y H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Type:</strong></td>
                                    <td>
                                        <span class="badge bg-info">{{ $anomaly->type_name }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Severity:</strong></td>
                                    <td>
                                        <span class="badge bg-{{ $anomaly->severity_color }}">
                                            {{ ucfirst($anomaly->severity) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Status:</strong></td>
                                    <td>
                                        <span class="badge bg-{{ $anomaly->status_color }}">
                                            {{ ucfirst(str_replace('_', ' ', $anomaly->status)) }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Machine:</strong></td>
                                    <td>{{ $anomaly->machine->name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Branch:</strong></td>
                                    <td>{{ $anomaly->machine->branch->name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Temperature:</strong></td>
                                    <td>
                                        @php
                                            $reading = $anomaly->temperatureReading;
                                            $temp = optional($reading)->temperature_value;

                                            if ($temp === null) {
                                                $color = 'secondary';
                                            } elseif ($temp >= $anomaly->machine->temp_critical_max) {
                                                $color = 'danger';
                                            } elseif ($temp >= $anomaly->machine->temp_max_normal) {
                                                $color = 'warning';
                                            } else {
                                                $color = 'success';
                                            }
                                        @endphp
                                        <span class="h5 text-{{ $color }}">
                                            {{ $temp ? number_format($temp, 1) : 'N/A' }}°C
                                        </span>
                                    </td>
                                </tr>
                                @if ($anomaly->acknowledged_at)
                                    <tr>
                                        <td><strong>Acknowledged:</strong></td>
                                        <td>{{ $anomaly->acknowledged_at->format('d/m/Y H:i:s') }}</td>
                                    </tr>
                                @endif
                            </table>
                        </div>
                    </div>

                    <div class="mt-4">
                        <h6>Description:</h6>
                        <div class="alert alert-info">
                            {{ $anomaly->description }}
                        </div>
                    </div>

                    @if ($anomaly->possible_causes)
                        <div class="mt-3">
                            <h6>Possible Causes:</h6>
                            <div class="alert alert-warning">
                                {{ $anomaly->possible_causes }}
                            </div>
                        </div>
                    @endif

                    @if ($anomaly->recommendations)
                        <div class="mt-3">
                            <h6>Recommendations:</h6>
                            <div class="alert alert-success">
                                {{ $anomaly->recommendations }}
                            </div>
                        </div>
                    @endif

                    @if ($anomaly->acknowledged_by)
                        <div class="mt-3">
                            <h6>Acknowledgment:</h6>
                            <p>Acknowledged by <strong>{{ $anomaly->acknowledged_by }}</strong> on
                                {{ $anomaly->acknowledged_at->format('d/m/Y H:i:s') }}</p>
                        </div>
                    @endif

                    @if ($anomaly->resolution_notes)
                        <div class="mt-3">
                            <h6>Resolution Notes:</h6>
                            <div class="alert alert-success">
                                {{ $anomaly->resolution_notes }}
                            </div>
                            @if ($anomaly->resolved_at)
                                <p><small>Resolved on {{ $anomaly->resolved_at->format('d/m/Y H:i:s') }}</small></p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- Temperature Context Chart -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-graph-up"></i> Temperature Context (±2 hours)
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="contextChart" height="100"></canvas>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-md-4">
            <!-- Machine Information -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-cpu"></i> Machine Information
                    </h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td><strong>Name:</strong></td>
                            <td>{{ $anomaly->machine->name }}</td>
                        </tr>
                        <tr>
                            <td><strong>Type:</strong></td>
                            <td>{{ $anomaly->machine->type }}</td>
                        </tr>
                        <tr>
                            <td><strong>Model:</strong></td>
                            <td>{{ $anomaly->machine->model ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Normal Range:</strong></td>
                            <td>{{ $anomaly->machine->temp_min_normal }}°C to {{ $anomaly->machine->temp_max_normal }}°C
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Critical Range:</strong></td>
                            <td>{{ $anomaly->machine->temp_critical_min }}°C to
                                {{ $anomaly->machine->temp_critical_max }}°C</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Similar Anomalies -->
            @if ($similarAnomalies->count() > 0)
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-arrow-repeat"></i> Similar Anomalies
                        </h5>
                    </div>
                    <div class="card-body">
                        @foreach ($similarAnomalies as $similar)
                            <div class="border-bottom pb-2 mb-2">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong>{{ $similar->detected_at->format('d/m/Y H:i') }}</strong><br>
                                        @php
                                            $simReading = $similar->temperatureReading;
                                            $simTemp = optional($simReading)->temperature_value;

                                            if ($simTemp === null) {
                                                $simColor = 'secondary';
                                            } elseif ($simTemp >= $similar->machine->temp_critical_max) {
                                                $simColor = 'danger';
                                            } elseif ($simTemp >= $similar->machine->temp_max_normal) {
                                                $simColor = 'warning';
                                            } else {
                                                $simColor = 'success';
                                            }
                                        @endphp
                                        <small class="text-{{ $simColor }}">
                                            {{ $simTemp ? number_format($simTemp, 1) : 'N/A' }}°C
                                        </small>
                                    </div>
                                    <span class="badge bg-{{ $similar->status_color }}">
                                        {{ ucfirst($similar->status) }}
                                    </span>
                                </div>
                                <div class="mt-1">
                                    <a href="{{ route('anomalies.show', $similar) }}"
                                        class="btn btn-sm btn-outline-primary">
                                        View
                                    </a>
                                </div>
                            </div>
                        @endforeach
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
                        <a href="{{ route('machines.show', $anomaly->machine) }}" class="btn btn-outline-primary">
                            <i class="bi bi-cpu"></i> View Machine
                        </a>
                        @if ($anomaly->temperatureReading)
                            <a href="{{ route('temperature.show', $anomaly->temperatureReading->id) }}"
                                class="btn btn-outline-info">
                                <i class="bi bi-thermometer"></i> View Reading
                            </a>
                        @else
                            <button class="btn btn-outline-secondary" disabled>
                                <i class="bi bi-thermometer"></i> No Reading
                            </button>
                        @endif
                        <a href="{{ route('machines.temperature-history', $anomaly->machine) }}"
                            class="btn btn-outline-secondary">
                            <i class="bi bi-graph-up"></i> Temperature History
                        </a>
                    </div>
                </div>
            </div>
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
        let currentAnomalyId = {{ $anomaly->id }};

        // Temperature Context Chart
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('contextChart').getContext('2d');
            const relatedReadings = @json($relatedReadings);

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: relatedReadings.map(r => new Date(r.recorded_at).toLocaleTimeString()),
                    datasets: [{
                        label: 'Temperature (°C)',
                        data: relatedReadings.map(r => r.temperature),
                        borderColor: '#4facfe',
                        backgroundColor: 'rgba(79, 172, 254, 0.1)',
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: relatedReadings.map(r =>
                            r.id === {{ $anomaly->temperature_reading_id }} ? '#ff6b6b' :
                            '#4facfe'
                        ),
                        pointRadius: relatedReadings.map(r =>
                            r.id === {{ $anomaly->temperature_reading_id }} ? 8 : 4
                        )
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Temperature readings around anomaly detection time'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            title: {
                                display: true,
                                text: 'Temperature (°C)'
                            }
                        }
                    }
                }
            });
        });

        function acknowledgeAnomaly(anomalyId) {
            document.getElementById('acknowledged_by').value = '';
            new bootstrap.Modal(document.getElementById('acknowledgeModal')).show();
        }

        function resolveAnomaly(anomalyId) {
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
    </script>
@endpush
