<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Machine;
use App\Models\TemperatureReading;
use App\Models\Temperature;
use App\Models\Anomaly;
use App\Models\MaintenanceRecommendation;
use App\Models\SystemAlert;
use App\Services\AnalyticsService;
use App\Services\AnomalyDetectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

        // Recent temperature readings with status - Use Temperature model
        $recentReadings = Temperature::with(['machine.branch'])
            ->latest('timestamp')
            ->limit(10)
            ->get()
            ->map(function ($reading) {
                $machine = $reading->machine;
                $temp = $reading->temperature_value;
                $status = 'normal';

                if ($machine) {
                    if ($temp < $machine->temp_critical_min || $temp > $machine->temp_critical_max) {
                        $status = 'critical';
                    } elseif ($temp < $machine->temp_min_normal || $temp > $machine->temp_max_normal) {
                        $status = 'warning';
                    }
                }

                return [
                    'id' => $reading->id,
                    'machine' => $machine->name ?? 'Unknown',
                    'branch' => $machine->branch->name ?? 'Unknown',
                    'temperature' => $temp,
                    'recorded_at' => $reading->timestamp,
                    'status' => $status
                ];
            });

        // Branch performance summary
        $branchPerformance = $this->analyticsService->getBranchPerformanceSummary();

        // Temperature trends (last 30 days) - Use Temperature model
        $temperatureTrends = Temperature::select(
            DB::raw('DATE(timestamp) as date'),
            DB::raw('AVG(temperature_value) as avg_temperature'),
            DB::raw('MIN(temperature_value) as min_temperature'),
            DB::raw('MAX(temperature_value) as max_temperature'),
            DB::raw('COUNT(*) as reading_count')
        )
            ->where('timestamp', '>=', now()->subDays(90))
            ->groupBy(DB::raw('DATE(timestamp)'))
            ->orderBy('date')
            ->get();

        // Active anomalies
        $activeAnomalies = Anomaly::with(['machine.branch'])
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
            ->where('is_read', false)
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

    $filters['date_from'] = $filters['date_from'] ?? now()->subDays(30)->format('Y-m-d');
    $filters['date_to'] = $filters['date_to'] ?? now()->format('Y-m-d');

    try {
        // Get analytics data
        $analyticsData = $this->analyticsService->getAdvancedAnalytics($filters);
        $seasonalAnalysis = $this->analyticsService->getSeasonalAnalysis($filters);
        $performanceComparison = $this->analyticsService->getBranchComparison($filters);

        // Branches and machines for filters
        $branches = Branch::where('is_active', true)->get();
        $machines = Machine::where('is_active', true)->with('branch')->get();

        // DEBUG: Log untuk memastikan data ada
        Log::info('Analytics Data Count: ' . $analyticsData->count());
        Log::info('First analytics item: ' . json_encode($analyticsData->first()));

        return view('layouts.dashboard.analytics', compact(
            'analyticsData',
            'seasonalAnalysis',
            'performanceComparison',
            'branches',
            'machines',
            'filters'
        ));

    } catch (\Exception $e) {
        Log::error('Analytics Error: ' . $e->getMessage());

        $branches = Branch::where('is_active', true)->get();
        $machines = Machine::where('is_active', true)->with('branch')->get();

        return view('layouts.dashboard.analytics', compact(
            'branches',
            'machines',
            'filters'
        ))->with('error', 'Error loading analytics data: ' . $e->getMessage());
    }
}

    public function anomalies()
    {
        $anomalies = Anomaly::with(['machine.branch'])
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
        $branches = Branch::with(['machines', 'monthlySummaries' => function ($query) {
            $query->where('year', now()->year);
        }])->where('is_active', true)->get();

        $comparisonData = $this->analyticsService->getBranchComparisonData();

        $performanceRankings = $this->analyticsService->getBranchPerformanceRankings();

        // Get branch performance summary for the view
        $branchPerformance = $this->analyticsService->getBranchPerformanceSummary();

        return view('layouts.dashboard.branch-comparison', compact(
            'branches',
            'comparisonData',
            'performanceRankings',
            'branchPerformance'
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
