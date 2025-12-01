@extends('layouts.app')

@section('title', 'Branch Comparison')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="bi bi-bar-chart text-primary"></i> Branch Performance Comparison
    </h1>
    {{-- <div class="btn-group">
        <button class="btn btn-success" onclick="exportComparison()">
            <i class="bi bi-download"></i> Export Report
        </button>
    </div> --}}
</div>

<!-- Performance Rankings -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-trophy"></i> Performance Rankings
                </h5>
            </div>
            <div class="card-body">
                @if($performanceRankings->count() > 0)
                    <div class="row">
                        @foreach($performanceRankings->take(3) as $index => $branch)
                            <div class="col-md-4 mb-3">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            @if($index == 0)
                                                <i class="bi bi-trophy-fill text-warning" style="font-size: 3rem;"></i>
                                            @elseif($index == 1)
                                                <i class="bi bi-award-fill text-secondary" style="font-size: 3rem;"></i>
                                            @else
                                                <i class="bi bi-award text-warning" style="font-size: 3rem;"></i>
                                            @endif
                                        </div>
                                        <h5 class="card-title">{{ $branch['branch']->name }}</h5>
                                        <h3 class="text-primary">{{ number_format($branch['performance_score'], 1) }}%</h3>
                                        <p class="text-muted mb-0">Performance Score</p>
                                        <div class="mt-2">
                                            <small class="text-muted">
                                                {{ $branch['machine_count'] }} machines •
                                                {{ number_format($branch['avg_temperature'] ?? 0, 1) }}°C avg
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="bi bi-building display-4 text-muted"></i>
                        <p class="text-muted mt-2">No branch data available for comparison.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Comparison Charts -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-pie-chart"></i> Performance Distribution
                </h5>
                <button class="btn btn-outline-light btn-sm" onclick="downloadChart('performanceChart', 'performance_distribution')">
                    <i class="bi bi-download"></i> Download
                </button>
            </div>
            <div class="card-body">
                <canvas id="performanceChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-thermometer"></i> Average Temperature
                </h5>
                <button class="btn btn-outline-light btn-sm" onclick="downloadChart('temperatureChart', 'temperature_comparison')">
                    <i class="bi bi-download"></i> Download
                </button>
            </div>
            <div class="card-body">
                <canvas id="temperatureChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Detailed Comparison Table -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-table"></i> Detailed Branch Comparison
                </h5>
            </div>
            <div class="card-body">
                @if($comparisonData->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Branch</th>
                                    <th>Machines</th>
                                    <th>Total Readings</th>
                                    <th>Avg Temperature</th>
                                    <th>Temperature Range</th>
                                    <th>Anomalies</th>
                                    <th>Anomaly Rate</th>
                                    <th>Performance Score</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($performanceRankings as $index => $branch)
                                    <tr>
                                        <td>
                                            <span class="badge bg-{{ $index < 3 ? 'warning' : 'secondary' }}">
                                                #{{ $index + 1 }}
                                            </span>
                                        </td>
                                        <td>
                                            <strong>{{ $branch['branch']->name }}</strong><br>
                                            <small class="text-muted">{{ $branch['branch']->city }}</small>
                                        </td>
                                        <td>{{ $branch['machine_count'] }}</td>
                                        <td>{{ number_format($branch['total_readings']) }}</td>
                                        <td>
                                            <strong>{{ number_format($branch['avg_temperature'] ?? 0, 1) }}°C</strong>
                                        </td>
                                        <td>
                                            <small>
                                                {{ number_format($branch['min_temperature'] ?? 0, 1) }}°C -
                                                {{ number_format($branch['max_temperature'] ?? 0, 1) }}°C
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $branch['anomaly_count'] > 0 ? 'warning' : 'success' }}">
                                                {{ $branch['anomaly_count'] }}
                                            </span>
                                        </td>
                                        <td>{{ number_format($branch['anomaly_rate'], 1) }}%</td>
                                        <td>
                                            <div class="progress" style="height: 20px; width: 100px;">
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
                @else
                    <div class="text-center py-4">
                        <i class="bi bi-inbox display-4 text-muted"></i>
                        <p class="text-muted mt-2">No comparison data available.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Branch Details Cards -->
<div class="row">
    @foreach($branches as $branch)
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="bi bi-building"></i> {{ $branch->name }}
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row text-center mb-3">
                        <div class="col-4">
                            <h5 class="text-primary mb-0">{{ $branch->machines->where('is_active', true)->count() }}</h5>
                            <small class="text-muted">Machines</small>
                        </div>
                        <div class="col-4">
                            <h5 class="text-info mb-0">
                                @php
                                    $avgTemp = $branch->temperatureReadings()->avg('temperature');
                                @endphp
                                {{ number_format($avgTemp ?? 0, 1) }}°C
                            </h5>
                            <small class="text-muted">Avg Temp</small>
                        </div>
                        <div class="col-4">
                            <h5 class="text-warning mb-0">{{ $branch->anomalies()->count() }}</h5>
                            <small class="text-muted">Anomalies</small>
                        </div>
                    </div>

                    @php
                        $branchPerformance = $performanceRankings->firstWhere('branch.id', $branch->id);
                        $performanceScore = $branchPerformance['performance_score'] ?? 0;
                    @endphp

                    <div class="progress mb-2" style="height: 8px;">
                        <div class="progress-bar bg-{{ $performanceScore >= 80 ? 'success' : ($performanceScore >= 60 ? 'warning' : 'danger') }}"
                             style="width: {{ $performanceScore }}%">
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">Performance: {{ number_format($performanceScore, 1) }}%</small>
                        <a href="{{ route('branches.show', $branch) }}" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-eye"></i> View
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const comparisonData = @json($comparisonData);

    // Performance Distribution Chart
    const performanceCtx = document.getElementById('performanceChart').getContext('2d');
    new Chart(performanceCtx, {
        type: 'doughnut',
        data: {
            labels: comparisonData.map(d => d.branch_name),
            datasets: [{
                data: comparisonData.map(d => d.performance_score),
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

    // Temperature Comparison Chart
    const temperatureCtx = document.getElementById('temperatureChart').getContext('2d');
    new Chart(temperatureCtx, {
        type: 'bar',
        data: {
            labels: comparisonData.map(d => d.branch_name),
            datasets: [{
                label: 'Average Temperature (°C)',
                data: comparisonData.map(d => d.avg_temperature || 0),
                backgroundColor: 'rgba(79, 172, 254, 0.8)',
                borderColor: '#4facfe',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
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
        link.download = `${filename}_${new Date().toISOString().slice(0, 10)}.png`;
        link.href = canvas.toDataURL();
        link.click();
    }
}

function exportComparison() {
    window.open('/branch-comparison/export', '_blank');
}
</script>
@endpush
