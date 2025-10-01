@extends('layouts.app')

@section('title', 'Detail Anomaly')

@section('content')
<div class="container mt-4">
    <h1>Detail Anomaly #{{ $anomaly->id }}</h1>

    <div class="card mb-3">
        <div class="card-body">
            <p><strong>Machine:</strong> {{ $anomaly->machine->name }} ({{ $anomaly->machine->branch->name }})</p>
            <p><strong>Type:</strong> {{ $anomaly->type_name }}</p>
            <p><strong>Severity:</strong> {{ ucfirst($anomaly->severity) }}</p>
            <p><strong>Status:</strong> {{ ucfirst($anomaly->status) }}</p>
            <p><strong>Detected At:</strong> {{ $anomaly->detected_at }}</p>
            <p><strong>Temperature:</strong> {{ $anomaly->temperatureReading->temperature ?? '-' }} °C</p>
        </div>
    </div>

    <h5>Related Readings (±2 hours)</h5>
    <ul>
        @foreach($relatedReadings as $reading)
            <li>{{ $reading->recorded_at }} → {{ $reading->temperature }} °C</li>
        @endforeach
    </ul>

    <h5>Similar Anomalies</h5>
    <ul>
        @foreach($similarAnomalies as $similar)
            <li>
                <a href="{{ route('anomalies.show', $similar->id) }}">
                    Anomaly #{{ $similar->id }} ({{ $similar->detected_at }})
                </a>
            </li>
        @endforeach
    </ul>

    <a href="{{ route('anomalies.index') }}" class="btn btn-secondary mt-3">← Back</a>
</div>
@endsection
