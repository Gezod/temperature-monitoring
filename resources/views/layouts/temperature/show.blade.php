@extends('layouts.app')

@section('title', 'Temperature Reading Details')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="bi bi-thermometer text-primary"></i> Temperature Reading Details
        </h1>
        <div class="btn-group">
            <a href="{{ route('temperature.show-date', $temperature->reading_date) }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Date View
            </a>
            <a href="{{ route('temperature.edit', $temperature) }}" class="btn btn-warning">
                <i class="bi bi-pencil"></i> Edit
            </a>
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
                                    <td><strong>Date:</strong></td>
                                    <td>{{ Carbon\Carbon::parse($temperature->reading_date)->format('d M Y') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Time:</strong></td>
                                    <td>{{ $temperature->reading_time }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Temperature:</strong></td>
                                    <td>
                                        @php
                                            $minNormal = $temperature->machine->temp_min_normal ?? null;
                                            $maxNormal = $temperature->machine->temp_max_normal ?? null;
                                            $temp = $temperature->temperature_value;
                                            $isDanger = false;
                                            if (!is_null($minNormal) && !is_null($maxNormal)) {
                                                $isDanger = $temp < $minNormal || $temp > $maxNormal;
                                            }
                                        @endphp

                                        <span class="h4 text-{{ $isDanger ? 'danger' : 'success' }}">
                                            {{ number_format($temp, 1) }}°C
                                        </span>@php
                                            $minNormal = $temperature->machine->temp_min_normal ?? null;
                                            $maxNormal = $temperature->machine->temp_max_normal ?? null;
                                            $temp = $temperature->temperature_value;
                                            $isDanger = false;
                                            if (!is_null($minNormal) && !is_null($maxNormal)) {
                                                $isDanger = $temp < $minNormal || $temp > $maxNormal;
                                            }
                                        @endphp

                                        <span class="h4 text-{{ $isDanger ? 'danger' : 'success' }}">
                                            {{ number_format($temp, 1) }}°C
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Validation Status:</strong></td>
                                    <td>
                                        <span
                                            class="badge bg-{{ $temperature->validation_status === 'imported' ? 'success' : ($temperature->validation_status === 'pending' ? 'warning' : 'info') }}">
                                            {{ ucfirst($temperature->validation_status) }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Machine:</strong></td>
                                    <td>{{ $temperature->machine->name ?? 'Unknown Machine' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Branch:</strong></td>
                                    <td>{{ $temperature->machine->name ?? 'Unknown Branch' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Machine Type:</strong></td>
                                   <td>{{ $temperature->machine->name ?? 'Unknown Type' }}</td>
                                </tr>
                                {{-- <tr>
                                <td><strong>Is Validated:</strong></td>
                                <td>
                                    <span class="badge bg-{{ $temperature->is_validated ? 'success' : 'warning' }}">
                                        {{ $temperature->is_validated ? 'Yes' : 'No' }}
                                    </span>
                                </td>
                            </tr> --}}
                            </table>
                        </div>
                    </div>

                    @if ($temperature->validation_notes)
                        <div class="mt-3">
                            <h6>Validation Notes:</h6>
                            <div class="alert alert-info">
                                @if (is_array($temperature->validation_notes))
                                    {{ json_encode($temperature->validation_notes, JSON_PRETTY_PRINT) }}
                                @else
                                    {{ $temperature->validation_notes }}
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Daily Temperature Chart -->
            <div class="card mt-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-graph-up"></i> Daily Temperature Pattern
                    </h5>
                    <button class="btn btn-outline-primary btn-sm" onclick="downloadChart()">
                        <i class="bi bi-download"></i> Download Chart
                    </button>
                </div>
                <div class="card-body">
                    <canvas id="dailyChart" height="100"></canvas>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-md-4">
            <!-- Machine Temperature Ranges -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-thermometer-half"></i> Machine Temperature Ranges
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-6">
                            <h6 class="text-success">Normal Range</h6>
                            <p class="mb-0">
                                <strong>{{ $temperature->machine->temp_min_normal }}°C</strong> to
                                <strong>{{ $temperature->machine->temp_max_normal }}°C</strong>
                            </p>
                        </div>
                        <div class="col-6">
                            <h6 class="text-danger">Critical Range</h6>
                            <p class="mb-0">
                                Below <strong>{{ $temperature->machine->temp_critical_min }}°C</strong> or<br>
                                Above <strong>{{ $temperature->machine->temp_critical_max }}°C</strong>
                            </p>
                        </div>
                    </div>

                    <!-- Temperature Gauge -->
                    <div class="mt-3">
                        <div class="progress" style="height: 30px;">
                            @php
                                $minRange = $temperature->machine->temp_critical_min;
                                $maxRange = $temperature->machine->temp_critical_max;
                                $currentTemp = $temperature->temperature_value;
                                $position = (($currentTemp - $minRange) / ($maxRange - $minRange)) * 100;
                                $position = max(0, min(100, $position));
                            @endphp
                            <div class="progress-bar bg-success"
                                style="width: {{ (($temperature->machine->temp_min_normal - $minRange) / ($maxRange - $minRange)) * 100 }}%">
                            </div>
                            <div class="progress-bar bg-warning"
                                style="width: {{ (($temperature->machine->temp_max_normal - $temperature->machine->temp_min_normal) / ($maxRange - $minRange)) * 100 }}%">
                            </div>
                            <div class="progress-bar bg-danger"
                                style="width: {{ (($maxRange - $temperature->machine->temp_max_normal) / ($maxRange - $minRange)) * 100 }}%">
                            </div>
                        </div>
                        <div class="position-relative">
                            <div class="position-absolute"
                                style="left: {{ $position }}%; top: -5px; transform: translateX(-50%);">
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

            <!-- Nearby Readings -->
            @if ($nearbyReadings->count() > 0)
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-clock-history"></i> Other Readings Today
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive" style="max-height: 300px;">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>Temperature</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($nearbyReadings as $nearby)
                                        <tr>
                                            <td>{{ $nearby->reading_time }}</td>
                                            <td>
                                                <span
                                                    class="text-{{ $nearby->temperature_value < $temperature->machine->temp_min_normal || $nearby->temperature_value > $temperature->machine->temp_max_normal ? 'danger' : 'success' }}">
                                                    {{ number_format($nearby->temperature_value, 1) }}°C
                                                </span>
                                            </td>
                                            <td>
                                                <a href="{{ route('temperature.show', $nearby->id) }}"
                                                    class="btn btn-outline-info btn-sm">
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
                        <a href="{{ route('machines.show', $temperature->machine) }}" class="btn btn-outline-primary">
                            <i class="bi bi-cpu"></i> View Machine Details
                        </a>
                        <a href="{{ route('temperature.show-date', $temperature->reading_date) }}"
                            class="btn btn-outline-info">
                            <i class="bi bi-calendar3"></i> View All Today's Readings
                        </a>
                        <form method="POST" action="{{ route('temperature.destroy', $temperature) }}"
                            onsubmit="return confirm('Are you sure you want to delete this reading?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger">
                                <i class="bi bi-trash"></i> Delete Reading
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Daily temperature chart
            const ctx = document.getElementById('dailyChart').getContext('2d');
            const chartData = @json($chartData);

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartData.map(d => d.time.substring(0, 5)),
                    datasets: [{
                        label: 'Temperature (°C)',
                        data: chartData.map(d => d.temperature),
                        borderColor: function(context) {
                            return chartData[context.dataIndex].is_current ? '#ff6b6b' :
                                '#4facfe';
                        },
                        backgroundColor: function(context) {
                            return chartData[context.dataIndex].is_current ?
                                'rgba(255, 107, 107, 0.1)' : 'rgba(79, 172, 254, 0.1)';
                        },
                        pointBackgroundColor: function(context) {
                            return chartData[context.dataIndex].is_current ? '#ff6b6b' :
                                '#4facfe';
                        },
                        pointRadius: function(context) {
                            return chartData[context.dataIndex].is_current ? 8 : 4;
                        },
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Daily Temperature Pattern - {{ Carbon\Carbon::parse($temperature->reading_date)->format('d M Y') }}'
                        },
                        legend: {
                            display: false
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
                            title: {
                                display: true,
                                text: 'Time'
                            }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    }
                }
            });
        });

        function downloadChart() {
            const canvas = document.getElementById('dailyChart');
            const link = document.createElement('a');
            link.download = 'temperature_detail_{{ $temperature->reading_date }}.png';
            link.href = canvas.toDataURL();
            link.click();
        }
    </script>
@endpush
