@extends('layouts.app')

@section('title', 'Validation History')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="bi bi-clock-history text-primary"></i> Validation History
    </h1>
    <a href="{{ route('temperature.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back to Temperature
    </a>
</div>

<!-- Filter Section -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select name="status" id="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="needs_review" {{ request('status') == 'needs_review' ? 'selected' : '' }}>Needs Review</option>
                    <option value="ready" {{ request('status') == 'ready' ? 'selected' : '' }}>Ready</option>
                    <option value="imported" {{ request('status') == 'imported' ? 'selected' : '' }}>Imported</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="machine_id" class="form-label">Machine</label>
                <select name="machine_id" id="machine_id" class="form-select">
                    <option value="">All Machines</option>
                    @foreach($machines->groupBy('branch.name') as $branchName => $branchMachines)
                        <optgroup label="{{ $branchName }}">
                            @foreach($branchMachines as $machine)
                                <option value="{{ $machine->id }}" {{ request('machine_id') == $machine->id ? 'selected' : '' }}>
                                    {{ $machine->name }}
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="bi bi-search"></i> Filter
                </button>
                <a href="{{ route('temperature.validation.history') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Validation History Table -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-list-ul"></i> Upload & Validation History
            <span class="badge bg-secondary ms-2">{{ $validations->total() }} total</span>
        </h5>
    </div>
    <div class="card-body">
        @if($validations->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Upload Date</th>
                            <th>Machine</th>
                            <th>Records</th>
                            <th>Errors</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($validations as $validation)
                            <tr>
                                <td>
                                    <strong>{{ $validation->uploaded_at->format('d/m/Y H:i:s') }}</strong><br>
                                    <small class="text-muted">{{ $validation->uploaded_at->diffForHumans() }}</small>
                                </td>
                                <td>
                                    <strong>{{ $validation->machine->name }}</strong><br>
                                    <small class="text-muted">{{ $validation->machine->branch->name }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-info">{{ $validation->data_count }}</span>
                                </td>
                                <td>
                                    @if($validation->validation_errors_count > 0)
                                        <span class="badge bg-warning">{{ $validation->validation_errors_count }}</span>
                                    @else
                                        <span class="badge bg-success">0</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $statusColors = [
                                            'pending' => 'secondary',
                                            'needs_review' => 'warning',
                                            'ready' => 'success',
                                            'imported' => 'primary'
                                        ];
                                    @endphp
                                    <span class="badge bg-{{ $statusColors[$validation->status] ?? 'secondary' }}">
                                        {{ ucfirst(str_replace('_', ' ', $validation->status)) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('temperature.validation.review', $validation->upload_session_id) }}"
                                           class="btn btn-outline-info" title="Review">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @if($validation->status === 'ready')
                                            <button class="btn btn-outline-success"
                                                    onclick="importValidation('{{ $validation->upload_session_id }}')"
                                                    title="Import">
                                                <i class="bi bi-check-circle"></i>
                                            </button>
                                        @endif
                                        @if(!$validation->is_imported)
                                            <button class="btn btn-outline-danger"
                                                    onclick="deleteValidation('{{ $validation->upload_session_id }}')"
                                                    title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
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
                {{ $validations->withQueryString()->links() }}
            </div>
        @else
            <div class="text-center py-5">
                <i class="bi bi-inbox display-4 text-muted"></i>
                <p class="text-muted mt-2">No validation history found.</p>
            </div>
        @endif
    </div>
</div>

@endsection

@push('scripts')
<script>
function importValidation(sessionId) {
    if (confirm('Import this validated data? This action cannot be undone.')) {
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
                location.reload();
            } else {
                alert('Error importing data: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error: ' + error.message);
        });
    }
}

function deleteValidation(sessionId) {
    if (confirm('Delete this validation session? This action cannot be undone.')) {
        fetch(`/temperature/validation/${sessionId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Validation session deleted successfully.');
                location.reload();
            } else {
                alert('Error deleting validation session: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error: ' + error.message);
        });
    }
}
</script>
@endpush
