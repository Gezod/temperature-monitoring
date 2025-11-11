<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title','Temperature Monitoring System')</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    body{font-family:'Segoe UI',sans-serif;background:#f5f7fa;overflow-x:hidden;}
    .navbar{background:linear-gradient(135deg,#667eea,#764ba2);}
    .navbar-brand{font-weight:700;color:#fff!important;}

<<<<<<< HEAD
        .navbar {
            background: var(--primary-gradient);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
=======
    /* === Sidebar === */
    .sidebar{
      position:fixed;
      top:70px;
      left:0;
      width:250px;
      height:calc(100vh - 70px);
      background:rgba(255,255,255,.95);
      box-shadow:4px 0 20px rgba(0,0,0,.1);
      border-radius:0 20px 20px 0;
      transition:transform .3s ease;
      z-index:1000;
      padding:15px;
    }
    .sidebar.collapsed{transform:translateX(-260px);}
    .sidebar .nav-link{color:#495057;border-radius:10px;padding:12px 16px;transition:.3s;}
    .sidebar .nav-link:hover{background:#f3f3ff;color:#667eea;}
    .sidebar .nav-link.active{background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;font-weight:600;}
>>>>>>> fa37243 (Update sidebar toggle  commit by Sikki)

    /* === Main === */
    .main-content{
      margin-left:260px;
      padding:30px;
      transition:margin-left .3s ease;
    }
    .main-content.expanded{margin-left:0;}

    /* === Toggle btn === */
    #sidebarToggle{
      background:#fff;
      border:none;
      border-radius:8px;
      font-size:1.3rem;
      padding:5px 10px;
      margin-right:10px;
    }

    /* Overlay for mobile */
    .overlay{position:fixed;top:70px;left:0;width:100%;height:calc(100vh - 70px);
      background:rgba(0,0,0,.5);display:none;z-index:900;}
    .overlay.active{display:block;}

        .sidebar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
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
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            min-height: calc(100vh - 100px);
            padding: 30px;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
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
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
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

        .form-control,
        .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .anomaly-critical {
            background-color: #dc3545;
            color: white;
        }

        .anomaly-high {
            background-color: #fd7e14;
            color: white;
        }

        .anomaly-medium {
            background-color: #ffc107;
            color: black;
        }

        .anomaly-low {
            background-color: #198754;
            color: white;
        }

        .status-normal {
            color: #198754;
        }

        .status-warning {
            color: #fd7e14;
        }

        .status-critical {
            color: #dc3545;
        }

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

    @media(max-width:992px){
      .sidebar{transform:translateX(-260px);}
      .sidebar.show{transform:translateX(0);}
      .main-content{margin-left:0;}
    }
  </style>

</head>

<body>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="alertsDropdown" role="button"
                            data-bs-toggle="dropdown">
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
                            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                                href="{{ route('dashboard') }}">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('branches.*') ? 'active' : '' }}"
                                href="{{ route('branches.index') }}">
                                <i class="bi bi-building"></i> Cabang (Branches)
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('machines.*') ? 'active' : '' }}"
                                href="{{ route('machines.index') }}">
                                <i class="bi bi-cpu"></i> Mesin (Machines)
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('temperature.*') ? 'active' : '' }}"
                                href="{{ route('temperature.index') }}">
                                <i class="bi bi-thermometer"></i> Temperature Data
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('analytics') ? 'active' : '' }}"
                                href="{{ route('analytics') }}">
                                <i class="bi bi-graph-up"></i> Analisis (Analytics)
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('anomalies.*') ? 'active' : '' }}"
                                href="{{ route('anomalies.index') }}">
                                <i class="bi bi-exclamation-triangle"></i> Anomali (Anomalies)
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('maintenance.*') ? 'active' : '' }}"
                                href="{{ route('maintenance.index') }}">
                                <i class="bi bi-tools"></i> Pemeliharaan (Maintenance)
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('branch-comparison') ? 'active' : '' }}"
                                href="{{ route('branch-comparison') }}">
                                <i class="bi bi-bar-chart"></i> Perbandingan Cabang (Branch Comparison)
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

<nav class="navbar navbar-expand-lg">
  <div class="container-fluid d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center">
      <button id="sidebarToggle"><i class="bi bi-list"></i></button>
      <a class="navbar-brand" href="{{route('dashboard')}}">
        <i class="bi bi-thermometer-snow"></i> Temperature Monitor
      </a>

    </div>
    <a class="nav-link text-white" href="{{route('profile')}}"><i class="bi bi-person"></i> Profile</a>
  </div>
</nav>

<div class="overlay" id="overlay"></div>

    <!-- Global JavaScript -->
    <script>
        // Auto-refresh functionality
        function startAutoRefresh() {
            setInterval(function () {
                if (document.getElementById('auto-refresh')?.checked) {
                    location.reload();
                }
            }, 30000); // 30 seconds
        }

        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function () {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

<!-- Sidebar di luar grid -->
<div class="sidebar" id="sidebar">
  <ul class="nav flex-column">
    <li><a class="nav-link {{request()->routeIs('dashboard')?'active':''}}" href="{{route('dashboard')}}"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
    <li><a class="nav-link {{request()->routeIs('temperature.*')?'active':''}}" href="{{route('temperature.index')}}"><i class="bi bi-thermometer"></i> Temperature Data</a></li>
    <li><a class="nav-link {{request()->routeIs('analytics')?'active':''}}" href="{{route('analytics')}}"><i class="bi bi-graph-up"></i> Analytics</a></li>
    <li><a class="nav-link {{request()->routeIs('anomalies.*')?'active':''}}" href="{{route('anomalies.index')}}"><i class="bi bi-exclamation-triangle"></i> Anomalies</a></li>
    <li><a class="nav-link {{request()->routeIs('maintenance.*')?'active':''}}" href="{{route('maintenance.index')}}"><i class="bi bi-tools"></i> Maintenance</a></li>
    <li><a class="nav-link {{request()->routeIs('branch-comparison')?'active':''}}" href="{{route('branch-comparison')}}"><i class="bi bi-bar-chart"></i> Branch Comparison</a></li>
    <li><a class="nav-link {{request()->routeIs('machines.*')?'active':''}}" href="{{route('machines.index')}}"><i class="bi bi-cpu"></i> Machines</a></li>
    <li><a class="nav-link {{request()->routeIs('branches.*')?'active':''}}" href="{{route('branches.index')}}"><i class="bi bi-building"></i> Branches</a></li>
  </ul>
</div>

<!-- Main di luar grid -->
<div class="main-content" id="mainContent">
  @yield('content')
</div>
>>>>>>> fa37243 (Update sidebar toggle  commit by Sikki)

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const sidebar=document.getElementById("sidebar");
const main=document.getElementById("mainContent");
const btn=document.getElementById("sidebarToggle");
const overlay=document.getElementById("overlay");

btn.onclick=function(){
  if(window.innerWidth<992){
    sidebar.classList.toggle("show");
    overlay.classList.toggle("active");
  }else{
    sidebar.classList.toggle("collapsed");
    main.classList.toggle("expanded");
  }
}
overlay.onclick=function(){
  sidebar.classList.remove("show");
  overlay.classList.remove("active");
};
</script>
</body>

</html>
