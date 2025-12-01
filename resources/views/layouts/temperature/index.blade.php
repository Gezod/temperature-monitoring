@extends('layouts.app')

@section('title', 'Temperature Data')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="bi bi-thermometer text-primary"></i> Data Suhu Mesin
        </h1>
        <div class="btn-group">
            <a href="{{ route('temperature.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Tambah Data Manual
            </a>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#uploadModal">
                <i class="bi bi-cloud-upload"></i> Upload File
            </button>
            {{-- <a href="{{ route('temperature.validation.history') }}" class="btn btn-info">
                <i class="bi bi-clock-history"></i> Validation History
            </a> --}}
            {{-- <a href="{{ route('temperature.index', ['view' => 'detailed']) }}" class="btn btn-outline-secondary">
                <i class="bi bi-list-ul"></i> Detailed View
            </a> --}}
            {{-- <button class="btn btn-warning" onclick="exportData()">
                <i class="bi bi-download"></i> Export PDF
            </button> --}}
        </div>
    </div>

    <!-- Toggle View Buttons -->
    <div class="mb-3">
        <button class="btn btn-outline-primary me-2" onclick="showView('table')">Table View</button>
        <button class="btn btn-outline-primary" onclick="showView('card')">Card View</button>
    </div>

    <!-- Filter Section (Shared) -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="machine_id" class="form-label">Filter Mesin</label>
                    <select name="machine_id" id="machine_id" class="form-select">
                        <option value="">Semua Mesin</option>
                        @foreach($machines->groupBy('branch.name') as $branchName => $branchMachines)
                            <optgroup label="{{ $branchName }}">
                                @foreach($branchMachines as $machine)
                                    <option value="{{ $machine->id }}" {{ request('machine_id') == $machine->id ? 'selected' : '' }}>
                                        {{ $machine->name }} ({{ $machine->type }})
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="date_from" class="form-label">Dari Tanggal</label>
                    <input type="date" name="date_from" id="date_from" class="form-control"
                        value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label for="date_to" class="form-label">Sampai Tanggal</label>
                    <input type="date" name="date_to" id="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-2">
                    <label for="validation_status" class="form-label">Status Validasi</label>
                    <select name="validation_status" id="validation_status" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="pending" {{ request('validation_status') == 'pending' ? 'selected' : '' }}>Pending
                        </option>
                        <option value="imported" {{ request('validation_status') == 'imported' ? 'selected' : '' }}>Imported
                        </option>
                        <option value="manual" {{ request('validation_status') == 'manual' ? 'selected' : '' }}>Manual
                        </option>
                        <option value="edited" {{ request('validation_status') == 'edited' ? 'selected' : '' }}>Edited
                        </option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-search"></i> Filter
                    </button>
                    <a href="{{ route('temperature.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-lg"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Table View -->
    <div id="table-view">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-list-ul"></i> Daftar Pembacaan Suhu (Tabel)</h5>
            </div>
            <div class="card-body">
                @if($readings->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Temperature</th>
                                    <th>Time Stamp</th>
                                    <th>Machine</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($readings as $reading)
                                    <tr class="{{ $reading->is_anomaly ? 'table-warning' : '' }}">
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $reading->temperature_value }}°C</td>
                                        <td>{{ $reading->reading_time }}</td>
                                        <td>
                                            <small class="text-muted">{{ optional($reading->machine)->type ?? '-' }}</small>
                                            <small class="text-muted">{{ optional($reading->machine)->type ?? '-' }}</small>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="bi bi-inbox display-4 text-muted"></i>
                        <p class="text-muted mt-2">Belum ada data pembacaan suhu.</p>
                        <a href="{{ route('temperature.create') }}" class="btn btn-primary">
                            <i class="bi bi-plus-lg"></i> Tambah Data Pertama
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Card / Dashboard View -->
    <div id="card-view" style="display:none;">
        <div class="row">
            @if($groupedReadings->count() > 0)
                @foreach($groupedReadings as $date => $dateReadings)
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="bi bi-calendar3"></i> {{ \Carbon\Carbon::parse($date)->format('d M Y') }}
                                </h5>
                                <span class="badge bg-primary">{{ $dateReadings->count() }} readings</span>
                            </div>
                            <div class="card-body">
                                @php
                                    $avgTemp = $dateReadings->avg('temperature_value');
                                    $minTemp = $dateReadings->min('temperature_value');
                                    $maxTemp = $dateReadings->max('temperature_value');
                                    $machineCount = $dateReadings->unique('machine_id')->count();
                                    $pendingCount = $dateReadings->where('validation_status', 'pending')->count();
                                @endphp
                                <div class="row text-center mb-3">
                                    <div class="col-6">
                                        <h4 class="text-info mb-0">{{ number_format($avgTemp, 1) }}°C</h4>
                                        <small class="text-muted">Rata-rata</small>
                                    </div>
                                    <div class="col-6">
                                        <h4 class="text-success mb-0">{{ $machineCount }}</h4>
                                        <small class="text-muted">Mesin</small>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <small class="text-muted">Range: {{ number_format($minTemp, 1) }}°C -
                                        {{ number_format($maxTemp, 1) }}°C</small>
                                </div>
                                @if($pendingCount > 0)
                                    <div class="alert alert-warning py-2 mb-2">
                                        <i class="bi bi-exclamation-triangle"></i> {{ $pendingCount }} readings need validation
                                    </div>
                                @endif
                                {{-- <canvas id="miniChart-{{ $date }}" height="60"></canvas> --}}
                                <div class="mt-3 d-flex justify-content-between">
                                    <small class="text-muted">{{ $dateReadings->first()->reading_time }} -
                                        {{ $dateReadings->last()->reading_time }}</small>
                                    <a href="{{ route('temperature.show-date', $date) }}" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-eye"></i> View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    @push('scripts')
                        <script>
                            document.addEventListener('DOMContentLoaded', function () {
                                const ctx{{ $loop->index }} = document.getElementById('miniChart-{{ $date }}').getContext('2d');
                                const data{{ $loop->index }} = @json($dateReadings->sortBy('reading_time')->map(function ($reading) {
                                    return [
                                        'time' => $reading->reading_time,
                                        'temperature' => $reading->temperature_value
                                    ];
                                })->values());

                                new Chart(ctx{{ $loop->index }}, {
                                    type: 'line',
                                    data: {
                                        labels: data{{ $loop->index }}.map(d => d.time.substring(0, 5)),
                                        datasets: [{
                                            data: data{{ $loop->index }}.map(d => d.temperature),
                                            borderColor: '#4facfe',
                                            backgroundColor: 'rgba(79, 172, 254, 0.1)',
                                            borderWidth: 2,
                                            tension: 0.4,
                                            fill: true,
                                            pointRadius: 0
                                        }]
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        plugins: { legend: { display: false } },
                                        scales: { x: { display: false }, y: { display: false, beginAtZero: false } },
                                        elements: { point: { radius: 0 } }
                                    }
                                });
                            });
                        </script>
                    @endpush
                @endforeach
            @else
                <div class="col-12 text-center py-5">
                    <i class="bi bi-inbox display-4 text-muted"></i>
                    <p class="text-muted mt-2">Belum ada data pembacaan suhu.</p>
                    <a href="{{ route('temperature.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i> Tambah Data Pertama
                    </a>
                </div>
            @endif
        </div>
    </div>

@endsection

@section('modals')
    <!-- Upload Modal -->
    <div class="modal fade" id="uploadModal" tabindex="-1" data-bs-backdrop="static">

        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Upload File Data Suhu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                <form id="pdfUploadForm1" enctype="multipart/form-data" action="{{ route('temperature.upload-pdf-py') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                                <label for="machine_id_pdf" class="form-label">Pilih Mesin</label>
                                <select name="machine_id" id="machine_id_pdf" class="form-select" required>
                                    <option value="">Pilih mesin...</option>
                                    @foreach($machines->groupBy('branch.name') as $branchName => $branchMachines)
                                        <optgroup label="{{ $branchName }}">
                                            @foreach($branchMachines as $machine)
                                                <option value="{{ $machine->id }}">
                                                    {{ $machine->name }} ({{ $machine->type }})
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </div>
                        <div class="mb-3">
                            <label for="pdf_file" class="form-label">File PDF <span class="text-danger">*</span></label>
                            <input type="file" name="file" id="pdf_file" class="form-control" accept=".pdf" required>
                        </div>
                        <button type="submit" class="btn btn-success"><i class="bi bi-upload"></i> Upload & Validasi
                            PDF</button>
                    </form>
                    <div id="upload-progress" class="mt-3" style="display: none;">
                        <div class="progress">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 0%"></div>
                        </div>
                        <small class="text-muted mt-2">Memproses file...</small>
                    </div>
                    <div id="upload-result" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function showView(view) {
            document.getElementById('table-view').style.display = (view === 'table') ? 'block' : 'none';
            document.getElementById('card-view').style.display = (view === 'card') ? 'block' : 'none';
        }
        window.onload = () => showView('table');

        function uploadFileWithValidation(form, url = '{{ route("temperature.upload-pdf-py") }}') {
            const formData = new FormData(form);
            const progressDiv = document.getElementById('upload-progress');
            const resultDiv = document.getElementById('upload-result');
            const progressBar = progressDiv.querySelector('.progress-bar');
            progressDiv.style.display = 'block'; resultDiv.innerHTML = ''; progressBar.style.width = '0%';

            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="spinner-border spinner-border-sm me-2"></i>Processing...';

            fetch(url, { method: 'POST', body: formData, headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') } })
                .then(res => res.json()).then(data => {
                    progressBar.style.width = '100%';
                    if (data.success) {
                        resultDiv.innerHTML = `<div class="alert alert-success"><i class="bi bi-check-circle"></i> ${data.message}</div>`;
                        form.reset();
                    } else {
                        resultDiv.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> ${data.message}</div>`;
                    }
                })
                .catch(err => resultDiv.innerHTML = `<div class="alert alert-danger">Error: ${err}</div>`)
                .finally(() => { submitBtn.disabled = false; submitBtn.innerHTML = originalText; progressDiv.style.display = 'none'; });
        }

        document.getElementById('pdfUploadForm').addEventListener('submit', function (e) {
            e.preventDefault(); uploadFileWithValidation(this);
        });

        function exportData() {
            window.open('{{ route("temperature.export-pdf") }}', '_blank');
        }
    </script>
@endpush
