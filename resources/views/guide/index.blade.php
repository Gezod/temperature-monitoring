@extends('layouts.app')

@section('content')
<div class="container py-5">
    <!-- Header Section -->
    <div class="text-center mb-5">
        <h1 class="fw-bold text-primary display-5 mb-3">
            <i class="bi bi-book me-2"></i>Panduan Penggunaan Sistem
        </h1>
        <p class="lead text-muted mb-4">
            Sistem Monitoring Suhu Mesin - Dokumentasi Lengkap
        </p>
        <div class="alert alert-info bg-light border-info">
            <div class="d-flex align-items-center">
                <i class="bi bi-info-circle-fill fs-4 me-3 text-info"></i>
                <div>
                    <p class="mb-0">
                        Sistem ini digunakan untuk memantau suhu mesin dari berbagai cabang,
                        mendeteksi anomali, mengelola data temperature, dan menyediakan
                        analitik untuk pengambilan keputusan.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Navigation -->
    <div class="card shadow-sm mb-5">
        <div class="card-header bg-light">
            <h5 class="mb-0">
                <i class="bi bi-list-columns me-2"></i>Navigasi Cepat
            </h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                @php
                    $modules = [
                        ['Dashboard', 'bi-speedometer2', 'primary'],
                        ['Analytics', 'bi-graph-up', 'success'],
                        ['Temperature', 'bi-thermometer-half', 'danger'],
                        ['Validation', 'bi-check-circle', 'warning'],
                        ['Anomaly', 'bi-exclamation-triangle', 'warning'],
                        ['Branch', 'bi-building', 'secondary'],
                        ['Machines', 'bi-cpu', 'dark'],
                        ['Export/Import', 'bi-arrow-left-right', 'success'],
                        ['Additional', 'bi-plus-circle', 'info'],
                    ];
                @endphp

                @foreach($modules as $module)
                    @php
                        // Membuat ID yang konsisten untuk semua modul
                        $moduleId = strtolower(str_replace(['/', ' ', '&'], ['', '-', '-and'], $module[0]));
                    @endphp
                    <div class="col-md-6 col-lg-4">
                        <a href="#section-{{ $moduleId }}"
                           class="text-decoration-none">
                            <div class="card module-card h-100 border-0 shadow-sm hover-lift">
                                <div class="card-body text-center p-3">
                                    <div class="icon-wrapper mb-2">
                                        <i class="bi {{ $module[1] }} fs-2 text-{{ $module[2] }}"></i>
                                    </div>
                                    <h6 class="fw-bold mb-0">{{ $module[0] }}</h6>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Content Sections -->
    <div class="guide-content">
        @php
            $sections = [
                [
                    'id' => 'dashboard',
                    'title' => '1. Dashboard',
                    'icon' => 'bi-speedometer2',
                    'description' => 'Halaman Dashboard menampilkan ringkasan performa sistem seperti:',
                    'features' => [
                        'Total data suhu yang direkam',
                        'Jumlah branch aktif',
                        'Jumlah mesin aktif',
                        'Grafik tren suhu keseluruhan',
                        'Notifikasi alert dan rekomendasi'
                    ]
                ],
                [
                    'id' => 'analytics',
                    'title' => '2. Analytics',
                    'icon' => 'bi-graph-up',
                    'description' => 'Menu <strong>Analytics</strong> berisi fitur analisis lanjutan seperti:',
                    'features' => [
                        'Tren musiman berdasarkan tanggal',
                        'Analisis cabang berdasarkan performa suhu',
                        'Perbandingan antar cabang (branch comparison)',
                        'Heatmap dan grafik frekuensi anomali'
                    ]
                ],
                [
                    'id' => 'temperature',
                    'title' => '3. Manajemen Suhu (Temperature)',
                    'icon' => 'bi-thermometer-half',
                    'description' => 'Modul temperatur digunakan untuk mengelola data suhu dari mesin:',
                    'features' => [
                        'Melihat daftar temperatur semua mesin',
                        'Upload file PDF suhu',
                        'Upload file Excel suhu',
                        'Mengekspor PDF laporan suhu',
                        'Melihat grafik suhu per mesin per tanggal',
                        'Validasi data suhu yang masuk'
                    ]
                ],
                [
                    'id' => 'validation',
                    'title' => '4. Validasi Suhu',
                    'icon' => 'bi-check-circle',
                    'description' => 'Modul validasi berfungsi untuk:',
                    'features' => [
                        'Melihat riwayat sesi validasi',
                        'Mereview data hasil validasi',
                        'Mengupdate data hasil validasi',
                        'Mengimpor data validasi ke database utama',
                        'Menghapus sesi validasi tertentu'
                    ]
                ],
                [
                    'id' => 'anomaly',
                    'title' => '5. Anomali Mesin',
                    'icon' => 'bi-exclamation-triangle',
                    'description' => 'Sistem mendeteksi kenaikan suhu tidak normal dan menghasilkan rekomendasi:',
                    'features' => [
                        'Melihat daftar anomali',
                        'Menandai anomali sebagai sudah dibaca (acknowledge)',
                        'Menandai anomali sebagai resolved',
                        'Melihat grafik anomali',
                        'Menjalankan manual anomaly-check',
                        'Mengelola temperature reading yang menyebabkan anomali',
                        'Membersihkan data duplikat'
                    ]
                ],
                [
                    'id' => 'branch',
                    'title' => '6. Branch (Cabang)',
                    'icon' => 'bi-building',
                    'description' => 'Modul ini mengelola daftar cabang:',
                    'features' => [
                        'CRUD cabang',
                        'Melihat performa cabang',
                        'Mengekspor PDF performa',
                        'Analisis perbandingan antar cabang'
                    ]
                ],
                [
                    'id' => 'machines',
                    'title' => '7. Machines (Mesin)',
                    'icon' => 'bi-cpu',
                    'description' => 'Mengelola daftar mesin:',
                    'features' => [
                        'CRUD mesin',
                        'Melihat grafik suhu mesin',
                        'Melihat riwayat maintenance mesin',
                        'Menjalankan pengecekan anomali khusus mesin'
                    ]
                ],
                [
                    'id' => 'export-import',
                    'title' => '8. Export & Import',
                    'icon' => 'bi-arrow-left-right',
                    'description' => 'Sistem memiliki fungsi ekspor sebagai PDF dan impor file Excel/PDF:',
                    'features' => [
                        'Export suhu → PDF',
                        'Export branch performance → PDF',
                        'Import validasi suhu'
                    ]
                ],
                [
                    'id' => 'additional',
                    'title' => '9. Fitur Tambahan',
                    'icon' => 'bi-plus-circle',
                    'description' => 'Fitur-fitur tambahan yang tersedia:',
                    'features' => [
                        'Debug analytics',
                        'Transfer data darurat (emergency transfer)',
                        'Sync temperature readings (Artisan command)',
                        'Penghapusan bulk temperature reading'
                    ]
                ]
            ];
        @endphp

        @foreach($sections as $section)
            <div class="section-card card shadow-sm mb-4" id="section-{{ $section['id'] }}">
                <div class="card-header bg-gradient-primary text-white">
                    <div class="d-flex align-items-center">
                        <i class="bi {{ $section['icon'] }} fs-4 me-3"></i>
                        <h4 class="mb-0">{{ $section['title'] }}</h4>
                    </div>
                </div>
                <div class="card-body">
                    <p class="card-text">{!! $section['description'] !!}</p>
                    <div class="features-list ps-3">
                        @foreach($section['features'] as $feature)
                            <div class="d-flex align-items-start mb-2">
                                <i class="bi bi-check-circle-fill text-success me-2 mt-1"></i>
                                <span>{{ $feature }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Footer -->
    <div class="card shadow-sm mt-5">
        <div class="card-body text-center py-4">
            <div class="mb-3">
                <i class="bi bi-file-earmark-text fs-1 text-muted"></i>
            </div>
            <p class="text-muted mb-2">
                <i class="bi bi-clock-history me-1"></i>
                Dokumentasi otomatis. Terakhir diperbarui:
                <span class="fw-bold">{{ now()->format('d M Y') }}</span>
            </p>
            <small class="text-muted">
                Sistem Monitoring Suhu Mesin v1.0
            </small>
        </div>
    </div>
</div>

<style>
    .module-card {
        transition: all 0.3s ease;
        border-radius: 10px;
    }

    .module-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
    }

    .icon-wrapper {
        width: 60px;
        height: 60px;
        margin: 0 auto;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background-color: rgba(var(--bs-primary-rgb), 0.1);
    }

    .section-card {
        border-radius: 12px;
        overflow: hidden;
        border: none;
        scroll-margin-top: 20px; /* Untuk smooth scroll */
    }

    .section-card .card-header {
        border-radius: 0 !important;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-bottom: none;
    }

    .features-list {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 1.5rem;
        margin-top: 1rem;
    }

    .hover-lift:hover {
        transform: translateY(-2px);
        transition: transform 0.2s ease-in-out;
    }

    .bg-gradient-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    @media (max-width: 768px) {
        .display-5 {
            font-size: 2rem;
        }

        .section-card .card-header {
            padding: 1rem;
        }

        .section-card .card-header h4 {
            font-size: 1.25rem;
        }
    }
</style>

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

<script>
    // Smooth scroll untuk navigasi
    document.addEventListener('DOMContentLoaded', function() {
        const links = document.querySelectorAll('a[href^="#section-"]');

        links.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();

                const targetId = this.getAttribute('href');
                const targetElement = document.querySelector(targetId);

                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 80, // Offset untuk navbar
                        behavior: 'smooth'
                    });
                }
            });
        });
    });
</script>

@endsection
