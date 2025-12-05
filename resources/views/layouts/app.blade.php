<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Temperature Monitoring System')</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* Variabel yang ditambahkan untuk kontrol layout */
        :root {
            --sidebar-width: 280px;
            /* Ukuran sidebar disesuaikan agar mirip col-md-3/col-lg-2 di layout grid */
            --navbar-height: 56px;
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
            padding-top: var(--navbar-height);
            /* Padding untuk fixed navbar */
            overflow-x: hidden;
        }

        /* --- Navbar Fixed --- */
        .navbar {
            background: var(--primary-gradient);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1030;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: white !important;
            /* Agar tetap putih */
        }

        .nav-link {
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            transform: translateY(-2px);
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

        .stat-card {
            background: var(--primary-gradient);
            color: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            transition: all 0.3s ease;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
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

        /* Tombol Hamburger di Navbar */
        #sidebarToggle {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            margin-right: 15px;
            transition: all 0.3s ease;
        }

        /* --- Sidebar Fixed (Layout Baru) --- */
        .sidebar-wrapper {
            position: fixed;
            top: var(--navbar-height);
            left: 0;
            width: var(--sidebar-width);
            height: calc(100vh - var(--navbar-height));
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.15);
            transition: transform 0.3s ease;
            z-index: 1020;
            padding: 15px 0;
            margin-top: 25px;
            border-radius: 0 15px 0 0;
            overflow-y: auto;
        }

        /* Status Sidebar Tertutup */
        .sidebar-wrapper.collapsed {
            transform: translateX(calc(0px - var(--sidebar-width)));
        }

        /* Sidebar content styling */
        .sidebar-wrapper .nav-link {
            color: #495057;
            border-radius: 10px;
            margin-right: 10px;
            padding: 12px 16px;
            transition: all 0.3s ease;
        }

        .sidebar-wrapper .nav-link:hover {
            background: linear-gradient(135deg, #667eea20, #764ba220);
            color: #667eea;
            transform: translateX(5px);
        }

        .sidebar-wrapper .nav-link.active {
            background: var(--primary-gradient);
            color: white;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
        }

        /* Gaya untuk kategori sidebar */
        .sidebar-section {
            border-left: 3px solid #667eea;
            padding-left: 15px;
            margin-bottom: 25px;
        }

        .sidebar-section-title {
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            color: #6c757d !important;
            margin-bottom: 10px;
        }

        .sidebar-section .nav-link {
            padding: 10px 16px;
        }

        .sidebar-section .nav-link .menu-description {
            font-size: 0.75rem;
            opacity: 0.8;
            display: block;
            margin-top: 2px;
        }

        /* Indikator menu aktif yang lebih jelas */
        .sidebar-wrapper .nav-link.active::before {
            content: "";
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 70%;
            background: white;
            border-radius: 0 4px 4px 0;
        }

        /* --- Main Content --- */
        .main-content {
            margin-left: var(--sidebar-width);
            /* Jarak default untuk sidebar terbuka */
            padding: 30px;
            transition: margin-left 0.3s ease;

            /* Gaya card main-content dari kode lama */
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            min-height: calc(100vh - 80px);
        }

        /* Main Content Melebar saat Sidebar Tertutup */
        .main-content.expanded {
            margin-left: 0;
        }

        /* Overlay untuk Mobile View (saat sidebar terbuka) */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1010;
            display: none;
            transition: opacity 0.3s ease;
        }

        .sidebar-overlay.active {
            display: block;
        }

        /* Gaya tambahan dari template kedua */
        .form-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .anomaly-row {
            transition: all 0.3s ease;
        }

        .anomaly-row:hover {
            background-color: #f8f9fa;
        }

        .anomaly-row[data-severity="critical"] {
            border-left: 4px solid #dc3545;
        }

        .anomaly-row[data-severity="high"] {
            border-left: 4px solid #fd7e14;
        }

        .anomaly-row[data-severity="medium"] {
            border-left: 4px solid #ffc107;
        }

        .anomaly-row[data-severity="low"] {
            border-left: 4px solid #198754;
        }

        .chart-container {
            position: relative;
            height: 400px;
        }

        /* --- Responsive Design --- */
        @media (max-width: 991.98px) {

            /* Di mobile/tablet */
            .sidebar-wrapper {
                transform: translateX(calc(0px - var(--sidebar-width)));
                box-shadow: 4px 0 20px rgba(0, 0, 0, 0.3);
            }

            /* Kelas 'show' hanya digunakan di mobile untuk membuka sidebar */
            .sidebar-wrapper.show {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                /* Di mobile, content selalu melebar */
                padding: 15px;
            }

            .sidebar-section .nav-link .menu-description {
                display: none;
                /* Sembunyikan deskripsi di mobile untuk menghemat ruang */
            }
        }
    </style>

    @stack('styles')
</head>

<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <button id="sidebarToggle" type="button">
                <i class="bi bi-list"></i>
            </button>

            <a class="navbar-brand" href="{{ route('dashboard') }}">
                <i class="bi bi-thermometer-snow"></i> Temperature Monitor
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="ms-auto d-flex align-items-center">
                    <li class="nav-item dropdown list-unstyled">
                        <a class="nav-link dropdown-toggle text-white me-2" href="#" id="alertsDropdown" role="button"
                            data-bs-toggle="dropdown">
                            <i class="bi bi-bell"></i>
                            <span class="badge bg-danger" id="alertCount">0</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" id="alertsMenu">
                            <li><span class="dropdown-header">No new alerts</span></li>
                        </ul>
                    </li>
                    <a class="nav-link text-white" href="{{ route('profile') }}">
                        <i class="bi bi-person"></i> Profile
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="sidebar-wrapper" id="sidebarWrapper">
        <div class="p-3">
            <!-- Dashboard - Utama -->
            <div class="sidebar-section">
                <h6 class="sidebar-section-title text-uppercase fw-bold small mb-3">
                    <i class="bi bi-house me-2"></i>Utama
                </h6>
                <ul class="nav flex-column gap-2">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                            href="{{ route('dashboard') }}">
                            <i class="bi bi-speedometer2"></i> Dashboard
                            <span class="menu-description">Overview sistem monitoring</span>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Data Master -->
            <div class="sidebar-section">
                <h6 class="sidebar-section-title text-uppercase fw-bold small mb-3">
                    <i class="bi bi-database me-2"></i>Data Master
                </h6>
                <ul class="nav flex-column gap-2">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('branches.*') ? 'active' : '' }}"
                            href="{{ route('branches.index') }}">
                            <div class="d-flex align-items-center justify-center justify-content-between">
                                <div>
                                    <i class="bi bi-building"></i> Cabang

                                </div>
                                <p class="border {{ request()->routeIs('branches.*') ? 'active bg-white text-dark' : '' }} border-dark rounded-circle py-1 px-3 d-inline-block">1</p>
                            </div>
                            <span class="menu-description">Kelola data cabang</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('machines.*') ? 'active' : '' }}"
                            href="{{ route('machines.index') }}">
                            <div class="d-flex align-items-center justify-center justify-content-between">
                                <div>
                                    <i class="bi bi-cpu"></i> Mesin
                                </div>
                                <p class="border {{ request()->routeIs('machines.*') ? 'active bg-white text-dark' : '' }} border-dark rounded-circle py-1 px-3 d-inline-block">2</p>
                            </div>
                            <span class="menu-description">Kelola data mesin</span>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Monitoring -->
            <div class="sidebar-section">
                <h6 class="sidebar-section-title text-uppercase fw-bold small mb-3">
                    <i class="bi bi-display me-2"></i>Monitoring
                </h6>
                <ul class="nav flex-column gap-2">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('temperature.*') ? 'active' : '' }}"
                            href="{{ route('temperature.index') }}">
                            <div class="d-flex align-items-center justify-center justify-content-between">
                                <div>
                                    <i class="bi bi-thermometer"></i> Data Temperature
                                </div>
                                <p class="border {{ request()->routeIs('temperature*') ? 'active bg-white text-dark' : '' }} border-dark rounded-circle py-1 px-3 d-inline-block">3</p>
                            </div>
                            <span class="menu-description">Monitoring suhu real-time</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('analytics') ? 'active' : '' }}"
                            href="{{ route('analytics') }}">
                            <div class="d-flex align-items-center justify-center justify-content-between">
                                <div>
                                    <i class="bi bi-graph-up"></i> Analisis
                                </div>
                                <p class="border {{ request()->routeIs('analytics*') ? 'active bg-white text-dark' : '' }} border-dark rounded-circle py-1 px-3 d-inline-block">4</p>
                            </div>
                            <span class="menu-description">Analisis data temperature</span>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Laporan & Alert -->
            <div class="sidebar-section">
                <h6 class="sidebar-section-title text-uppercase fw-bold small mb-3">
                    <i class="bi bi-flag me-2"></i>Laporan & Alert
                </h6>
                <ul class="nav flex-column gap-2">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('anomalies.*') ? 'active' : '' }}"
                            href="{{ route('anomalies.index') }}">
                            <div class="d-flex align-items-center justify-center justify-content-between">
                                <div>
                                    <i class="bi bi-exclamation-triangle"></i> Anomali
                                </div>
                                <p class="border {{ request()->routeIs('anomalies*') ? 'active bg-white text-dark' : '' }} border-dark rounded-circle py-1 px-3 d-inline-block">5</p>
                            </div>
                            <span class="menu-description">Data temperature tidak normal</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('branch-comparison') ? 'active' : '' }}"
                            href="{{ route('branch-comparison') }}">
                            <div class="d-flex align-items-center justify-center justify-content-between">
                                <div>
                                    <i class="bi bi-bar-chart"></i> Perbandingan
                                </div>
                                <p class="border {{ request()->routeIs('branch-comparison*') ? 'active bg-white text-dark' : '' }} border-dark rounded-circle py-1 px-3 d-inline-block">6</p>
                            </div>
                            <span class="menu-description">Perbandingan antar cabang</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="main-content mt-4" id="mainContent">
                    <!-- Success Messages -->
                    @if(session('success'))
                        <script>
                            document.addEventListener('DOMContentLoaded', function () {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Berhasil!',
                                    text: '{{ session('success') }}',
                                    timer: 3000,
                                    showConfirmButton: false
                                });
                            });
                        </script>
                    @endif

                    <!-- Error Messages -->
                    @if(session('error'))
                        <script>
                            document.addEventListener('DOMContentLoaded', function () {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: '{{ session('error') }}',
                                    confirmButtonText: 'OK'
                                });
                            });
                        </script>
                    @endif

                    <!-- Validation Errors -->
                    @if($errors->any())
                        <script>
                            document.addEventListener('DOMContentLoaded', function () {
                                let errorMessages = @json($errors->all());
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Validation Error!',
                                    html: errorMessages.join('<br>'),
                                    confirmButtonText: 'OK'
                                });
                            });
                        </script>
                    @endif

                    <!-- Alert Messages (untuk kompatibilitas dengan template lama) -->
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

    <!-- Modals Section -->
    @yield('modals')

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // --- Logika Toggle Sidebar ---
            const sidebarWrapper = document.getElementById('sidebarWrapper');
            const mainContent = document.getElementById('mainContent');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const overlay = document.getElementById('sidebarOverlay');

            // Fungsi untuk mengaktifkan/menonaktifkan sidebar
            function toggleSidebar() {
                if (window.innerWidth >= 992) { // Desktop View
                    sidebarWrapper.classList.toggle('collapsed');
                    mainContent.classList.toggle('expanded');
                } else { // Mobile View
                    sidebarWrapper.classList.toggle('show');
                    overlay.classList.toggle('active');
                }
            }

            sidebarToggle.addEventListener('click', toggleSidebar);

            // Tutup sidebar mobile saat overlay diklik
            overlay.addEventListener('click', function () {
                sidebarWrapper.classList.remove('show');
                overlay.classList.remove('active');
            });

            // Inisialisasi awal saat dimuat
            if (window.innerWidth < 992) {
                // Di mobile, sidebar dimulai tertutup dan content selalu full width
                sidebarWrapper.classList.add('collapsed');
                mainContent.classList.add('expanded');
            } else {
                // Di desktop, sidebar dimulai terbuka dan content di sebelah kanan
                sidebarWrapper.classList.remove('collapsed');
                mainContent.classList.remove('expanded');
            }

            // Atur ulang layout saat window di-resize
            window.addEventListener('resize', function () {
                if (window.innerWidth >= 992) {
                    // Pastikan di desktop, overlay dan kelas show ditutup
                    overlay.classList.remove('active');
                    sidebarWrapper.classList.remove('show');
                    // Tentukan kembali status collapsed berdasarkan kondisi terakhir (jika tidak ada, anggap terbuka)
                    if (!sidebarWrapper.classList.contains('collapsed')) {
                        mainContent.classList.remove('expanded');
                    }
                } else {
                    // Pastikan di mobile, content selalu full width
                    mainContent.classList.add('expanded');
                    sidebarWrapper.classList.remove('show'); // Pastikan tertutup saat transisi ke mobile
                }
            });
            // --- Akhir Logika Toggle Sidebar ---

            // Tambahkan tooltip untuk menu sidebar
            const sidebarLinks = document.querySelectorAll('.sidebar-wrapper .nav-link');

            sidebarLinks.forEach(link => {
                const description = link.querySelector('.menu-description');
                if (description) {
                    link.setAttribute('data-bs-toggle', 'tooltip');
                    link.setAttribute('data-bs-placement', 'right');
                    link.setAttribute('title', description.textContent);
                }
            });

            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Auto-refresh functionality
            function startAutoRefresh() {
                setInterval(function () {
                    if (document.getElementById('auto-refresh')?.checked) {
                        location.reload();
                    }
                }, 30000); // 30 seconds
            }

            startAutoRefresh();
            loadAlerts();
        });

        // Load system alerts
        function loadAlerts() {
            // Implementation would fetch alerts via AJAX
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

        // Global SweetAlert Functions
        // Confirm Delete
        function confirmDelete(form) {
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Data ini akan dihapus secara permanen!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        }

        // Success Toast
        function showSuccessToast(message) {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });

            Toast.fire({
                icon: 'success',
                title: message
            });
        }

        // Error Toast
        function showErrorToast(message) {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });

            Toast.fire({
                icon: 'error',
                title: message
            });
        }
    </script>

    @stack('scripts')
</body>

</html>