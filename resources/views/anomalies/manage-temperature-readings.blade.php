@extends('layouts.app')

@section('title', 'Manage Temperature Readings')

@push('styles')
<style>
.reading-card {
    transition: all 0.3s ease;
    border-left: 4px solid #dee2e6;
}
.reading-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.reading-with-anomalies { border-left-color: #dc3545; }
.reading-normal { border-left-color: #28a745; }

.bulk-actions {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    padding: 1rem;
    margin-bottom: 1rem;
}

.table-actions-cell {
    width: 100px;
    white-space: nowrap;
}

/* Pagination Styling */
.pagination-wrapper {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    margin-top: 1rem;
}

.pagination-info {
    color: #6c757d;
    font-size: 0.875rem;
}

.pagination-custom .page-link {
    border-radius: 0.375rem;
    margin: 0 0.15rem;
    border: 1px solid #dee2e6;
}

.pagination-custom .page-item.active .page-link {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

.pagination-custom .page-item.disabled .page-link {
    color: #6c757d;
    background-color: #f8f9fa;
}

/* Fix modal backdrop issue */
.modal-backdrop {
    z-index: 1040;
}

.modal {
    z-index: 1050;
}

@media (max-width: 768px) {
    .pagination-wrapper {
        justify-content: center;
        text-align: center;
    }

    .pagination-info {
        order: 2;
        width: 100%;
    }

    .pagination-custom {
        order: 1;
    }
}
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-thermometer-half text-primary"></i>
                Manage Temperature Readings
            </h1>
            <p class="text-muted mb-0">Manage and clean up temperature reading data</p>
        </div>
        <div class="btn-group">
            <a href="{{ route('anomalies.index') }}" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left"></i> Back to Anomalies
            </a>
            <button class="btn btn-warning" onclick="exportReadings()">
                <i class="bi bi-download"></i> Export Data
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h4 class="text-primary mb-1">{{ number_format($stats['total_readings']) }}</h4>
                    <small class="text-muted">Total Readings</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h4 class="text-danger mb-1">{{ number_format($stats['with_anomalies']) }}</h4>
                    <small class="text-muted">With Anomalies</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h4 class="text-success mb-1">{{ number_format($stats['without_anomalies']) }}</h4>
                    <small class="text-muted">Normal Readings</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h4 class="text-info mb-1">{{ number_format($stats['today_readings']) }}</h4>
                    <small class="text-muted">Today's Readings</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
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
                <div class="col-md-2">
                    <label for="date_from" class="form-label">Date From</label>
                    <input type="date" name="date_from" id="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label for="date_to" class="form-label">Date To</label>
                    <input type="date" name="date_to" id="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-2">
                    <label for="has_anomalies" class="form-label">Anomaly Status</label>
                    <select name="has_anomalies" id="has_anomalies" class="form-select">
                        <option value="">All</option>
                        <option value="yes" {{ request('has_anomalies') === 'yes' ? 'selected' : '' }}>With Anomalies</option>
                        <option value="no" {{ request('has_anomalies') === 'no' ? 'selected' : '' }}>Without Anomalies</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="reading_type" class="form-label">Reading Type</label>
                    <select name="reading_type" id="reading_type" class="form-select">
                        <option value="">All Types</option>
                        <option value="manual_entry" {{ request('reading_type') === 'manual_entry' ? 'selected' : '' }}>Manual Entry</option>
                        <option value="transfer" {{ request('reading_type') === 'transfer' ? 'selected' : '' }}>Transfer</option>
                        <option value="imported" {{ request('reading_type') === 'imported' ? 'selected' : '' }}>Imported</option>
                        <option value="emergency_transfer" {{ request('reading_type') === 'emergency_transfer' ? 'selected' : '' }}>Emergency Transfer</option>
                    </select>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Temperature Readings Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-list"></i>
                Temperature Readings
            </h5>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-danger btn-sm" onclick="toggleBulkMode()">
                    <i class="bi bi-check-square"></i> Bulk Actions
                </button>
                <button class="btn btn-outline-secondary btn-sm" onclick="refreshTable()">
                    <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
            </div>
        </div>

        <!-- Bulk Actions Panel -->
        <div id="bulkActionsPanel" class="bulk-actions d-none">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <span class="fw-semibold">Bulk Actions:</span>
                    <span id="selectedCount" class="text-muted">0 selected</span>
                </div>
                <div class="btn-group">
                    <button class="btn btn-danger btn-sm" onclick="bulkDeleteReadings()" disabled id="bulkDeleteBtn">
                        <i class="bi bi-trash"></i> Delete Selected
                    </button>
                    <button class="btn btn-secondary btn-sm" onclick="cancelBulkMode()">
                        <i class="bi bi-x"></i> Cancel
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body">
            @if($readings->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover" id="readingsTable">
                        <thead>
                            <tr>
                                <th class="bulk-select-column d-none">
                                    <input type="checkbox" id="selectAll" class="form-check-input">
                                </th>
                                <th>Machine</th>
                                <th>Temperature</th>
                                <th>Recorded At</th>
                                <th>Reading Type</th>
                                <th>Anomalies</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($readings as $reading)
                                <tr class="reading-row {{ $reading->anomalies->count() > 0 ? 'reading-with-anomalies' : 'reading-normal' }}">
                                    <td class="bulk-select-column d-none">
                                        <input type="checkbox" class="form-check-input reading-checkbox" value="{{ $reading->id }}">
                                    </td>
                                    <td>
                                        <div>
                                            <strong>{{ $reading->machine->name }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $reading->machine->branch->name }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="fw-semibold">{{ $reading->formatted_temperature }}</span>
                                        <br>
                                        <small class="badge bg-{{ $reading->status === 'normal' ? 'success' : ($reading->status === 'warning' ? 'warning' : 'danger') }}">
                                            {{ ucfirst($reading->status) }}
                                        </small>
                                    </td>
                                    <td>
                                        <div>
                                            {{ $reading->recorded_at->format('d/m/Y H:i') }}
                                            <br>
                                            <small class="text-muted">{{ $reading->recorded_at->diffForHumans() }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $reading->reading_type)) }}</span>
                                    </td>
                                    <td>
                                        @if($reading->anomalies->count() > 0)
                                            <span class="badge bg-danger">{{ $reading->anomalies->count() }} anomalies</span>
                                            <br>
                                            <small class="text-muted">
                                                @foreach($reading->anomalies->take(2) as $anomaly)
                                                    {{ $anomaly->type_name }}@if(!$loop->last), @endif
                                                @endforeach
                                                @if($reading->anomalies->count() > 2)
                                                    , +{{ $reading->anomalies->count() - 2 }} more
                                                @endif
                                            </small>
                                        @else
                                            <span class="text-muted">No anomalies</span>
                                        @endif
                                    </td>
                                    <td class="table-actions-cell">
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-danger"
                                                    onclick="deleteReading({{ $reading->id }})"
                                                    title="Delete Reading">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                            @if($reading->anomalies->count() > 0)
                                                <a href="{{ route('anomalies.show', $reading->anomalies->first()) }}"
                                                   class="btn btn-outline-primary" title="View Anomalies">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="pagination-wrapper">
                    <div class="pagination-info">
                        Showing {{ $readings->firstItem() ?? 0 }} to {{ $readings->lastItem() ?? 0 }} of {{ $readings->total() }} entries
                    </div>

                    @if($readings->hasPages())
                    <nav aria-label="Temperature readings pagination">
                        <ul class="pagination pagination-custom mb-0">
                            {{-- Previous Page Link --}}
                            @if($readings->onFirstPage())
                                <li class="page-item disabled">
                                    <span class="page-link">
                                        <i class="bi bi-chevron-left"></i> Previous
                                    </span>
                                </li>
                            @else
                                <li class="page-item">
                                    <a class="page-link" href="{{ $readings->previousPageUrl() }}{{ request()->getQueryString() ? '&' . http_build_query(request()->except('page')) : '' }}" rel="prev">
                                        <i class="bi bi-chevron-left"></i> Previous
                                    </a>
                                </li>
                            @endif

                            {{-- First Page --}}
                            @if($readings->currentPage() > 3)
                                <li class="page-item">
                                    <a class="page-link" href="{{ $readings->url(1) }}{{ request()->getQueryString() ? '&' . http_build_query(request()->except('page')) : '' }}">1</a>
                                </li>
                                @if($readings->currentPage() > 4)
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                @endif
                            @endif

                            {{-- Pagination Elements --}}
                            @php
                                $start = max(1, $readings->currentPage() - 2);
                                $end = min($readings->lastPage(), $readings->currentPage() + 2);
                            @endphp

                            @for($page = $start; $page <= $end; $page++)
                                @if($page == $readings->currentPage())
                                    <li class="page-item active">
                                        <span class="page-link">{{ $page }}</span>
                                    </li>
                                @else
                                    <li class="page-item">
                                        <a class="page-link" href="{{ $readings->url($page) }}{{ request()->getQueryString() ? '&' . http_build_query(request()->except('page')) : '' }}">{{ $page }}</a>
                                    </li>
                                @endif
                            @endfor

                            {{-- Last Page --}}
                            @if($readings->currentPage() < $readings->lastPage() - 2)
                                @if($readings->currentPage() < $readings->lastPage() - 3)
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                @endif
                                <li class="page-item">
                                    <a class="page-link" href="{{ $readings->url($readings->lastPage()) }}{{ request()->getQueryString() ? '&' . http_build_query(request()->except('page')) : '' }}">{{ $readings->lastPage() }}</a>
                                </li>
                            @endif

                            {{-- Next Page Link --}}
                            @if($readings->hasMorePages())
                                <li class="page-item">
                                    <a class="page-link" href="{{ $readings->nextPageUrl() }}{{ request()->getQueryString() ? '&' . http_build_query(request()->except('page')) : '' }}" rel="next">
                                        Next <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            @else
                                <li class="page-item disabled">
                                    <span class="page-link">
                                        Next <i class="bi bi-chevron-right"></i>
                                    </span>
                                </li>
                            @endif
                        </ul>
                    </nav>
                    @endif
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-thermometer display-4 text-muted"></i>
                    <h4 class="mt-3">No Temperature Readings Found</h4>
                    <p class="text-muted">No temperature readings found with the current filters.</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteReadingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete Temperature Reading</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i>
                    <strong>Warning!</strong> This action will permanently delete the temperature reading and update related anomalies.
                </div>
                <p>Are you sure you want to delete this temperature reading?</p>
                <div id="readingDetails"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteReading">
                    <i class="bi bi-trash"></i> Delete Reading
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Delete Modal -->
<div class="modal fade" id="bulkDeleteModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bulk Delete Temperature Readings</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i>
                    <strong>Warning!</strong> This action will permanently delete multiple temperature readings and update related anomalies.
                </div>
                <p>Are you sure you want to delete <span id="bulkDeleteCount"></span> temperature readings?</p>
                <div id="bulkDeleteList" style="max-height: 300px; overflow-y: auto;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmBulkDelete">
                    <i class="bi bi-trash"></i> Delete All Selected
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let bulkModeActive = false;
let selectedReadings = new Set();
let pendingDeleteReadingId = null;

// Toggle bulk mode
function toggleBulkMode() {
    bulkModeActive = !bulkModeActive;
    const bulkColumns = document.querySelectorAll('.bulk-select-column');
    const bulkPanel = document.getElementById('bulkActionsPanel');

    bulkColumns.forEach(col => {
        col.classList.toggle('d-none', !bulkModeActive);
    });

    bulkPanel.classList.toggle('d-none', !bulkModeActive);

    if (!bulkModeActive) {
        clearSelections();
    }
}

function cancelBulkMode() {
    bulkModeActive = false;
    const bulkColumns = document.querySelectorAll('.bulk-select-column');
    const bulkPanel = document.getElementById('bulkActionsPanel');

    bulkColumns.forEach(col => col.classList.add('d-none'));
    bulkPanel.classList.add('d-none');
    clearSelections();
}

function clearSelections() {
    selectedReadings.clear();
    document.querySelectorAll('.reading-checkbox').forEach(cb => cb.checked = false);
    document.getElementById('selectAll').checked = false;
    updateBulkUI();
}

function updateBulkUI() {
    const count = selectedReadings.size;
    document.getElementById('selectedCount').textContent = `${count} selected`;
    document.getElementById('bulkDeleteBtn').disabled = count === 0;
}

// Event listeners for checkboxes
document.addEventListener('DOMContentLoaded', function() {
    // Select all checkbox
    document.getElementById('selectAll').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.reading-checkbox');
        const isChecked = this.checked;

        checkboxes.forEach(cb => {
            cb.checked = isChecked;
            const readingId = cb.value;
            if (isChecked) {
                selectedReadings.add(readingId);
            } else {
                selectedReadings.delete(readingId);
            }
        });

        updateBulkUI();
    });

    // Individual checkboxes
    document.querySelectorAll('.reading-checkbox').forEach(cb => {
        cb.addEventListener('change', function() {
            const readingId = this.value;
            if (this.checked) {
                selectedReadings.add(readingId);
            } else {
                selectedReadings.delete(readingId);
            }
            updateBulkUI();
        });
    });

    // Fix modal backdrop issue
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.addEventListener('hidden.bs.modal', function() {
            // Remove any lingering backdrop
            const backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach(backdrop => backdrop.remove());

            // Remove modal-open class from body
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        });
    });
});

// Delete single reading
function deleteReading(readingId) {
    pendingDeleteReadingId = readingId;

    // Find the reading row to get details
    const row = document.querySelector(`.reading-checkbox[value="${readingId}"]`).closest('tr');
    const machine = row.cells[1].querySelector('strong').textContent;
    const temperature = row.cells[2].querySelector('.fw-semibold').textContent;
    const recordedAt = row.cells[3].textContent.trim().split('\n')[0];
    const readingType = row.cells[4].textContent.trim();

    document.getElementById('readingDetails').innerHTML = `
        <div class="card">
            <div class="card-body">
                <strong>Temperature:</strong> ${temperature}<br>
                <strong>Machine:</strong> ${machine}<br>
                <strong>Recorded:</strong> ${recordedAt}<br>
                <strong>Reading Type:</strong> ${readingType}
            </div>
        </div>
    `;

    const modal = new bootstrap.Modal(document.getElementById('deleteReadingModal'));
    modal.show();
}

// Confirm delete reading
document.getElementById('confirmDeleteReading').addEventListener('click', function() {
    if (!pendingDeleteReadingId) return;

    const btn = this;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Deleting...';

    fetch(`/anomalies/temperature-readings/${pendingDeleteReadingId}/delete`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', data.message);
            // Hide modal first, then reload
            const modal = bootstrap.Modal.getInstance(document.getElementById('deleteReadingModal'));
            modal.hide();

            // Clean up backdrop
            const backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach(backdrop => backdrop.remove());

            setTimeout(() => {
                location.reload();
            }, 500);
        } else {
            showToast('error', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('error', 'Failed to delete temperature reading');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        pendingDeleteReadingId = null;
    });
});

// Bulk delete readings
function bulkDeleteReadings() {
    if (selectedReadings.size === 0) return;

    const readingIds = Array.from(selectedReadings);
    document.getElementById('bulkDeleteCount').textContent = readingIds.length;

    // Show list of readings to be deleted
    let listHtml = '<div class="list-group">';
    readingIds.forEach(id => {
        const row = document.querySelector(`.reading-checkbox[value="${id}"]`).closest('tr');
        const machine = row.cells[1].querySelector('strong').textContent;
        const temperature = row.cells[2].querySelector('.fw-semibold').textContent;
        const recordedAt = row.cells[3].textContent.trim().split('\n')[0];

        listHtml += `
            <div class="list-group-item">
                <strong>${temperature}</strong> - ${machine} - ${recordedAt}
            </div>
        `;
    });
    listHtml += '</div>';

    document.getElementById('bulkDeleteList').innerHTML = listHtml;

    const modal = new bootstrap.Modal(document.getElementById('bulkDeleteModal'));
    modal.show();
}

// Confirm bulk delete
document.getElementById('confirmBulkDelete').addEventListener('click', function() {
    if (selectedReadings.size === 0) return;

    const btn = this;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Deleting...';

    const readingIds = Array.from(selectedReadings);

    fetch('/anomalies/temperature-readings/bulk-delete', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            reading_ids: readingIds
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', data.message);
            // Hide modal first, then reload
            const modal = bootstrap.Modal.getInstance(document.getElementById('bulkDeleteModal'));
            modal.hide();

            // Clean up backdrop
            const backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach(backdrop => backdrop.remove());

            setTimeout(() => {
                location.reload();
            }, 500);
        } else {
            showToast('error', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('error', 'Failed to delete temperature readings');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
});

function refreshTable() {
    location.reload();
}

function exportReadings() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'true');
    window.open(`${window.location.pathname}?${params.toString()}`, '_blank');
}

// Toast notification function
function showToast(type, message) {
    const toastContainer = document.getElementById('toastContainer') || createToastContainer();
    const toastId = 'toast-' + Date.now();

    const toastHtml = `
        <div class="toast" id="${toastId}" role="alert">
            <div class="toast-header">
                <i class="bi ${type === 'success' ? 'bi-check-circle-fill text-success' : 'bi-exclamation-circle-fill text-danger'} me-2"></i>
                <strong class="me-auto">${type === 'success' ? 'Success' : 'Error'}</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">${message}</div>
        </div>
    `;

    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement);
    toast.show();

    toastElement.addEventListener('hidden.bs.toast', () => {
        toastElement.remove();
    });
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toastContainer';
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = '1050';
    document.body.appendChild(container);
    return container;
}
</script>
@endpush
