<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TemperatureController;
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
Route::get('/anomalies', [DashboardController::class, 'anomalies'])->name('anomalies');
Route::get('/maintenance', [DashboardController::class, 'maintenance'])->name('maintenance');
Route::get('/branch-comparison', [DashboardController::class, 'branchComparison'])->name('branch-comparison');
Route::get('/alerts', [DashboardController::class, 'alerts'])->name('alerts');

// Alert Management
Route::patch('/alerts/{id}/read', [DashboardController::class, 'markAlertAsRead'])->name('alerts.read');
Route::patch('/alerts/{id}/dismiss', [DashboardController::class, 'dismissAlert'])->name('alerts.dismiss');

// Temperature Data Routes
Route::resource('temperature', TemperatureController::class);
Route::post('/temperature/upload-pdf', [TemperatureController::class, 'uploadPdf'])->name('temperature.upload-pdf');
Route::post('/temperature/upload-excel', [TemperatureController::class, 'uploadExcel'])->name('temperature.upload-excel');
Route::get('/temperature/export/pdf', [TemperatureController::class, 'exportPdf'])->name('temperature.export-pdf');

// Anomaly Management Routes
Route::resource('anomalies', AnomalyController::class)->only(['index', 'show', 'update']);
Route::patch('/anomalies/{anomaly}/acknowledge', [AnomalyController::class, 'acknowledge'])->name('anomalies.acknowledge');
Route::patch('/anomalies/{anomaly}/resolve', [AnomalyController::class, 'resolve'])->name('anomalies.resolve');

// Maintenance Management Routes
Route::resource('maintenance', MaintenanceController::class);
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
