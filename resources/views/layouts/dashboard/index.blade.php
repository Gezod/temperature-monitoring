@extends('layouts.app')

@section('title', 'Dashboard - Temperature Monitoring')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="bi bi-speedometer2 text-primary"></i> Dashboard Pemantauan Suhu
    </h1>
    {{-- <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" id="auto-refresh">
        <label class="form-check-label" for="auto-refresh">Auto Refresh</label>
    </div> --}}
</div>

<!-- Overview Statistics -->
<div class="row mb-4">
    <div class="col-md-6 col-xl-4 mb-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <div class="stat-value">{{ $stats['total_branches'] }}</div>
            <div class="stat-label">Total Cabang</div>
        </div>
    </div>

    <div class="col-md-6 col-xl-4 mb-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
            <div class="stat-value">{{ $stats['total_machines'] }}</div>
            <div class="stat-label">Total Mesin</div>
        </div>
    </div>

    <div class="col-md-6 col-xl-4 mb-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);">
            <div class="stat-value">{{ $stats['active_anomalies'] }}</div>
            <div class="stat-label">Anomali Aktif</div>
        </div>
    </div>
</div>


<!-- Temperature Trends Chart -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-graph-up"></i> Tren Suhu 90 Hari Terakhir
                </h5>
                <button class="btn btn-outline-light btn-sm" onclick="downloadChart('temperatureTrendChart', 'temperature_trends')">
                    <i class="bi bi-download"></i> Download
                </button>
            </div>
            <div class="card-body">
                <canvas id="temperatureTrendChart" style="width: 100%; overflow-x: auto;"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Recent Readings and Alerts -->
<div class="row mb-4">
    <!-- Recent Temperature Readings -->
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-thermometer"></i> Pembacaan Suhu Terbaru
                </h5>
            </div>
            <div class="card-body">
                @if($recentReadings->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Mesin</th>
                                    <th>Suhu</th>
                                    <th>Status</th>
                                    <th>Waktu</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentReadings->take(8) as $reading)
                                    <tr>
                                        <td>
                                            <strong>{{ $reading['machine'] }}</strong><br>
                                            <small class="text-muted">{{ $reading['branch'] }}</small>
                                        </td>
                                        <td>
                                            <strong>{{ number_format($reading['temperature'], 1) }}°C</strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{
                                                $reading['status'] == 'critical' ? 'danger' :
                                                ($reading['status'] == 'warning' ? 'warning' : 'success')
                                            }}">
                                                {{ ucfirst($reading['status']) }}
                                            </span>
                                        </td>
                                        <td>
                                            <small>{{ $reading['recorded_at']->diffForHumans() }}</small>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted text-center py-3">Belum ada data pembacaan suhu.</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Active Anomalies -->
    {{-- <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-exclamation-triangle"></i> Anomali Aktif
                </h5>
            </div>
            <div class="card-body">
                @if($activeAnomalies->count() > 0)
                    @foreach($activeAnomalies as $anomaly)
                        <div class="alert alert-{{ $anomaly->severity == 'critical' ? 'danger' : ($anomaly->severity == 'high' ? 'warning' : 'info') }} py-2 mb-2">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong>{{ $anomaly->machine->name }}</strong> - {{ $anomaly->machine->branch->name }}<br>
                                    <small>{{ $anomaly->type_name }}: {{ number_format($anomaly->temperatureReading->temperature, 1) }}°C</small>
                                </div>
                                <small class="text-muted">{{ $anomaly->detected_at->diffForHumans() }}</small>
                            </div>
                        </div>
                    @endforeach
                @else
                    <p class="text-muted text-center py-3">Tidak ada anomali aktif saat ini.</p>
                @endif
            </div>
        </div>
    </div> --}}
{{-- </div> --}}

<!-- Branch Performance Summary -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-bar-chart"></i> Ringkasan Kinerja Cabang
                </h5>
            </div>
            <div class="card-body">
                @if($branchPerformance->count() > 0)
                    <div class="row">
                        @foreach($branchPerformance as $branch)
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body">
                                        <h6 class="card-title">{{ $branch['branch']->name }}</h6>
                                        <div class="row text-center">
                                            <div class="col-6">
                                                <div class="mb-2">
                                                    <h4 class="mb-0 text-primary">{{ $branch['machine_count'] }}</h4>
                                                    <small class="text-muted">Mesin</small>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="mb-2">
                                                    <h4 class="mb-0 text-info">{{ number_format($branch['avg_temperature'] ?? 0, 1) }}°C</h4>
                                                    <small class="text-muted">Rata-rata</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="progress mb-2" style="height: 8px;">
                                            <div class="progress-bar bg-success"
                                                 style="width: {{ $branch['performance_score'] }}%"
                                                 title="Performance Score: {{ number_format($branch['performance_score'], 1) }}%">
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <small class="text-muted">Performance: {{ number_format($branch['performance_score'], 1) }}%</small>
                                            <small class="text-muted">Anomali: {{ $branch['anomaly_count'] }}</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted text-center py-3">Belum ada data kinerja cabang.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Maintenance Alerts -->
@if($maintenanceAlerts->count() > 0)
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">
                    <i class="bi bi-tools"></i> Peringatan Maintenance (7 Hari Ke Depan)
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Mesin</th>
                                <th>Cabang</th>
                                <th>Jenis</th>
                                <th>Prioritas</th>
                                <th>Tanggal Rekomendasi</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($maintenanceAlerts as $alert)
                                <tr>
                                    <td><strong>{{ $alert->machine->name }}</strong></td>
                                    <td>{{ $alert->machine->branch->name }}</td>
                                    <td>{{ $alert->type_name }}</td>
                                    <td>
                                        <span class="badge bg-{{ $alert->priority_color }}">
                                            {{ ucfirst($alert->priority) }}
                                        </span>
                                    </td>
                                    <td>{{ $alert->recommended_date->format('d/m/Y') }}</td>
                                    <td>
                                        <a href="{{ route('maintenance.show', $alert->id) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Temperature Trends Chart
    const trendCtx = document.getElementById('temperatureTrendChart').getContext('2d');

    const trendData = @json($temperatureTrends);

    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: trendData.map(d => d.date),
            datasets: [{
                label: 'Rata-rata Harian',
                data: trendData.map(d => d.avg_temperature),
                borderColor: '#4facfe',
                backgroundColor: 'rgba(79, 172, 254, 0.1)',
                tension: 0.4,
                fill: true
            }, {
                label: 'Maximum Harian',
                data: trendData.map(d => d.max_temperature),
                borderColor: '#ff6b6b',
                backgroundColor: 'rgba(255, 107, 107, 0.1)',
                tension: 0.4,
                fill: false
            }, {
                label: 'Minimum Harian',
                data: trendData.map(d => d.min_temperature),
                borderColor: '#51cf66',
                backgroundColor: 'rgba(81, 207, 102, 0.1)',
                tension: 0.4,
                fill: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Tren Suhu Harian (°C)'
                },
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: false,
                    title: {
                        display: true,
                        text: 'Suhu (°C)'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Tanggal'
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    });

    // Auto-refresh active anomalies every 30 seconds
    setInterval(function() {
        if (document.getElementById('auto-refresh')?.checked) {
            // Would typically use AJAX to update anomalies
            console.log('Refreshing anomalies...');
        }
    }, 30000);
});
</script>
@endpush
