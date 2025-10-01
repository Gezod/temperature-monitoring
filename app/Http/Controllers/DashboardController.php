<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Machine;
use App\Models\TemperatureReading;
use App\Models\Anomaly;
use App\Models\MaintenanceRecommendation;
use App\Models\SystemAlert;
use App\Services\AnalyticsService;
use App\Services\AnomalyDetectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    protected $analyticsService;
    protected $anomalyService;

    public function __construct(AnalyticsService $analyticsService, AnomalyDetectionService $anomalyService)
    {
        $this->analyticsService = $analyticsService;
        $this->anomalyService = $anomalyService;
    }

    public function index()
    {
        // Overview statistics
        $stats = [
            'total_branches' => Branch::where('is_active', true)->count(),
            'total_machines' => Machine::where('is_active', true)->count(),
            'active_anomalies' => Anomaly::whereIn('status', ['new', 'acknowledged', 'investigating'])->count(),
            'pending_maintenance' => MaintenanceRecommendation::where('status', 'pending')->count(),
            'critical_alerts' => SystemAlert::active()->where('level', 'critical')->count()
        ];

        // Recent temperature readings with status
        $recentReadings = TemperatureReading::with(['machine.branch'])
            ->latest('recorded_at')
            ->limit(10)
            ->get()
            ->map(function ($reading) {
                return [
                    'id' => $reading->id,
                    'machine' => $reading->machine->name,
                    'branch' => $reading->machine->branch->name,
                    'temperature' => $reading->temperature,
                    'recorded_at' => $reading->recorded_at,
                    'status' => $reading->status
                ];
            });

        // Branch performance summary
        $branchPerformance = $this->analyticsService->getBranchPerformanceSummary();

        // Temperature trends (last 30 days)
        $temperatureTrends = $this->analyticsService->getTemperatureTrends(30);

        // Active anomalies
        $activeAnomalies = Anomaly::with(['machine.branch', 'temperatureReading'])
            ->whereIn('status', ['new', 'acknowledged', 'investigating'])
            ->orderBy('detected_at', 'desc')
            ->limit(5)
            ->get();

        // Maintenance alerts
        $maintenanceAlerts = MaintenanceRecommendation::with(['machine.branch'])
            ->where('status', 'pending')
            ->where('recommended_date', '<=', Carbon::now()->addDays(7))
            ->orderBy('priority')
            ->orderBy('recommended_date')
            ->limit(5)
            ->get();

        // System alerts
        $systemAlerts = SystemAlert::active()
            ->unread()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('layouts.dashboard.index', compact(
            'stats',
            'recentReadings',
            'branchPerformance',
            'temperatureTrends',
            'activeAnomalies',
            'maintenanceAlerts',
            'systemAlerts'
        ));
    }

    public function analytics(Request $request)
    {
        $filters = $request->only(['branch_id', 'machine_id', 'date_from', 'date_to', 'chart_type']);

        // Get filtered data
        $analyticsData = $this->analyticsService->getAdvancedAnalytics($filters);

        // Seasonal analysis
        $seasonalAnalysis = $this->analyticsService->getSeasonalAnalysis($filters);

        // Performance comparison
        $performanceComparison = $this->analyticsService->getBranchComparison($filters);

        // Branches and machines for filters
        $branches = Branch::where('is_active', true)->get();
        $machines = Machine::where('is_active', true)->with('branch')->get();

        return view('dashboard.analytics', compact(
            'analyticsData',
            'seasonalAnalysis',
            'performanceComparison',
            'branches',
            'machines',
            'filters'
        ));
    }

    public function anomalies()
    {
        $anomalies = Anomaly::with(['machine.branch', 'temperatureReading'])
            ->orderBy('detected_at', 'desc')
            ->paginate(20);

        $anomalyStats = [
            'total' => Anomaly::count(),
            'new' => Anomaly::where('status', 'new')->count(),
            'investigating' => Anomaly::where('status', 'investigating')->count(),
            'resolved' => Anomaly::where('status', 'resolved')->count(),
            'critical' => Anomaly::where('severity', 'critical')->count(),
        ];

        return view('dashboard.anomalies', compact('anomalies', 'anomalyStats'));
    }

    public function maintenance()
    {
        $recommendations = MaintenanceRecommendation::with(['machine.branch'])
            ->orderBy('recommended_date')
            ->orderBy('priority')
            ->paginate(20);

        $maintenanceStats = [
            'pending' => MaintenanceRecommendation::where('status', 'pending')->count(),
            'scheduled' => MaintenanceRecommendation::where('status', 'scheduled')->count(),
            'overdue' => MaintenanceRecommendation::where('status', 'pending')
                ->where('recommended_date', '<', now()->toDateString())
                ->count(),
            'this_month' => MaintenanceRecommendation::whereMonth('recommended_date', now()->month)
                ->whereYear('recommended_date', now()->year)
                ->count(),
        ];

        // Predictive maintenance insights
        $predictiveInsights = $this->analyticsService->getPredictiveMaintenanceInsights();

        return view('dashboard.maintenance', compact(
            'recommendations',
            'maintenanceStats',
            'predictiveInsights'
        ));
    }

    public function branchComparison()
    {
        $branches = Branch::with(['machines', 'monthlySummaries' => function($query) {
            $query->where('year', now()->year);
        }])->where('is_active', true)->get();

        $comparisonData = $this->analyticsService->getBranchComparisonData();

        $performanceRankings = $this->analyticsService->getBranchPerformanceRankings();

        return view('branches.index', compact(
            'branches',
            'comparisonData',
            'performanceRankings'
        ));
    }

    public function alerts()
    {
        $alerts = SystemAlert::orderBy('created_at', 'desc')->paginate(20);

        return view('dashboard.alerts', compact('alerts'));
    }

    public function markAlertAsRead($id)
    {
        $alert = SystemAlert::findOrFail($id);
        $alert->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }

    public function dismissAlert($id)
    {
        $alert = SystemAlert::findOrFail($id);
        $alert->update(['is_dismissed' => true]);

        return response()->json(['success' => true]);
    }
}
