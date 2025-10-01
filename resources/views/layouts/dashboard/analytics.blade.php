@extends('layouts.app')

@section('title', 'Advanced Analytics')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="bi bi-graph-up text-primary"></i> Advanced Analytics
    </h1>
    <div class="btn-group">
        <button class="btn btn-success" onclick="exportAnalytics()">
            <i class="bi bi-download"></i> Export Report
        </button>
    </div>
</div>

<!-- Filter Section -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-funnel"></i> Analytics Filters
        </h5>
    </div>
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
                <label for="machine_id" class="form-label">Machine</label>
                <select name="machine_id" id="machine_id" class="form-select">
                    <option value="">All Machines</option>
                    @foreach($machines as $machine)
                        <option value="{{ $machine->id }}"
                            {{ request('machine_id') == $machine->id ? 'selected' : '' }}>
                            {{ $machine->name }} ({{ $machine->branch->name }})
                        </option>
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
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Analyze
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Analytics Charts -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-graph-up"></i> Temperature Trends
                </h5>
                <button class="btn btn-outline-light btn-sm" onclick="downloadChart('trendsChart', 'temperature_trends')">
                    <i class="bi bi-download"></i> Download
                </button>
            </div>
            <div class="card-body">
                <canvas id="trendsChart" height="100"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-pie-chart"></i> Performance Distribution
                </h5>
            </div>
            <div class="card-body">
                <canvas id="performanceChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Seasonal Analysis -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-calendar3"></i> Seasonal Analysis
                </h5>
            </div>
            <div class="card-body">
                <canvas id="seasonalChart" height="80"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Branch Comparison -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-bar-chart"></i> Branch Performance Comparison
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Branch</th>
                                <th>Machines</th>
                                <th>Avg Temperature</th>
                                <th>Total Readings</th>
                                <th>Anomalies</th>
                                <th>Performance Score</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($performanceComparison as $branch)
                                <tr>
                                    <td><strong>{{ $branch['branch_name'] }}</strong></td>
                                    <td>{{ $branch['machine_count'] }}</td>
                                    <td>{{ number_format($branch['avg_temperature'] ?? 0, 1) }}°C</td>
                                    <td>{{ number_format($branch['total_readings']) }}</td>
                                    <td>
                                        <span class="badge bg-{{ $branch['anomaly_count'] > 0 ? 'warning' : 'success' }}">
                                            {{ $branch['anomaly_count'] }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-{{ $branch['performance_score'] >= 80 ? 'success' : ($branch['performance_score'] >= 60 ? 'warning' : 'danger') }}"
                                                 style="width: {{ $branch['performance_score'] }}%">
                                                {{ number_format($branch['performance_score'], 1) }}%
                                            </div>
                                        </div>
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

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Temperature Trends Chart
    const trendsCtx = document.getElementById('trendsChart').getContext('2d');
    const analyticsData = @json($analyticsData);

    new Chart(trendsCtx, {
        type: 'line',
        data: {
            labels: analyticsData.map(d => d.date || d.time),
            datasets: [{
                label: 'Average Temperature',
                data: analyticsData.map(d => d.avg_temperature),
                borderColor: '#4facfe',
                backgroundColor: 'rgba(79, 172, 254, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Temperature Trends Analysis'
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

    // Performance Distribution Chart
    const performanceCtx = document.getElementById('performanceChart').getContext('2d');
    const performanceData = @json($performanceComparison);

    new Chart(performanceCtx, {
        type: 'doughnut',
        data: {
            labels: performanceData.map(d => d.branch_name),
            datasets: [{
                data: performanceData.map(d => d.performance_score),
                backgroundColor: [
                    '#4facfe', '#ff6b6b', '#51cf66', '#ffd93d', '#ff8cc8',
                    '#74c0fc', '#ff922b', '#9c88ff', '#69db7c', '#ffa8a8'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Seasonal Analysis Chart
    const seasonalCtx = document.getElementById('seasonalChart').getContext('2d');
    const seasonalData = @json($seasonalAnalysis);

    new Chart(seasonalCtx, {
        type: 'bar',
        data: {
            labels: seasonalData.map(d => d.month),
            datasets: [{
                label: 'Average Temperature',
                data: seasonalData.map(d => d.avg_temperature),
                backgroundColor: 'rgba(79, 172, 254, 0.8)',
                borderColor: '#4facfe',
                borderWidth: 2
            }, {
                label: 'Max Temperature',
                data: seasonalData.map(d => d.max_temperature),
                backgroundColor: 'rgba(255, 107, 107, 0.8)',
                borderColor: '#ff6b6b',
                borderWidth: 2
            }, {
                label: 'Min Temperature',
                data: seasonalData.map(d => d.min_temperature),
                backgroundColor: 'rgba(81, 207, 102, 0.8)',
                borderColor: '#51cf66',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Seasonal Temperature Patterns'
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

function downloadChart(chartId, filename) {
    const canvas = document.getElementById(chartId);
    if (canvas) {
        const link = document.createElement('a');
        link.download = filename + '_' + new Date().toISOString().slice(0, 10) + '.png';
        link.href = canvas.toDataURL();
        link.click();
    }
}

function exportAnalytics() {
    const params = new URLSearchParams(window.location.search);
    window.open('/analytics/export?' + params.toString(), '_blank');
}
</script>
@endpush
