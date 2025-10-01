<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Temperature Monitoring System')</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            --danger-gradient: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);
            --warning-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        .navbar {
            background: var(--primary-gradient);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }

        .nav-link {
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            transform: translateY(-2px);
        }

        .sidebar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            min-height: calc(100vh - 100px);
        }

        .sidebar .nav-link {
            color: #495057;
            border-radius: 10px;
            margin: 2px 0;
            padding: 12px 16px;
            transition: all 0.3s ease;
        }

        .sidebar .nav-link:hover {
            background: linear-gradient(135deg, #667eea20, #764ba220);
            color: #667eea;
            transform: translateX(5px);
        }

        .sidebar .nav-link.active {
            background: var(--primary-gradient);
            color: white;
            font-weight: 600;
        }

        .main-content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            min-height: calc(100vh - 100px);
            padding: 30px;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        }

        .card-header {
            background: var(--secondary-gradient);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            font-weight: 600;
        }

        .btn-primary {
            background: var(--primary-gradient);
            border: none;
            border-radius: 10px;
            font-weight: 600;
            padding: 10px 20px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-success {
            background: var(--success-gradient);
            border: none;
        }

        .btn-danger {
            background: var(--danger-gradient);
            border: none;
        }

        .btn-warning {
            background: var(--warning-gradient);
            border: none;
        }

        .stat-card {
            background: var(--primary-gradient);
            color: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 1rem;
            opacity: 0.9;
        }

        .alert {
            border: none;
            border-radius: 12px;
            padding: 15px 20px;
        }

        .table {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .table thead th {
            background: var(--secondary-gradient);
            color: white;
            border: none;
            font-weight: 600;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .badge {
            font-size: 0.8rem;
            padding: 6px 12px;
            border-radius: 20px;
        }

        .modal-content {
            border-radius: 15px;
            border: none;
        }

        .modal-header {
            background: var(--primary-gradient);
            color: white;
            border-radius: 15px 15px 0 0;
        }

        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .anomaly-critical { background-color: #dc3545; color: white; }
        .anomaly-high { background-color: #fd7e14; color: white; }
        .anomaly-medium { background-color: #ffc107; color: black; }
        .anomaly-low { background-color: #198754; color: white; }

        .status-normal { color: #198754; }
        .status-warning { color: #fd7e14; }
        .status-critical { color: #dc3545; }

        @media (max-width: 768px) {
            .sidebar {
                margin-bottom: 20px;
            }
            .main-content {
                margin-top: 0;
            }
        }
    </style>

    @stack('styles')
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ route('dashboard') }}">
                <i class="bi bi-thermometer-snow"></i> Temperature Monitor
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="alertsDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-bell"></i>
                            <span class="badge bg-danger" id="alertCount">0</span>
                        </a>
                        <ul class="dropdown-menu" id="alertsMenu">
                            <li><span class="dropdown-header">No new alerts</span></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('profile') }}">
                            <i class="bi bi-person"></i> Profile
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2">
                <div class="sidebar p-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('temperature.*') ? 'active' : '' }}" href="{{ route('temperature.index') }}">
                                <i class="bi bi-thermometer"></i> Temperature Data
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('analytics') ? 'active' : '' }}" href="{{ route('analytics') }}">
                                <i class="bi bi-graph-up"></i> Analytics
                            </a>
                        </li>
                        {{-- <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('anomalies') ? 'active' : '' }}" href="{{ route('anomalies') }}">
                                <i class="bi bi-exclamation-triangle"></i> Anomalies
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('maintenance') ? 'active' : '' }}" href="{{ route('maintenance') }}">
                                <i class="bi bi-tools"></i> Maintenance
                            </a>
                        </li> --}}
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('branch-comparison') ? 'active' : '' }}" href="{{ route('branch-comparison') }}">
                                <i class="bi bi-bar-chart"></i> Branch Comparison
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('machines.*') ? 'active' : '' }}" href="{{ route('machines.index') }}">
                                <i class="bi bi-cpu"></i> Machines
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('branches.*') ? 'active' : '' }}" href="{{ route('branches.index') }}">
                                <i class="bi bi-building"></i> Branches
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="main-content">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle"></i> {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-circle"></i> {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-circle"></i>
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @yield('content')
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Global JavaScript -->
    <script>
        // Auto-refresh functionality
        function startAutoRefresh() {
            setInterval(function() {
                if (document.getElementById('auto-refresh')?.checked) {
                    location.reload();
                }
            }, 30000); // 30 seconds
        }

        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            startAutoRefresh();
            loadAlerts();
        });

        // Load system alerts
        function loadAlerts() {
            // Implementation would fetch alerts via AJAX
            // For now, simulated
        }

        // Temperature status helper
        function getTemperatureStatus(temp, minNormal, maxNormal, minCritical, maxCritical) {
            if (temp < minCritical || temp > maxCritical) return 'critical';
            if (temp < minNormal || temp > maxNormal) return 'warning';
            return 'normal';
        }

        // Format temperature display
        function formatTemperature(temp) {
            return parseFloat(temp).toFixed(1) + '°C';
        }

        // Download functionality
        function downloadChart(chartId, filename) {
            const canvas = document.getElementById(chartId);
            if (canvas) {
                const link = document.createElement('a');
                link.download = filename + '_' + new Date().toISOString().slice(0, 10) + '.png';
                link.href = canvas.toDataURL();
                link.click();
            }
        }
    </script>

    @stack('scripts')
</body>
</html>
