@extends('layouts.app')

@section('title', 'Advanced Analytics')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="bi bi-graph-up text-primary"></i> Advanced Analytics
        </h1>
    </div>

    <!-- Filter Section -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="bi bi-funnel"></i> Analytics Filters
            </h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3" id="analyticsForm">
                <div class="col-md-3">
                    <label for="branch_id" class="form-label">Branch</label>
                    <select name="branch_id" id="branch_id" class="form-select">
                        <option value="">All Branches</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="machine_id" class="form-label">Machine</label>
                    <select name="machine_id" id="machine_id" class="form-select">
                        <option value="">All Machines</option>
                        @foreach ($machines as $machine)
                            <option value="{{ $machine->id }}"
                                {{ request('machine_id') == $machine->id ? 'selected' : '' }}
                                data-branch-id="{{ $machine->branch_id }}">
                                {{ $machine->name }} ({{ $machine->branch->name }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="date_from" class="form-label">From Date</label>
                    <input type="date" name="date_from" id="date_from" class="form-control"
                        value="{{ request('date_from', now()->subDays(30)->format('Y-m-d')) }}">
                </div>
                <div class="col-md-2">
                    <label for="date_to" class="form-label">To Date</label>
                    <input type="date" name="date_to" id="date_to" class="form-control"
                        value="{{ request('date_to', now()->format('Y-m-d')) }}">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Analyze
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if(isset($analyticsData) && count($analyticsData) > 0)
    @if($analyticsData->contains('is_demo', true))
    <div class="alert alert-warning">
        <i class="bi bi-info-circle"></i>
        <strong>Demo Mode:</strong> Showing demonstration data.
        Add temperature readings to see real analytics.
    </div>
    @endif
@endif

    <!-- DEBUG: Tampilkan informasi data -->
    @if(request()->hasAny(['branch_id', 'machine_id', 'date_from', 'date_to']))
        <div class="alert alert-info mb-4">
            <small>
                <strong>Debug Info:</strong>
                Analytics Data: {{ isset($analyticsData) && count($analyticsData) > 0 ? count($analyticsData) . ' records' : 'Empty' }} |
                Seasonal: {{ isset($seasonalAnalysis) && count($seasonalAnalysis) > 0 ? count($seasonalAnalysis) . ' records' : 'Empty' }} |
                Performance: {{ isset($performanceComparison) && count($performanceComparison) > 0 ? count($performanceComparison) . ' records' : 'Empty' }}
                @if(isset($analyticsData) && count($analyticsData) > 0)
                | Date Range: {{ $analyticsData->first()['date'] ?? 'N/A' }} to {{ $analyticsData->last()['date'] ?? 'N/A' }}
                @endif
            </small>
        </div>

        <!-- DEBUG: Data Sample dengan Tampilan yang Lebih Menarik -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-database"></i> Data Preview
                    <small class="text-muted">(First {{ min(3, count($analyticsData ?? [])) }} records)</small>
                </h5>
                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#dataSampleCollapse">
                    <i class="bi bi-chevron-down"></i> Toggle View
                </button>
            </div>
            <div class="collapse show" id="dataSampleCollapse">
                <div class="card-body">
                    <div class="row">
                        @if(isset($analyticsData) && count($analyticsData) > 0)
                        <div class="col-md-4 mb-3">
                            <div class="card h-100">
                                <div class="card-header bg-primary text-white py-2">
                                    <h6 class="mb-0">
                                        <i class="bi bi-thermometer-half"></i> Analytics Data
                                        <span class="badge bg-light text-primary ms-2">{{ count($analyticsData) }} total</span>
                                    </h6>
                                </div>
                                <div class="card-body p-0">
                                    <div class="list-group list-group-flush">
                                        @foreach($analyticsData->take(3) as $index => $data)
                                        <div class="list-group-item">
                                            <div class="d-flex w-100 justify-content-between">
                                                <div>
                                                    <small class="text-muted">#{{ $index + 1 }}</small>
                                                    <strong class="d-block">
                                                        {{ $data['date'] ?? 'No Date' }}
                                                        @if(isset($data['is_demo']) && $data['is_demo'])
                                                        <span class="badge bg-warning ms-1">Demo</span>
                                                        @endif
                                                    </strong>
                                                </div>
                                                <div class="text-end">
                                                    <span class="badge bg-info rounded-pill">
                                                        {{ number_format($data['avg_temperature'] ?? 0, 1) }}°C
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="mt-2">
                                                <div class="row small">
                                                    <div class="col-6">
                                                        <span class="text-muted">Min:</span>
                                                        <strong class="text-primary">{{ number_format($data['min_temperature'] ?? 0, 1) }}°C</strong>
                                                    </div>
                                                    <div class="col-6">
                                                        <span class="text-muted">Max:</span>
                                                        <strong class="text-danger">{{ number_format($data['max_temperature'] ?? 0, 1) }}°C</strong>
                                                    </div>
                                                </div>
                                                @if(isset($data['readings_count']))
                                                <div class="mt-1">
                                                    <span class="text-muted">Readings:</span>
                                                    <span class="badge bg-secondary">{{ $data['readings_count'] }}</span>
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if(isset($seasonalAnalysis) && count($seasonalAnalysis) > 0)
                        <div class="col-md-4 mb-3">
                            <div class="card h-100">
                                <div class="card-header bg-success text-white py-2">
                                    <h6 class="mb-0">
                                        <i class="bi bi-calendar3"></i> Seasonal Analysis
                                        <span class="badge bg-light text-success ms-2">{{ count($seasonalAnalysis) }} months</span>
                                    </h6>
                                </div>
                                <div class="card-body p-0">
                                    <div class="list-group list-group-flush">
                                        @foreach($seasonalAnalysis->take(3) as $index => $data)
                                        <div class="list-group-item">
                                            <div class="d-flex w-100 justify-content-between align-items-start">
                                                <div>
                                                    <span class="badge bg-secondary mb-1">{{ $data['month'] ?? 'Unknown' }}</span>
                                                    <div class="mt-2">
                                                        <div class="d-flex align-items-center mb-1">
                                                            <i class="bi bi-thermometer-low text-info me-2"></i>
                                                            <small>Avg: <strong>{{ number_format($data['avg_temperature'] ?? 0, 1) }}°C</strong></small>
                                                        </div>
                                                        <div class="d-flex align-items-center mb-1">
                                                            <i class="bi bi-arrow-up text-danger me-2"></i>
                                                            <small>Max: <strong>{{ number_format($data['max_temperature'] ?? 0, 1) }}°C</strong></small>
                                                        </div>
                                                        <div class="d-flex align-items-center">
                                                            <i class="bi bi-arrow-down text-primary me-2"></i>
                                                            <small>Min: <strong>{{ number_format($data['min_temperature'] ?? 0, 1) }}°C</strong></small>
                                                        </div>
                                                    </div>
                                                </div>
                                                @if(isset($data['trend']))
                                                <div>
                                                    @if($data['trend'] == 'up')
                                                    <span class="badge bg-danger">
                                                        <i class="bi bi-arrow-up"></i> Up
                                                    </span>
                                                    @elseif($data['trend'] == 'down')
                                                    <span class="badge bg-primary">
                                                        <i class="bi bi-arrow-down"></i> Down
                                                    </span>
                                                    @else
                                                    <span class="badge bg-secondary">
                                                        <i class="bi bi-dash"></i> Stable
                                                    </span>
                                                    @endif
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if(isset($performanceComparison) && count($performanceComparison) > 0)
                        <div class="col-md-4 mb-3">
                            <div class="card h-100">
                                <div class="card-header bg-warning text-dark py-2">
                                    <h6 class="mb-0">
                                        <i class="bi bi-trophy"></i> Performance Comparison
                                        <span class="badge bg-light text-warning ms-2">{{ count($performanceComparison) }} branches</span>
                                    </h6>
                                </div>
                                <div class="card-body p-0">
                                    <div class="list-group list-group-flush">
                                        @foreach($performanceComparison->take(3) as $index => $data)
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <strong>{{ $data['branch_name'] ?? 'Unknown Branch' }}</strong>
                                                <div class="text-end">
                                                    <span class="badge bg-{{ $data['performance_score'] >= 80 ? 'success' : ($data['performance_score'] >= 60 ? 'warning' : 'danger') }}">
                                                        {{ number_format($data['performance_score'] ?? 0, 1) }}%
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="row small g-2">
                                                <div class="col-6">
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-cpu text-info me-1"></i>
                                                        <small>Machines:</small>
                                                    </div>
                                                    <strong>{{ $data['machine_count'] ?? 0 }}</strong>
                                                </div>
                                                <div class="col-6">
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-thermometer-half text-primary me-1"></i>
                                                        <small>Avg Temp:</small>
                                                    </div>
                                                    <strong>{{ number_format($data['avg_temperature'] ?? 0, 1) }}°C</strong>
                                                </div>
                                                <div class="col-6">
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-list-check text-success me-1"></i>
                                                        <small>Readings:</small>
                                                    </div>
                                                    <strong>{{ number_format($data['total_readings'] ?? 0) }}</strong>
                                                </div>
                                                <div class="col-6">
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-exclamation-triangle text-danger me-1"></i>
                                                        <small>Anomalies:</small>
                                                    </div>
                                                    <strong>{{ $data['anomaly_count'] ?? 0 }}</strong>
                                                </div>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Raw JSON View (Collapsible) -->
                    <div class="mt-4">
                        <div class="accordion" id="rawDataAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#rawDataCollapse">
                                        <i class="bi bi-code me-2"></i> View Raw JSON Data
                                    </button>
                                </h2>
                                <div id="rawDataCollapse" class="accordion-collapse collapse" data-bs-parent="#rawDataAccordion">
                                    <div class="accordion-body">
                                        <div class="row">
                                            @if(isset($analyticsData) && count($analyticsData) > 0)
                                            <div class="col-md-4">
                                                <strong>Analytics Data:</strong>
                                                <pre class="small bg-light p-2 rounded" style="max-height: 200px; overflow: auto;">{{ json_encode($analyticsData->take(3), JSON_PRETTY_PRINT) }}</pre>
                                            </div>
                                            @endif
                                            @if(isset($seasonalAnalysis) && count($seasonalAnalysis) > 0)
                                            <div class="col-md-4">
                                                <strong>Seasonal Data:</strong>
                                                <pre class="small bg-light p-2 rounded" style="max-height: 200px; overflow: auto;">{{ json_encode($seasonalAnalysis->take(3), JSON_PRETTY_PRINT) }}</pre>
                                            </div>
                                            @endif
                                            @if(isset($performanceComparison) && count($performanceComparison) > 0)
                                            <div class="col-md-4">
                                                <strong>Comparison Data:</strong>
                                                <pre class="small bg-light p-2 rounded" style="max-height: 200px; overflow: auto;">{{ json_encode($performanceComparison->take(3), JSON_PRETTY_PRINT) }}</pre>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if (request()->hasAny(['branch_id', 'machine_id', 'date_from', 'date_to']))

        <!-- Analytics Charts -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-graph-up"></i> Temperature Trends
                            @if(isset($analyticsData) && count($analyticsData) > 0)
                                <small class="text-muted">({{ count($analyticsData) }} days)</small>
                                @if($analyticsData->contains('is_demo', true))
                                <span class="badge bg-warning ms-2">Includes Demo Data</span>
                                @endif
                            @endif
                        </h5>
                        @if(isset($analyticsData) && count($analyticsData) > 0)
                        <button class="btn btn-outline-secondary btn-sm"
                            onclick="downloadChart('trendsChart', 'temperature_trends')">
                            <i class="bi bi-download"></i> Download
                        </button>
                        @endif
                    </div>
                    <div class="card-body">
                        @if (isset($analyticsData) && count($analyticsData) > 0)
                            <div id="chartDebug" class="small text-muted mb-2"></div>
                            <canvas id="trendsChart" height="100" ></canvas>
                        @else
                            <div class="text-center py-4">
                                <i class="bi bi-exclamation-circle text-muted display-4"></i>
                                <p class="mt-2 text-muted">No temperature data available for selected filters</p>
                                <small class="text-muted">Check if there are temperature readings in the selected date range</small>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-pie-chart"></i> Performance Distribution
                            @if(isset($performanceComparison) && count($performanceComparison) > 0)
                                <small class="text-muted">({{ count($performanceComparison) }} branches)</small>
                            @endif
                        </h5>
                    </div>
                    <div class="card-body">
                        @if (isset($performanceComparison) && count($performanceComparison) > 0)
                            <canvas id="performanceChart"></canvas>
                        @else
                            <div class="text-center py-4">
                                <i class="bi bi-exclamation-circle text-muted display-4"></i>
                                <p class="mt-2 text-muted">No performance data available</p>
                                <small class="text-muted">Branch comparison data not available</small>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Seasonal Analysis -->
        @if (isset($seasonalAnalysis) && count($seasonalAnalysis) > 0)
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="bi bi-calendar3"></i> Seasonal Analysis
                                <small class="text-muted">({{ count($seasonalAnalysis) }} months)</small>
                                @if($seasonalAnalysis->contains('is_demo', true))
                                <span class="badge bg-warning ms-2">Includes Demo Data</span>
                                @endif
                            </h5>
                        </div>
                        <div class="card-body">
                            <canvas id="seasonalChart" style="width: 100%; height: 350px;"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Branch Comparison -->
        @if (isset($performanceComparison) && count($performanceComparison) > 0)
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
                                        @foreach ($performanceComparison as $branch)
                                            <tr>
                                                <td><strong>{{ $branch['branch_name'] }}</strong></td>
                                                <td>{{ $branch['machine_count'] }}</td>
                                                <td>{{ number_format($branch['avg_temperature'] ?? 0, 1) }}°C</td>
                                                <td>{{ number_format($branch['total_readings'] ?? 0) }}</td>
                                                <td>
                                                    <span
                                                        class="badge bg-{{ ($branch['anomaly_count'] ?? 0) > 0 ? 'warning' : 'success' }}">
                                                        {{ $branch['anomaly_count'] ?? 0 }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="progress" style="height: 20px;">
                                                        <div class="progress-bar bg-{{ ($branch['performance_score'] ?? 0) >= 80 ? 'success' : (($branch['performance_score'] ?? 0) >= 60 ? 'warning' : 'danger') }}"
                                                            style="width: {{ $branch['performance_score'] ?? 0 }}%">
                                                            {{ number_format($branch['performance_score'] ?? 0, 1) }}%
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
        @else
            <div class="card mb-4">
                <div class="card-body text-center py-4">
                    <i class="bi bi-building text-muted display-4"></i>
                    <p class="mt-2 text-muted">No branch comparison data available</p>
                    <small class="text-muted">Ensure branches have machines and temperature data</small>
                </div>
            </div>
        @endif

    @else
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-graph-up display-4 text-muted"></i>
                <h4 class="mt-3">Select Filters to View Analytics</h4>
                <p class="text-muted">Choose branch, machines, and date range to generate analytics data.</p>
            </div>
        </div>
    @endif

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('=== ANALYTICS PAGE LOADED ===');

            @if (request()->hasAny(['branch_id', 'machine_id', 'date_from', 'date_to']))

                // Initialize machine filtering
                initializeMachineFiltering();

                @if (isset($analyticsData) && count($analyticsData) > 0)
                    console.log('Analytics data available:', @json($analyticsData->count()));
                    setTimeout(() => {
                        initializeTrendsChart();
                    }, 100);
                @else
                    console.log('No analytics data available for trends chart');
                @endif

                @if (isset($performanceComparison) && count($performanceComparison) > 0)
                    console.log('Performance comparison data available:', @json($performanceComparison));
                    setTimeout(() => {
                        initializePerformanceChart();
                    }, 200);
                @else
                    console.log('No performance comparison data available');
                @endif

                @if (isset($seasonalAnalysis) && count($seasonalAnalysis) > 0)
                    console.log('Seasonal analysis data available:', @json($seasonalAnalysis->count()));
                    setTimeout(() => {
                        initializeSeasonalChart();
                    }, 300);
                @else
                    console.log('No seasonal analysis data available');
                @endif

            @endif
        });

        function initializeMachineFiltering() {
            const branchSelect = document.getElementById('branch_id');
            const machineSelect = document.getElementById('machine_id');

            if (!branchSelect || !machineSelect) {
                console.log('Machine filtering elements not found');
                return;
            }

            branchSelect.addEventListener('change', function() {
                const branchId = this.value;
                const allOptions = machineSelect.querySelectorAll('option');

                allOptions.forEach(option => {
                    if (option.value === '') {
                        option.style.display = '';
                        return;
                    }

                    const optionBranchId = option.getAttribute('data-branch-id');
                    if (branchId === '' || optionBranchId === branchId) {
                        option.style.display = '';
                    } else {
                        option.style.display = 'none';
                    }
                });

                // Reset to first visible option
                machineSelect.value = '';
            });
        }

        function initializeTrendsChart() {
            const trendsCtx = document.getElementById('trendsChart');
            if (!trendsCtx) {
                console.error('Trends chart canvas not found');
                return;
            }

            const analyticsData = @json($analyticsData ?? []);

            if (analyticsData.length === 0) {
                console.error('No data for trends chart');
                return;
            }

            // Format data untuk chart
            const chartLabels = analyticsData.map(d => {
                if (d.date) {
                    try {
                        return new Date(d.date).toLocaleDateString('id-ID', {
                            day: '2-digit',
                            month: 'short'
                        });
                    } catch (e) {
                        return d.date;
                    }
                }
                return 'Unknown Date';
            });

            const chartData = analyticsData.map(d => d.avg_temperature || 0);

            console.log('Trends Chart Data:', {
                labels: chartLabels,
                data: chartData
            });

            try {
                // Destroy existing chart if any
                if (window.trendsChartInstance) {
                    window.trendsChartInstance.destroy();
                }

                window.trendsChartInstance = new Chart(trendsCtx, {
                    type: 'line',
                    data: {
                        labels: chartLabels,
                        datasets: [{
                            label: 'Average Temperature (°C)',
                            data: chartData,
                            borderColor: '#4facfe',
                            backgroundColor: 'rgba(79, 172, 254, 0.1)',
                            tension: 0.4,
                            fill: true,
                            pointBackgroundColor: '#4facfe',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            pointRadius: 3,
                            pointHoverRadius: 5
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Temperature Trends Analysis'
                            },
                            tooltip: {
                                mode: 'index',
                                intersect: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: false,
                                title: {
                                    display: true,
                                    text: 'Temperature (°C)'
                                }
                            },
                            x: {
                                ticks: {
                                    maxTicksLimit: 10
                                }
                            }
                        }
                    }
                });

                console.log('✓ Trends chart initialized successfully');
            } catch (error) {
                console.error('✗ Error creating trends chart:', error);
            }
        }

        function initializePerformanceChart() {
            console.log('Initializing performance chart...');

            const performanceCtx = document.getElementById('performanceChart');
            if (!performanceCtx) {
                console.error('Performance chart canvas not found');
                return;
            }

            const performanceData = @json($performanceComparison ?? []);

            console.log('Performance Chart Data:', performanceData);

            if (performanceData.length === 0) {
                console.error('No data for performance chart');
                return;
            }

            // Pastikan ada data yang valid untuk chart
            const validData = performanceData.filter(branch =>
                branch.performance_score !== undefined &&
                branch.performance_score !== null &&
                branch.performance_score > 0
            );

            if (validData.length === 0) {
                console.error('No valid performance scores for chart');
                // Fallback: buat data dummy untuk testing
                createFallbackPerformanceChart(performanceCtx);
                return;
            }

            try {
                // Destroy existing chart if any
                if (window.performanceChartInstance) {
                    window.performanceChartInstance.destroy();
                }

                const labels = validData.map(d => d.branch_name || 'Unknown Branch');
                const data = validData.map(d => Math.max(0, Math.min(100, d.performance_score || 0)));

                console.log('Performance Chart Final Data:', { labels, data });

                window.performanceChartInstance = new Chart(performanceCtx, {
                    type: 'doughnut',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: data,
                            backgroundColor: [
                                '#4facfe', '#ff6b6b', '#51cf66', '#ffd93d', '#ff8cc8',
                                '#74c0fc', '#ff922b', '#9c88ff', '#69db7c', '#ffa8a8'
                            ],
                            borderWidth: 2,
                            borderColor: '#ffffff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    boxWidth: 12,
                                    padding: 15,
                                    font: {
                                        size: 11
                                    }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.parsed || 0;
                                        return `${label}: ${value.toFixed(1)}%`;
                                    }
                                }
                            }
                        },
                        cutout: '60%'
                    }
                });

                console.log('✓ Performance chart initialized successfully');
            } catch (error) {
                console.error('✗ Error creating performance chart:', error);
                // Fallback jika error
                createFallbackPerformanceChart(performanceCtx);
            }
        }

        function createFallbackPerformanceChart(ctx) {
            console.log('Creating fallback performance chart...');

            try {
                const fallbackChart = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Demo Branch'],
                        datasets: [{
                            data: [85],
                            backgroundColor: ['#4facfe'],
                            borderWidth: 2,
                            borderColor: '#ffffff'
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return `Demo: ${context.parsed}%`;
                                    }
                                }
                            },
                            title: {
                                display: true,
                                text: 'Demo Data'
                            }
                        },
                        cutout: '60%'
                    }
                });
                console.log('✓ Fallback performance chart created');
            } catch (error) {
                console.error('✗ Fallback chart also failed:', error);
            }
        }

        function initializeSeasonalChart() {
            console.log('Initializing seasonal chart...');

            const seasonalCtx = document.getElementById('seasonalChart');
            if (!seasonalCtx) {
                console.error('Seasonal chart canvas not found');
                return;
            }

            const seasonalData = @json($seasonalAnalysis ?? []);

            console.log('Seasonal Chart Data:', seasonalData);

            if (seasonalData.length === 0) {
                console.error('No data for seasonal chart');
                return;
            }

            try {
                // Destroy existing chart if any
                if (window.seasonalChartInstance) {
                    window.seasonalChartInstance.destroy();
                }

                const labels = seasonalData.map(d => d.month || 'Unknown');
                const avgTemps = seasonalData.map(d => d.avg_temperature || 0);
                const minTemps = seasonalData.map(d => d.min_temperature || 0);
                const maxTemps = seasonalData.map(d => d.max_temperature || 0);

                window.seasonalChartInstance = new Chart(seasonalCtx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Average Temperature (°C)',
                            data: avgTemps,
                            backgroundColor: 'rgba(79, 172, 254, 0.8)',
                            borderColor: '#4facfe',
                            borderWidth: 2
                        }, {
                            label: 'Max Temperature (°C)',
                            data: maxTemps,
                            backgroundColor: 'rgba(255, 107, 107, 0.8)',
                            borderColor: '#ff6b6b',
                            borderWidth: 2
                        }, {
                            label: 'Min Temperature (°C)',
                            data: minTemps,
                            backgroundColor: 'rgba(81, 207, 102, 0.8)',
                            borderColor: '#51cf66',
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
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

                console.log('✓ Seasonal chart initialized successfully');
            } catch (error) {
                console.error('✗ Error creating seasonal chart:', error);
            }
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

        function exportAnalytics() {
            const params = new URLSearchParams(window.location.search);
            window.open('/analytics/export?' + params.toString(), '_blank');
        }
    </script>
@endpush
