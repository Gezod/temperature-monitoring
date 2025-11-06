<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TemperatureController;
use App\Http\Controllers\TemperatureValidationController;
use App\Http\Controllers\AnomalyController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\MachineController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Dashboard
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// Analytics Routes
Route::get('/analytics', [DashboardController::class, 'analytics'])->name('analytics');
Route::get('/branch-comparison', [DashboardController::class, 'branchComparison'])->name('branch-comparison');
Route::get('/alerts', [DashboardController::class, 'alerts'])->name('alerts');

// Alert Management
Route::patch('/alerts/{id}/read', [DashboardController::class, 'markAlertAsRead'])->name('alerts.read');
Route::patch('/alerts/{id}/dismiss', [DashboardController::class, 'dismissAlert'])->name('alerts.dismiss');

// Temperature Data Routes
Route::resource('temperature', TemperatureController::class);
Route::get('/temperature/date/{date}', [TemperatureController::class, 'showDate'])->name('temperature.show-date');
Route::get('/temperature/{temperature}/edit', [TemperatureController::class, 'edit'])->name('temperature.edit');
Route::post('/temperature/upload-pdf', [TemperatureController::class, 'uploadPdf'])->name('temperature.upload-pdf');
Route::post('/temperature/upload-excel', [TemperatureController::class, 'uploadExcel'])->name('temperature.upload-excel');
Route::get('/temperature/export/pdf', [TemperatureController::class, 'exportPdf'])->name('temperature.export-pdf');
Route::post('/upload-pdf', [TemperatureController::class, 'uploadPdfPy'])->name('temperature.upload-pdf-py');
Route::get('/temperature/chart-data/{machineId}/{date}', [TemperatureController::class, 'getChartData'])->name('temperature.chart-data');

// Temperature Validation Routes
Route::prefix('temperature/validation')->group(function () {
    Route::get('/history', [TemperatureValidationController::class, 'getValidationHistory'])->name('temperature.validation.history');
    Route::get('/{sessionId}/review', [TemperatureValidationController::class, 'reviewValidation'])->name('temperature.validation.review');
    Route::patch('/{sessionId}/update', [TemperatureValidationController::class, 'updateValidationData'])->name('temperature.validation.update');
    Route::post('/{sessionId}/import', [TemperatureValidationController::class, 'importValidatedData'])->name('temperature.validation.import');
    Route::delete('/{sessionId}', [TemperatureValidationController::class, 'deleteValidation'])->name('temperature.validation.delete');
});

// API Routes for machine info
Route::get('/api/machines/{machine}/info', [MachineController::class, 'apiMachineInfo'])->name('api.machine-info');

// Anomaly Management Routes
Route::get('/anomalies', [AnomalyController::class, 'index'])->name('anomalies.index');
Route::get('/anomalies/{anomaly}', [AnomalyController::class, 'show'])->name('anomalies.show');
Route::put('/anomalies/{anomaly}', [AnomalyController::class, 'update'])->name('anomalies.update');
Route::patch('/anomalies/{anomaly}/acknowledge', [AnomalyController::class, 'acknowledge'])->name('anomalies.acknowledge');
Route::patch('/anomalies/{anomaly}/resolve', [AnomalyController::class, 'resolve'])->name('anomalies.resolve');

// Maintenance Management Routes
Route::get('/maintenance', [MaintenanceController::class, 'index'])->name('maintenance.index');
Route::get('/maintenance/create', [MaintenanceController::class, 'create'])->name('maintenance.create');
Route::post('/maintenance', [MaintenanceController::class, 'store'])->name('maintenance.store');
Route::get('/maintenance/{maintenance}', [MaintenanceController::class, 'show'])->name('maintenance.show');
Route::get('/maintenance/{maintenance}/edit', [MaintenanceController::class, 'edit'])->name('maintenance.edit');
Route::put('/maintenance/{maintenance}', [MaintenanceController::class, 'update'])->name('maintenance.update');
Route::delete('/maintenance/{maintenance}', [MaintenanceController::class, 'destroy'])->name('maintenance.destroy');
Route::patch('/maintenance/{recommendation}/schedule', [MaintenanceController::class, 'schedule'])->name('maintenance.schedule');
Route::patch('/maintenance/{recommendation}/complete', [MaintenanceController::class, 'complete'])->name('maintenance.complete');
Route::get('/maintenance/export/pdf', [MaintenanceController::class, 'exportPdf'])->name('maintenance.export-pdf');

// Branch Management Routes
Route::resource('branches', BranchController::class);
Route::get('/branches/{branch}/performance', [BranchController::class, 'performance'])->name('branches.performance');
Route::get('/branches/{branch}/export-pdf', [BranchController::class, 'exportPdf'])->name('branches.export-pdf');

// Machine Management Routes
Route::resource('machines', MachineController::class);
Route::get('/machines/{machine}/temperature-history', [MachineController::class, 'temperatureHistory'])->name('machines.temperature-history');
Route::get('/machines/{machine}/maintenance-history', [MachineController::class, 'maintenanceHistory'])->name('machines.maintenance-history');
Route::post('/machines/{machine}/run-anomaly-check', [MachineController::class, 'runAnomalyCheck'])->name('machines.run-anomaly-check');

// API Routes for AJAX calls
Route::prefix('api')->group(function () {
    Route::get('/temperature-data', [TemperatureController::class, 'apiTemperatureData'])->name('api.temperature-data');
    Route::get('/anomaly-stats', [AnomalyController::class, 'apiAnomalyStats'])->name('api.anomaly-stats');
    Route::get('/branch-performance', [BranchController::class, 'apiBranchPerformance'])->name('api.branch-performance');
    Route::get('/maintenance-insights', [MaintenanceController::class, 'apiMaintenanceInsights'])->name('api.maintenance-insights');
});

// Profile route (placeholder)
Route::get('/profile', function () {
    return view('profile.index');
})->name('profile');
