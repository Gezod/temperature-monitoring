@extends('layouts.app')

@section('title', 'Review Temperature Data')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="bi bi-clipboard-check text-primary"></i> Review Temperature Data
    </h1>
    <div class="btn-group">
        <a href="{{ route('temperature.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Temperature
        </a>
        @if($validation->status === 'ready')
            <button class="btn btn-success" onclick="importData()">
                <i class="bi bi-check-circle"></i> Import Data
            </button>
        @endif
    </div>
</div>

<!-- Validation Summary -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
            <div class="stat-value">{{ $validation->data_count }}</div>
            <div class="stat-label">Total Records</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);">
            <div class="stat-value">{{ $validation->validation_errors_count }}</div>
            <div class="stat-label">Validation Errors</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
            <div class="stat-value">{{ $validation->machine->name }}</div>
            <div class="stat-label">Machine</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <div class="stat-value">{{ ucfirst($validation->status) }}</div>
            <div class="stat-label">Status</div>
        </div>
    </div>
</div>

<!-- Machine Info -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-cpu"></i> Machine Information
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p><strong>Machine:</strong> {{ $validation->machine->name }}</p>
                <p><strong>Branch:</strong> {{ $validation->machine->branch->name }}</p>
                <p><strong>Type:</strong> {{ $validation->machine->type }}</p>
            </div>
            <div class="col-md-6">
                <p><strong>Normal Range:</strong> {{ $validation->machine->temp_min_normal }}°C to {{ $validation->machine->temp_max_normal }}°C</p>
                <p><strong>Critical Range:</strong> {{ $validation->machine->temp_critical_min }}°C to {{ $validation->machine->temp_critical_max }}°C</p>
                <p><strong>Uploaded:</strong> {{ $validation->uploaded_at->format('d/m/Y H:i:s') }}</p>
            </div>
        </div>
    </div>
</div>

<!-- Validation Errors -->
@if($validation->validation_errors && count($validation->validation_errors) > 0)
<div class="card mb-4">
    <div class="card-header bg-warning text-dark">
        <h5 class="mb-0">
            <i class="bi bi-exclamation-triangle"></i> Validation Errors ({{ count($validation->validation_errors) }})
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead>
                    <tr>
                        <th>Row</th>
                        <th>Error Type</th>
                        <th>Message</th>
                        <th>Current Value</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($validation->validation_errors as $error)
                        @php
                            $rowData = $validation->raw_data[$error['index']] ?? null;
                        @endphp
                        <tr>
                            <td>{{ $error['index'] + 1 }}</td>
                            <td>
                                <span class="badge bg-warning">{{ str_replace('_', ' ', $error['type']) }}</span>
                            </td>
                            <td>{{ $error['message'] }}</td>
                            <td>
                                @if($rowData)
                                    <small>
                                        Temp: {{ $rowData['temperature'] ?? 'N/A' }}°C<br>
                                        Time: {{ $rowData['timestamp'] ?? 'N/A' }}
                                    </small>
                                @endif
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary"
                                        onclick="editRow({{ $error['index'] }})">
                                    <i class="bi bi-pencil"></i> Fix
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

<!-- Data by Date -->
<div class="row">
    @foreach($groupedData as $date => $dateData)
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="bi bi-calendar3"></i> {{ Carbon\Carbon::parse($date)->format('d M Y') }}
                    </h6>
                    <span class="badge bg-primary">{{ count($dateData) }} readings</span>
                </div>
                <div class="card-body">
                    @php
                        $avgTemp = collect($dateData)->avg('temperature');
                        $minTemp = collect($dateData)->min('temperature');
                        $maxTemp = collect($dateData)->max('temperature');
                    @endphp

                    <div class="row text-center mb-3">
                        <div class="col-4">
                            <h6 class="text-info mb-0">{{ number_format($avgTemp, 1) }}°C</h6>
                            <small class="text-muted">Avg</small>
                        </div>
                        <div class="col-4">
                            <h6 class="text-success mb-0">{{ number_format($minTemp, 1) }}°C</h6>
                            <small class="text-muted">Min</small>
                        </div>
                        <div class="col-4">
                            <h6 class="text-danger mb-0">{{ number_format($maxTemp, 1) }}°C</h6>
                            <small class="text-muted">Max</small>
                        </div>
                    </div>

                    <!-- Quick preview of readings -->
                    <div style="max-height: 200px; overflow-y: auto;">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Temp</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(array_slice($dateData, 0, 10) as $index => $reading)
                                    @php
                                        $hasError = collect($validation->validation_errors)->contains('index', array_search($reading, $validation->raw_data));
                                    @endphp
                                    <tr class="{{ $hasError ? 'table-warning' : '' }}">
                                        <td>{{ Carbon\Carbon::parse($reading['timestamp'])->format('H:i') }}</td>
                                        <td>{{ $reading['temperature'] }}°C</td>
                                        <td>
                                            @if($hasError)
                                                <i class="bi bi-exclamation-triangle text-warning"></i>
                                            @else
                                                <i class="bi bi-check-circle text-success"></i>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if(count($dateData) > 10)
                        <small class="text-muted">... and {{ count($dateData) - 10 }} more</small>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>

<!-- Edit Row Modal -->
<div class="modal fade" id="editRowModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Temperature Reading</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editRowForm">
                    <input type="hidden" id="edit_index">
                    <div class="mb-3">
                        <label for="edit_temperature" class="form-label">Temperature (°C)</label>
                        <input type="number" step="0.1" class="form-control" id="edit_temperature" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_timestamp" class="form-label">Timestamp</label>
                        <input type="datetime-local" class="form-control" id="edit_timestamp" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveRowEdit()">Save Changes</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
const validationData = @json($validation->raw_data);
const sessionId = '{{ $validation->upload_session_id }}';

function editRow(index) {
    const rowData = validationData[index];

    document.getElementById('edit_index').value = index;
    document.getElementById('edit_temperature').value = rowData.temperature;

    // Convert timestamp to datetime-local format
    const timestamp = new Date(rowData.timestamp);
    const localDateTime = new Date(timestamp.getTime() - (timestamp.getTimezoneOffset() * 60000))
        .toISOString().slice(0, 16);
    document.getElementById('edit_timestamp').value = localDateTime;

    new bootstrap.Modal(document.getElementById('editRowModal')).show();
}

function saveRowEdit() {
    const index = parseInt(document.getElementById('edit_index').value);
    const temperature = parseFloat(document.getElementById('edit_temperature').value);
    const timestamp = document.getElementById('edit_timestamp').value;

    fetch(`/temperature/validation/${sessionId}/update`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            corrections: [{
                index: index,
                temperature: temperature,
                timestamp: timestamp
            }]
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update local data
            validationData[index].temperature = temperature;
            validationData[index].timestamp = timestamp;

            // Close modal and refresh page
            bootstrap.Modal.getInstance(document.getElementById('editRowModal')).hide();
            location.reload();
        } else {
            alert('Error updating data: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error: ' + error.message);
    });
}

function importData() {
    if (confirm('Import all validated data? This action cannot be undone.')) {
        fetch(`/temperature/validation/${sessionId}/import`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                window.location.href = '{{ route("temperature.index") }}';
            } else {
                alert('Error importing data: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error: ' + error.message);
        });
    }
}
</script>
@endpush
