@extends('layouts.app')

@section('title', 'Anomaly Management')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="bi bi-exclamation-triangle text-warning"></i> Manajemen Anomali
    </h1>
    <div class="btn-group">
        <button class="btn btn-info" onclick="runAnomalyCheck()">
            <i class="bi bi-search"></i> Run Anomaly Check
        </button>
        <button class="btn btn-danger" onclick="runGlobalAnomalyCheck()">
            <i class="bi bi-lightning"></i> Global Check
        </button>
        <a href="{{ route('anomalies.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Add Anomaly
        </a>
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

<!-- Anomaly Trends Chart -->
{{-- <div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-graph-up"></i> Anomaly Trends (Last 30 Days)
                </h5>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary" onclick="updateTrendChart(7)">7 Days</button>
                    <button class="btn btn-outline-primary active" onclick="updateTrendChart(30)">30 Days</button>
                    <button class="btn btn-outline-success" onclick="downloadChart('trendChart', 'anomaly_trends')">
                        <i class="bi bi-download"></i> Download
                    </button>
                </div>
            </div>
            <div class="card-body">
                <canvas id="trendChart" height="100"></canvas>
            </div>
        </div>
    </div>
</div> --}}

<!-- Filter Section -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3" id="filterForm">
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
                                    @php
                                        $tempReading = null;
                                        if ($anomaly->temperature_reading_id) {
                                            $tempReading = \App\Models\Temperature::find($anomaly->temperature_reading_id);
                                        }
                                    @endphp
                                    @if($tempReading)
                                        <strong class="text-{{ $tempReading->temperature_value < $anomaly->machine->temp_min_normal || $tempReading->temperature_value > $anomaly->machine->temp_max_normal ? 'danger' : 'success' }}">
                                            {{ number_format($tempReading->temperature_value, 1) }}°C
                                        </strong>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
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
                                        <a href="{{ route('anomalies.edit', $anomaly) }}"
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

<!-- Modals -->
@include('anomalies.partials.acknowledge-modal')
@include('anomalies.partials.resolve-modal')

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let trendChart;
let currentAnomalyId = null;

document.addEventListener('DOMContentLoaded', function() {
    initializeTrendChart();
});

function initializeTrendChart() {
    const ctx = document.getElementById('trendChart').getContext('2d');
    const trendData = @json($trendData);

    const labels = Object.keys(trendData).map(date =>
        new Date(date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short' })
    );

    const datasets = [
        {
            label: 'Critical',
            data: Object.values(trendData).map(d => d.critical),
            backgroundColor: 'rgba(255, 65, 108, 0.8)',
            borderColor: '#ff416c',
            fill: false
        },
        {
            label: 'High',
            data: Object.values(trendData).map(d => d.high),
            backgroundColor: 'rgba(255, 147, 43, 0.8)',
            borderColor: '#ff932b',
            fill: false
        },
        {
            label: 'Medium',
            data: Object.values(trendData).map(d => d.medium),
            backgroundColor: 'rgba(255, 217, 61, 0.8)',
            borderColor: '#ffd93d',
            fill: false
        },
        {
            label: 'Low',
            data: Object.values(trendData).map(d => d.low),
            backgroundColor: 'rgba(17, 153, 142, 0.8)',
            borderColor: '#11998e',
            fill: false
        }
    ];

    trendChart = new Chart(ctx, {
        type: 'line',
        data: { labels, datasets },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Anomaly Detection Trends'
                },
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Number of Anomalies'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Date'
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    });
}

function updateTrendChart(days) {
    // Update active button
    document.querySelectorAll('.btn-group .btn').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');

    fetch(`/anomalies/chart-data?days=${days}`)
        .then(response => response.json())
        .then(data => {
            const labels = Object.keys(data).map(date =>
                new Date(date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short' })
            );

            trendChart.data.labels = labels;
            trendChart.data.datasets[0].data = Object.values(data).map(d => d.critical);
            trendChart.data.datasets[1].data = Object.values(data).map(d => d.high);
            trendChart.data.datasets[2].data = Object.values(data).map(d => d.medium);
            trendChart.data.datasets[3].data = Object.values(data).map(d => d.low);

            trendChart.update();
        })
        .catch(error => console.error('Error updating chart:', error));
}

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

function runAnomalyCheck() {
    const machineId = document.getElementById('machine_id').value;

    Swal.fire({
        title: 'Run Anomaly Check?',
        text: machineId
            ? 'Anomaly check akan dijalankan pada mesin terpilih.'
            : 'Anomaly check akan dijalankan untuk semua mesin.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, jalankan!',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#0d6efd',
        cancelButtonColor: '#6c757d'
    }).then(result => {
        if (result.isConfirmed) {

            Swal.fire({
                title: 'Processing...',
                text: 'Sedang menjalankan anomaly check...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => Swal.showLoading()
            });

            const params = machineId ? `?machine_id=${machineId}` : '';

            fetch(`/anomalies/run-check${params}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                Swal.close();

                Swal.fire({
                    icon: data.success ? 'success' : 'error',
                    title: data.success ? 'Berhasil!' : 'Gagal!',
                    text: data.message
                }).then(() => {
                    if (data.success) location.reload();
                });
            })
            .catch(error => {
                Swal.close();

                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message
                });
            });
        }
    });
}


function runGlobalAnomalyCheck() {
    Swal.fire({
        title: 'Global Anomaly Check?',
        text: 'Ini akan menjalankan anomaly detection pada semua mesin aktif. Proses mungkin memakan waktu cukup lama.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, lanjutkan!',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d'
    }).then(result => {
        if (result.isConfirmed) {

            Swal.fire({
                title: 'Processing...',
                text: 'Sedang menjalankan global anomaly check...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => Swal.showLoading()
            });

            fetch('/anomalies/run-check', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                Swal.close();

                Swal.fire({
                    icon: data.success ? 'success' : 'error',
                    title: data.success ? 'Selesai!' : 'Gagal!',
                    text: data.message
                }).then(() => {
                    if (data.success) location.reload();
                });
            })
            .catch(error => {
                Swal.close();

                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message
                });
            });
        }
    });
}

function downloadChart(chartId, filename) {
    const canvas = document.getElementById(chartId);
    if (canvas) {
        const link = document.createElement('a');
        link.download = filename + '_' + new Date().toISOString().slice(0, 10) + '.png';
        link.href = canvas.toDataURL();
        link.click();
    }
}

function exportAnomalies() {
    const params = new URLSearchParams(window.location.search);
    window.open('/anomalies/export?' + params.toString(), '_blank');
}
</script>
@endpush
