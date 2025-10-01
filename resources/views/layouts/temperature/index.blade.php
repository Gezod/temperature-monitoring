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
        <button class="btn btn-info" onclick="exportData()">
            <i class="bi bi-download"></i> Export PDF
        </button>
    </div>
</div>

<!-- Filter Section -->
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
                                <option value="{{ $machine->id }}"
                                    {{ request('machine_id') == $machine->id ? 'selected' : '' }}>
                                    {{ $machine->name }} ({{ $machine->type }})
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="date_from" class="form-label">Dari Tanggal</label>
                <input type="date" name="date_from" id="date_from" class="form-control"
                       value="{{ request('date_from') }}">
            </div>
            <div class="col-md-3">
                <label for="date_to" class="form-label">Sampai Tanggal</label>
                <input type="date" name="date_to" id="date_to" class="form-control"
                       value="{{ request('date_to') }}">
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

<!-- Data Table -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-list-ul"></i> Daftar Pembacaan Suhu
            <span class="badge bg-secondary ms-2">{{ $readings->total() }} total</span>
        </h5>
    </div>
    <div class="card-body">
        @if($readings->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Tanggal & Waktu</th>
                            <th>Cabang</th>
                            <th>Mesin</th>
                            <th>Suhu</th>
                            <th>Status</th>
                            <th>Tipe Pembacaan</th>
                            <th>File Sumber</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($readings as $reading)
                            <tr class="{{ $reading->is_anomaly ? 'table-warning' : '' }}">
                                <td>
                                    <strong>{{ $reading->recorded_at->format('d/m/Y H:i:s') }}</strong>
                                </td>
                                <td>{{ $reading->machine->branch->name }}</td>
                                <td>
                                    <strong>{{ $reading->machine->name }}</strong><br>
                                    <small class="text-muted">{{ $reading->machine->type }}</small>
                                </td>
                                <td>
                                    <strong class="text-{{ $reading->status == 'critical' ? 'danger' : ($reading->status == 'warning' ? 'warning' : 'success') }}">
                                        {{ number_format($reading->temperature, 1) }}°C
                                    </strong>
                                    @if($reading->is_anomaly)
                                        <i class="bi bi-exclamation-triangle text-warning" title="Anomali Terdeteksi"></i>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $reading->status == 'critical' ? 'danger' : ($reading->status == 'warning' ? 'warning' : 'success') }}">
                                        @if($reading->status == 'critical')
                                            Critical
                                        @elseif($reading->status == 'warning')
                                            Warning
                                        @else
                                            Normal
                                        @endif
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $reading->reading_type == 'automatic' ? 'info' : ($reading->reading_type == 'imported' ? 'secondary' : 'primary') }}">
                                        {{ ucfirst($reading->reading_type) }}
                                    </span>
                                </td>
                                <td>
                                    @if($reading->source_file)
                                        <small class="text-muted">{{ $reading->source_file }}</small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('temperature.show', $reading->id) }}"
                                           class="btn btn-outline-info btn-sm" title="Detail">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @if($reading->reading_type === 'manual')
                                            <form method="POST" action="{{ route('temperature.destroy', $reading->id) }}"
                                                  class="d-inline" onsubmit="return confirm('Yakin ingin menghapus data ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm" title="Hapus">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center mt-3">
                {{ $readings->withQueryString()->links() }}
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

<!-- Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload File Data Suhu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs" id="uploadTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="pdf-tab" data-bs-toggle="tab" data-bs-target="#pdf-upload" type="button">
                            <i class="bi bi-file-pdf"></i> Upload PDF
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="excel-tab" data-bs-toggle="tab" data-bs-target="#excel-upload" type="button">
                            <i class="bi bi-file-excel"></i> Upload Excel
                        </button>
                    </li>
                </ul>

                <div class="tab-content mt-3" id="uploadTabContent">
                    <div class="tab-pane fade show active" id="pdf-upload">
                        <form id="pdfUploadForm" enctype="multipart/form-data">
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
                                <label for="pdf_file" class="form-label">File PDF</label>
                                <input type="file" name="pdf_file" id="pdf_file" class="form-control"
                                       accept=".pdf" required>
                                <div class="form-text">
                                    Upload file PDF yang berisi data log suhu dari sensor. Format yang didukung seperti log EFE217100898.
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-upload"></i> Upload PDF
                            </button>
                        </form>
                    </div>

                    <div class="tab-pane fade" id="excel-upload">
                        <form id="excelUploadForm" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label for="machine_id_excel" class="form-label">Pilih Mesin</label>
                                <select name="machine_id" id="machine_id_excel" class="form-select" required>
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
                                <label for="excel_file" class="form-label">File Excel/CSV</label>
                                <input type="file" name="excel_file" id="excel_file" class="form-control"
                                       accept=".xlsx,.xls,.csv" required>
                                <div class="form-text">
                                    Upload file Excel atau CSV dengan kolom: Tanggal/Waktu, Suhu. Format tanggal: YYYY-MM-DD HH:MM:SS
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-upload"></i> Upload Excel/CSV
                            </button>
                        </form>
                    </div>
                </div>

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
<script>
// PDF Upload
document.getElementById('pdfUploadForm').addEventListener('submit', function(e) {
    e.preventDefault();
    uploadFile(this, '{{ route("temperature.upload-pdf") }}');
});

// Excel Upload
document.getElementById('excelUploadForm').addEventListener('submit', function(e) {
    e.preventDefault();
    uploadFile(this, '{{ route("temperature.upload-excel") }}');
});

function uploadFile(form, url) {
    const formData = new FormData(form);
    const progressDiv = document.getElementById('upload-progress');
    const resultDiv = document.getElementById('upload-result');
    const progressBar = progressDiv.querySelector('.progress-bar');

    // Show progress
    progressDiv.style.display = 'block';
    resultDiv.innerHTML = '';
    progressBar.style.width = '0%';

    // Disable form
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="spinner-border spinner-border-sm me-2"></i>Processing...';

    fetch(url, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        progressBar.style.width = '100%';

        if (data.success) {
            resultDiv.innerHTML = `
                <div class="alert alert-success">
                    <i class="bi bi-check-circle"></i> ${data.message}
                    <br><small>Imported: ${data.data.imported_count} readings</small>
                </div>
            `;

            // Reset form
            form.reset();

            // Refresh page after 2 seconds
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            resultDiv.innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-circle"></i> ${data.message}
                </div>
            `;
        }
    })
    .catch(error => {
        resultDiv.innerHTML = `
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-circle"></i> Error: ${error.message}
            </div>
        `;
    })
    .finally(() => {
        // Hide progress and restore button
        progressDiv.style.display = 'none';
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}

function exportData() {
    const params = new URLSearchParams();

    // Add current filters to export
    if (document.getElementById('machine_id').value) {
        params.append('machine_id', document.getElementById('machine_id').value);
    }
    if (document.getElementById('date_from').value) {
        params.append('date_from', document.getElementById('date_from').value);
    }
    if (document.getElementById('date_to').value) {
        params.append('date_to', document.getElementById('date_to').value);
    }

    window.open('{{ route("temperature.export-pdf") }}?' + params.toString(), '_blank');
}
</script>
@endpush
