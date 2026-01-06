<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TemperatureController;
use App\Http\Controllers\TemperatureValidationController;
use App\Http\Controllers\AnomalyController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\MachineController;
use App\Http\Controllers\AuthUserController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/


Route::get('/', [AuthUserController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthUserController::class, 'login']);
Route::post('/logout', [AuthUserController::class, 'logout'])->middleware('auth')->name('logout');

// Dashboard
Route::middleware('auth')->group(function () {
    //user
    Route::get('/profile-account', [AuthUserController::class, 'profileAccount'])->name('account-profile');
    Route::put('/profile', [AuthUserController::class, 'update'])->name('profile.update');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/analytics', [DashboardController::class, 'analytics'])->name('analytics');
    Route::get('/dashboard/analytics', [DashboardController::class, 'analytics'])->name('dashboard.analytics');

    Route::get('/test-chart', fn() => view('test-chart'));
    Route::get('/branch-comparison', [DashboardController::class, 'branchComparison'])->name('branch-comparison');
    Route::get('/alerts', [DashboardController::class, 'alerts'])->name('alerts');

    Route::prefix('guide')->group(function () {
        Route::get('/', fn() => view('guide.index'))->name('guide.index');
    });
    Route::patch('/alerts/{id}/read', [DashboardController::class, 'markAlertAsRead'])->name('alerts.read');
    Route::patch('/alerts/{id}/dismiss', [DashboardController::class, 'dismissAlert'])->name('alerts.dismiss');


    // Temperature Data
    Route::resource('temperature', TemperatureController::class);
    
    // Route::resource('temperature', TemperatureController::class)->except(['edit']);
    Route::get('/temperature/date/{date}', [TemperatureController::class, 'showDate'])->name('temperature.show-date');
    Route::post('/temperature/upload-pdf', [TemperatureController::class, 'uploadPdf'])->name('temperature.upload-pdf');
    Route::post('/temperature/upload-excel', [TemperatureController::class, 'uploadExcel'])->name('temperature.upload-excel');
    Route::get('/temperature/export/pdf', [TemperatureController::class, 'exportPdf'])->name('temperature.export-pdf');
    Route::post('/upload-pdf', [TemperatureController::class, 'uploadPdfPy'])->name('temperature.upload-pdf-py');
    Route::get('/temperature/chart-data/{machineId}/{date}', [TemperatureController::class, 'getChartData'])->name('temperature.chart-data');
    Route::post('/temperature/validateTemperature/{date}', [TemperatureController::class, 'validateTemperature'])->name('temperature.validate');


    // Temperature Validation
    Route::prefix('temperature/validation')->group(function () {
        Route::get('/history', [TemperatureValidationController::class, 'getValidationHistory'])->name('temperature.validation.history');
        Route::get('/{sessionId}/review', [TemperatureValidationController::class, 'reviewValidation'])->name('temperature.validation.review');
        Route::patch('/{sessionId}/update', [TemperatureValidationController::class, 'updateValidationData'])->name('temperature.validation.update');
        Route::post('/{sessionId}/import', [TemperatureValidationController::class, 'importValidatedData'])->name('temperature.validation.import');
        Route::delete('/{sessionId}', [TemperatureValidationController::class, 'deleteValidation'])->name('temperature.validation.delete');
    });


    // Anomalies
    Route::resource('anomalies', AnomalyController::class);
    Route::patch('/anomalies/{anomaly}/acknowledge', [AnomalyController::class, 'acknowledge'])->name('anomalies.acknowledge');
    Route::patch('/anomalies/{anomaly}/resolve', [AnomalyController::class, 'resolve'])->name('anomalies.resolve');
    Route::post('/anomalies/run-check', [AnomalyController::class, 'runAnomalyCheck'])->name('anomalies.run-check');
    Route::get('/anomalies/chart-data', [AnomalyController::class, 'getChartData'])->name('anomalies.chart-data');

    // ✅ NEW: Temperature Reading Management Routes
    Route::get('/anomalies/manage/temperature-readings', [AnomalyController::class, 'manageTemperatureReadings'])->name('anomalies.manage-temperature-readings');
    Route::delete('/anomalies/temperature-readings/{temperatureReadingId}/delete', [AnomalyController::class, 'deleteTemperatureReading'])->name('anomalies.delete-temperature-reading');
    Route::post('/anomalies/temperature-readings/bulk-delete', [AnomalyController::class, 'bulkDeleteTemperatureReadings'])->name('anomalies.bulk-delete-temperature-readings');

    // ✅ UPDATED: Duplicate prevention routes
    Route::get('/anomalies/duplicate-stats', [AnomalyController::class, 'duplicateStats'])->name('anomalies.duplicate-stats');
    Route::post('/anomalies/cleanup-duplicates', [AnomalyController::class, 'cleanupDuplicates'])->name('anomalies.cleanup-duplicates');
    Route::get('/export', [AnomalyController::class, 'export'])->name('export');

    Route::post('/sync-temperature', function () {
        Artisan::call('sync:temperature-readings');
        return response()->json([
            'status' => 'success',
            'message' => 'Sync executed!'
        ]);
    });

    Route::get('/anomalies/test-transfer', [AnomalyController::class, 'testTransfer'])->name('anomalies.test-transfer');
    Route::post('/anomalies/emergency-transfer', [AnomalyController::class, 'emergencyTransfer'])->name('anomalies.emergency-transfer');
    Route::get('/anomalies/test-transfer-fixed', [AnomalyController::class, 'testTransferFixed'])->name('anomalies.test-transfer-fixed');


    // Maintenance
    Route::resource('maintenance', MaintenanceController::class);
    Route::patch('/maintenance/{recommendation}/schedule', [MaintenanceController::class, 'schedule'])->name('maintenance.schedule');
    Route::patch('/maintenance/{recommendation}/complete', [MaintenanceController::class, 'complete'])->name('maintenance.complete');
    Route::get('/maintenance/export/pdf', [MaintenanceController::class, 'exportPdf'])->name('maintenance.export-pdf');


    // Branches
    Route::resource('branches', BranchController::class);
    Route::get('/branches/{branch}/performance', [BranchController::class, 'performance'])->name('branches.performance');
    Route::get('/branches/{branch}/export-pdf', [BranchController::class, 'exportPdf'])->name('branches.export-pdf');


    // Machines
    Route::resource('machines', MachineController::class);
    Route::get('/machines/{machine}/temperature-history', [MachineController::class, 'temperatureHistory'])->name('machines.temperature-history');
    Route::get('/machines/{machine}/maintenance-history', [MachineController::class, 'maintenanceHistory'])->name('machines.maintenance-history');
    Route::post('/machines/{machine}/run-anomaly-check', [MachineController::class, 'runAnomalyCheck'])->name('machines.run-anomaly-check');


    // AJAX API
    Route::prefix('api')->group(function () {
        Route::get('/temperature-data', [TemperatureController::class, 'apiTemperatureData'])->name('api.temperature-data');
        Route::get('/anomaly-stats', [AnomalyController::class, 'apiAnomalyStats'])->name('api.anomaly-stats');
        Route::get('/branch-performance', [BranchController::class, 'apiBranchPerformance'])->name('api.branch-performance');
        Route::get('/maintenance-insights', [MaintenanceController::class, 'apiMaintenanceInsights'])->name('api.maintenance-insights');
        Route::get('/machines/{machine}/info', [MachineController::class, 'apiMachineInfo'])->name('api.machine-info');
        Route::get('/temperature-readings/{machineId}', [TemperatureController::class, 'getTemperatureReadingsForMachine'])->name('api.temperature-readings');

        // ✅ NEW: Temperature reading details API
        Route::get('/temperature-readings/{readingId}/details', function ($readingId) {
            try {
                $reading = \App\Models\TemperatureReading::with('machine')->findOrFail($readingId);
                return response()->json([
                    'success' => true,
                    'reading' => [
                        'id' => $reading->id,
                        'temperature' => $reading->formatted_temperature,
                        'machine' => $reading->machine->name,
                        'recorded_at' => $reading->formatted_recorded_at,
                        'reading_type' => ucfirst(str_replace('_', ' ', $reading->reading_type))
                    ]
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reading not found'
                ], 404);
            }
        })->name('api.temperature-reading-details');
    });


    // Profile page
    Route::get('/profile', [AuthUserController::class,'profileAccount'])->name('profile');
    Route::get('/tambah-user',[AuthUserController::class,'showTambahUser'])->name('user.tambah');
    Route::post('/store-user',[AuthUserController::class,'store'])->name('user.store');

    // Debug Analytics
    Route::get('/debug-analytics', function () {

        $service = app(\App\Services\AnalyticsService::class);

        $filters = [
            'date_from' => now()->subDays(30)->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d')
        ];

        $results = [
            'temperature_count' => \App\Models\Temperature::count(),
            'branch_count' => \App\Models\Branch::where('is_active', true)->count(),
            'machine_count' => \App\Models\Machine::where('is_active', true)->count(),
            'advanced_analytics' => $service->getAdvancedAnalytics($filters),
            'seasonal_analysis' => $service->getSeasonalAnalysis($filters),
            'branch_comparison' => $service->getBranchComparison($filters),
        ];

        dd($results);
    });
});