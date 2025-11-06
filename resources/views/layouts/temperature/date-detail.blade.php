@extends('layouts.app')

@section('title', 'Temperature Detail - ' . $date)

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="bi bi-calendar3 text-primary"></i> Detail Suhu - {{ Carbon\Carbon::parse($date)->format('d M Y') }}
        </h1>
        <div class="btn-group">
            <a href="{{ route('temperature.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Overview
            </a>
            <a href="{{ route('temperature.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Add Reading
            </a>
        </div>
    </div>

    <!-- Summary Statistics -->
    <div class="row mb-4">
        @php
            $avgTemp = $readings->avg('temperature_value');
            $minTemp = $readings->min('temperature_value');
            $maxTemp = $readings->max('temperature_value');
            $machineCount = $readings->unique('machine_id')->count();
        @endphp
        <div class="col-md-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <div class="stat-value">{{ $readings->count() }}</div>
                <div class="stat-label">Total Readings</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                <div class="stat-value">{{ number_format($avgTemp, 1) }}°C</div>
                <div class="stat-label">Average Temperature</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);">
                <div class="stat-value">{{ number_format($maxTemp, 1) }}°C</div>
                <div class="stat-label">Max Temperature</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="stat-value">{{ $machineCount }}</div>
                <div class="stat-label">Machines</div>
            </div>
        </div>
    </div>

    <!-- Temperature Chart -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-graph-up"></i> Temperature Trends - {{ Carbon\Carbon::parse($date)->format('d M Y') }}
            </h5>
            <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-primary" onclick="toggleChartType()">
                    <i class="bi bi-bar-chart"></i> Toggle Chart Type
                </button>
                <button class="btn btn-outline-success" onclick="downloadChart()">
                    <i class="bi bi-download"></i> Download Chart
                </button>
            </div>
        </div>
        <div class="card-body">
            <canvas id="temperatureChart" height="100"></canvas>
        </div>
    </div>

    <!-- Machine Data -->
    <div class="row">
        @foreach ($groupedByMachine as $machineId => $machineReadings)
            @php
                $machine = $machineReadings->first()->machine ?? null;
            @endphp
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <small class="text-muted">({{ $machine->branch->name ?? 'No Branch' }})</small>
                            {{-- <small class="text-muted">({{ $machine->branch->name }})</small> --}}
                        </h5>
                        <span class="badge bg-info">{{ $machineReadings->count() }} readings</span>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-4 text-center">
                                <h6 class="text-info mb-1">
                                    {{ number_format($machineReadings->avg('temperature_value'), 1) }}°C</h6>
                                <small class="text-muted">Average</small>
                            </div>
                            <div class="col-4 text-center">
                                <h6 class="text-success mb-1">
                                    {{ number_format($machineReadings->min('temperature_value'), 1) }}°C</h6>
                                <small class="text-muted">Min</small>
                            </div>
                            <div class="col-4 text-center">
                                <h6 class="text-danger mb-1">
                                    {{ number_format($machineReadings->max('temperature_value'), 1) }}°C</h6>
                                <small class="text-muted">Max</small>
                            </div>
                        </div>

                        <!-- Mini chart -->
                        {{-- <canvas id="machineChart-{{ $machineId }}" height="60"></canvas> --}}

                        <!-- Table -->
                        <div class="table-responsive mt-3" style="max-height: 300px;">
                            <table class="table table-sm table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Time</th>
                                        <th>Temperature</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($machineReadings->sortBy('reading_time') as $reading)
                                        <tr>
                                            <td>{{ $reading->reading_time }}</td>
                                            <td>
                                                <strong
                                                    class="text-{{ $machine &&
                                                    ($reading->temperature_value < ($machine->temp_min_normal ?? 0) ||
                                                        $reading->temperature_value > ($machine->temp_max_normal ?? 100))
                                                        ? 'danger'
                                                        : 'success' }}">
                                                    {{ number_format($reading->temperature_value, 1) }}°C
                                                </strong>
                                            </td>
                                            <td>
                                                <span
                                                    class="badge bg-{{ $reading->validation_status === 'imported' ? 'success' : ($reading->validation_status === 'pending' ? 'warning' : 'info') }}">
                                                    {{ ucfirst($reading->validation_status) }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="{{ route('temperature.show', $reading->id) }}"
                                                        class="btn btn-outline-info"><i class="bi bi-eye"></i></a>
                                                    <a href="{{ route('temperature.edit', $reading->id) }}"
                                                        class="btn btn-outline-warning"><i class="bi bi-pencil"></i></a>
                                                    <form method="POST"
                                                        action="{{ route('temperature.destroy', $reading->id) }}"
                                                        style="display:inline;" onsubmit="return confirm('Are you sure?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-outline-danger"><i
                                                                class="bi bi-trash"></i></button>
                                                    </form>
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
        @endforeach
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>
    <script>
        let mainChart;
        let currentChartType = 'line';

        // Simpan warna per mesin
        const machineColors = {};

        function getColor(machine) {
            if (!machineColors[machine]) {
                const palette = [
                    'rgba(79, 172, 254, 1)',
                    'rgba(255, 107, 107, 1)',
                    'rgba(81, 207, 102, 1)',
                    'rgba(255, 147, 43, 1)',
                    'rgba(156, 136, 255, 1)',
                    'rgba(255, 200, 124, 1)'
                ];
                machineColors[machine] = palette[Object.keys(machineColors).length % palette.length];
            }
            return machineColors[machine];
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Main chart
            const ctx = document.getElementById('temperatureChart').getContext('2d');
            const chartData = @json($chartData);

            const machineGroups = {};
            chartData.forEach(item => {
                if (!machineGroups[item.machine]) {
                    const color = getColor(item.machine);
                    machineGroups[item.machine] = {
                        label: item.machine,
                        data: [],
                        borderColor: color,
                        backgroundColor: color.replace('1)', '0.1)'),
                        tension: 0.4,
                        fill: false
                    };
                }
                machineGroups[item.machine].data.push({
                    x: item.time,
                    y: item.temperature
                });
            });

            mainChart = new Chart(ctx, {
                type: currentChartType,
                data: {
                    datasets: Object.values(machineGroups)
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Temperature Readings for {{ Carbon\Carbon::parse($date)->format('d M Y') }}'
                        },
                        legend: {
                            position: 'top'
                        }
                    },
                    scales: {
                        x: {
                            type: 'time',
                            time: {
                                parser: 'HH:mm:ss',
                                unit: 'minute',
                                displayFormats: {
                                    minute: 'HH:mm'
                                }
                            },
                            title: {
                                display: true,
                                text: 'Time'
                            }
                        },
                        y: {
                            title: {
                                display: true,
                                text: 'Temperature (°C)'
                            }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    }
                }
            });

            // Mini charts per machine
            @foreach ($groupedByMachine as $machineId => $machineReadings)
                const ctx{{ $machineId }} = document.getElementById('machineChart-{{ $machineId }}')
                    .getContext('2d');
                const data{{ $machineId }} = @json($machineReadings->sortBy('reading_time')->map(fn($r) => ['time' => $r->reading_time, 'temperature' => $r->temperature_value])->values());

                new Chart(ctx{{ $machineId }}, {
                    type: 'line',
                    data: {
                        labels: data{{ $machineId }}.map(d => d.time.substring(0, 5)),
                        datasets: [{
                            data: data{{ $machineId }}.map(d => d.temperature),
                            borderColor: getColor(
                                '{{ $machineReadings->first()->machine->name ?? 'Unknown Machine' }}'
                                ),
                            backgroundColor: getColor(
                                '{{ $machineReadings->first()->machine->name ?? 'Unknown Machine' }}'
                                ).replace('1)', '0.1)'),
                            tension: 0.4,
                            fill: true,
                            pointRadius: 2,
                            pointHoverRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            x: {
                                display: true,
                                title: {
                                    display: true,
                                    text: 'Time'
                                }
                            },
                            y: {
                                display: true,
                                beginAtZero: false,
                                title: {
                                    display: true,
                                    text: '°C'
                                }
                            }
                        }
                    }
                });
            @endforeach
        });

        function toggleChartType() {
            currentChartType = currentChartType === 'line' ? 'bar' : 'line';
            mainChart.config.type = currentChartType;
            mainChart.update();
        }

        function downloadChart() {
            const canvas = document.getElementById('temperatureChart');
            const link = document.createElement('a');
            link.download = 'temperature_chart_{{ $date }}.png';
            link.href = canvas.toDataURL();
            link.click();
        }
    </script>
@endpush
