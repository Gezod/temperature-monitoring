@extends('layouts.app')

@section('title', 'Branch Management')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="bi bi-building text-primary"></i> Manajemen Cabang
    </h1>
    <div class="btn-group">
        <a href="{{ route('branches.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Add Branch
        </a>
        <a href="{{ route('branch-comparison') }}" class="btn btn-info">
            <i class="bi bi-bar-chart"></i> Compare Branches
        </a>
    </div>
</div>

<!-- Branch Performance Overview -->
<div class="row mb-4">
    @foreach($branchPerformance as $performance)
        <div class="col-md-6 col-lg-4 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h5 class="card-title mb-0">{{ $performance['branch']->name }}</h5>
                        <span class="badge bg-{{ $performance['performance_score'] >= 80 ? 'success' : ($performance['performance_score'] >= 60 ? 'warning' : 'danger') }}">
                            {{ number_format($performance['performance_score'], 1) }}%
                        </span>
                    </div>

                    <div class="row text-center mb-3">
                        <div class="col-4">
                            <div class="mb-2">
                                <h4 class="mb-0 text-primary">{{ $performance['machine_count'] }}</h4>
                                <small class="text-muted">Machines</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="mb-2">
                                <h4 class="mb-0 text-info">{{ number_format($performance['avg_temperature'] ?? 0, 1) }}°C</h4>
                                <small class="text-muted">Avg Temp</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="mb-2">
                                <h4 class="mb-0 text-warning">{{ $performance['anomaly_count'] }}</h4>
                                <small class="text-muted">Anomalies</small>
                            </div>
                        </div>
                    </div>

                    <div class="progress mb-2" style="height: 8px;">
                        <div class="progress-bar bg-{{ $performance['performance_score'] >= 80 ? 'success' : ($performance['performance_score'] >= 60 ? 'warning' : 'danger') }}"
                             style="width: {{ $performance['performance_score'] }}%">
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            {{ number_format($performance['total_readings']) }} readings
                        </small>
                        <div class="btn-group btn-group-sm">
                            <a href="{{ route('branches.show', $performance['branch']) }}"
                               class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('branches.performance', $performance['branch']) }}"
                               class="btn btn-outline-info btn-sm">
                                <i class="bi bi-graph-up"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<!-- Branches Table -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-list-ul"></i> All Branches
            <span class="badge bg-secondary ms-2">{{ $branches->count() }} total</span>
        </h5>
    </div>
    <div class="card-body">
        @if($branches->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Branch</th>
                            <th>Code</th>
                            <th>Location</th>
                            <th>Active Machines</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($branches as $branch)
                            <tr>
                                <td>
                                    <strong>{{ $branch->name }}</strong><br>
                                    @if($branch->contact_info)
                                        <small class="text-muted">
                                            @if(isset($branch->contact_info['phone']))
                                                <i class="bi bi-telephone"></i> {{ $branch->contact_info['phone'] }}
                                            @endif
                                        </small>
                                    @endif
                                </td>
                                <td>
                                    <code>{{ $branch->code }}</code>
                                </td>
                                <td>
                                    <strong>{{ $branch->city }}</strong>
                                    @if($branch->region)
                                        <br><small class="text-muted">{{ $branch->region }}</small>
                                    @endif
                                    @if($branch->address)
                                        <br><small class="text-muted">{{ Str::limit($branch->address, 30) }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-primary">{{ $branch->active_machines_count }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $branch->is_active ? 'success' : 'secondary' }}">
                                        {{ $branch->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('branches.show', $branch) }}"
                                           class="btn btn-outline-info" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('branches.performance', $branch) }}"
                                           class="btn btn-outline-success" title="Performance">
                                            <i class="bi bi-graph-up"></i>
                                        </a>
                                        <a href="{{ route('branches.edit', $branch) }}"
                                           class="btn btn-outline-secondary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button class="btn btn-outline-primary"
                                                onclick="exportBranchReport({{ $branch->id }})"
                                                title="Export Report">
                                            <i class="bi bi-download"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-5">
                <i class="bi bi-building display-4 text-muted"></i>
                <p class="text-muted mt-2">No branches found.</p>
                <a href="{{ route('branches.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i> Create First Branch
                </a>
            </div>
        @endif
    </div>
</div>

@endsection

@push('scripts')
<script>
function exportBranchReport(branchId) {
    window.open(`/branches/${branchId}/export-pdf`, '_blank');
}
</script>
@endpush
