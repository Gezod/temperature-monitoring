<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Machine;
use App\Services\AnalyticsService;
use App\Services\PdfProcessingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BranchController extends Controller
{
    protected $analyticsService;
    protected $pdfService;

    public function __construct(AnalyticsService $analyticsService, PdfProcessingService $pdfService)
    {
        $this->analyticsService = $analyticsService;
        $this->pdfService = $pdfService;
    }

    public function index()
    {
        $branches = Branch::with(['machines' => function($query) {
            $query->where('is_active', true);
        }])
        ->where('is_active', true)
        ->withCount(['machines as active_machines_count' => function($query) {
            $query->where('is_active', true);
        }])
        ->get();

        // Get performance data for each branch
        $branchPerformance = $this->analyticsService->getBranchPerformanceSummary();

        return view('branches.index', compact('branches', 'branchPerformance'));
    }

    public function create()
    {
        $machines = Machine::with('branch')->where('is_active', true)->get();
        return view('branches.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:branches,code',
            'address' => 'nullable|string|max:500',
            'city' => 'required|string|max:100',
            'region' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'manager' => 'nullable|string|max:255',
            'operating_hours' => 'nullable|string|max:100'
        ]);

        $contactInfo = array_filter([
            'phone' => $request->phone,
            'email' => $request->email,
            'manager' => $request->manager,
            'operating_hours' => $request->operating_hours
        ]);

        Branch::create([
            'name' => $request->name,
            'code' => $request->code,
            'address' => $request->address,
            'city' => $request->city,
            'region' => $request->region,
            'contact_info' => $contactInfo ?: null,
            'is_active' => true
        ]);

        return redirect()->route('branches.index')
            ->with('success', 'Branch created successfully.');
    }

    public function show(Branch $branch)
    {
        $branch->load(['machines.temperatureReadings' => function($query) {
            $query->orderBy('recorded_at', 'desc')->limit(100);
        }]);

        // Get branch statistics
        $stats = [
            'total_machines' => $branch->machines->where('is_active', true)->count(),
            'total_readings' => $branch->temperatureReadings()->count(),
            'avg_temperature' => $branch->temperatureReadings()
                ->where('recorded_at', '>=', now()->subMonth())
                ->avg('temperature'),
            'anomaly_count' => $branch->anomalies()
                ->where('detected_at', '>=', now()->subMonth())
                ->count(),
            'pending_maintenance' => $branch->machines()
                ->whereHas('maintenanceRecommendations', function($query) {
                    $query->where('status', 'pending');
                })
                ->count()
        ];

        // Get recent temperature readings
        $recentReadings = $branch->temperatureReadings()
            ->with(['machine'])
            ->orderBy('recorded_at', 'desc')
            ->limit(20)
            ->get();

        // Get active anomalies
        $activeAnomalies = $branch->anomalies()
            ->with(['machine', 'temperatureReading'])
            ->whereIn('status', ['new', 'acknowledged', 'investigating'])
            ->orderBy('detected_at', 'desc')
            ->limit(10)
            ->get();

        return view('branches.show', compact('branch', 'stats', 'recentReadings', 'activeAnomalies'));
    }

    public function edit(Branch $branch)
    {
        return view('branches.edit', compact('branch'));
    }

    public function update(Request $request, Branch $branch)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:branches,code,' . $branch->id,
            'address' => 'nullable|string|max:500',
            'city' => 'required|string|max:100',
            'region' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'manager' => 'nullable|string|max:255',
            'operating_hours' => 'nullable|string|max:100',
            'is_active' => 'boolean'
        ]);

        $contactInfo = array_filter([
            'phone' => $request->phone,
            'email' => $request->email,
            'manager' => $request->manager,
            'operating_hours' => $request->operating_hours
        ]);

        $branch->update([
            'name' => $request->name,
            'code' => $request->code,
            'address' => $request->address,
            'city' => $request->city,
            'region' => $request->region,
            'contact_info' => $contactInfo ?: null,
            'is_active' => $request->has('is_active')
        ]);

        return redirect()->route('branches.show', $branch)
            ->with('success', 'Branch updated successfully.');
    }

    public function destroy(Branch $branch)
    {
        // Check if branch has active machines
        if ($branch->machines()->where('is_active', true)->count() > 0) {
            return redirect()->route('branches.index')
                ->with('error', 'Cannot delete branch with active machines. Please deactivate machines first.');
        }

        $branch->delete();

        return redirect()->route('branches.index')
            ->with('success', 'Branch deleted successfully.');
    }

    public function performance(Branch $branch)
    {
        // Get detailed performance data for the branch
        $performanceData = $this->analyticsService->getBranchPerformanceSummary()
            ->where('branch.id', $branch->id)
            ->first();

        // Get monthly performance trends
        $monthlyTrends = $branch->monthlySummaries()
            ->with('machine')
            ->where('year', now()->year)
            ->orderBy('month')
            ->get()
            ->groupBy('month')
            ->map(function ($summaries, $month) {
                return [
                    'month' => $month,
                    'month_name' => Carbon::create()->month($month)->format('F'),
                    'avg_temperature' => $summaries->avg('temp_avg'),
                    'min_temperature' => $summaries->min('temp_min'),
                    'max_temperature' => $summaries->max('temp_max'),
                    'total_readings' => $summaries->sum('total_readings'),
                    'anomaly_count' => $summaries->sum('anomaly_count'),
                    'machine_count' => $summaries->count()
                ];
            });

        // Get machine performance comparison
        $machinePerformance = $branch->machines()
            ->with(['monthlySummaries' => function($query) {
                $query->where('year', now()->year);
            }])
            ->where('is_active', true)
            ->get()
            ->map(function ($machine) {
                $summaries = $machine->monthlySummaries;
                return [
                    'machine' => $machine,
                    'avg_temperature' => $summaries->avg('temp_avg'),
                    'total_readings' => $summaries->sum('total_readings'),
                    'anomaly_count' => $summaries->sum('anomaly_count'),
                    'performance_score' => $summaries->avg('performance_score') ?? 0
                ];
            });

        return view('branches.performance', compact('branch', 'performanceData', 'monthlyTrends', 'machinePerformance'));
    }

    public function exportPdf(Branch $branch)
    {
        // Generate comprehensive branch report
        $data = [
            'branch' => $branch,
            'machines' => $branch->machines()->where('is_active', true)->get(),
            'recent_readings' => $branch->temperatureReadings()
                ->with('machine')
                ->orderBy('recorded_at', 'desc')
                ->limit(100)
                ->get(),
            'anomalies' => $branch->anomalies()
                ->with(['machine', 'temperatureReading'])
                ->orderBy('detected_at', 'desc')
                ->limit(50)
                ->get(),
            'maintenance' => $branch->machines()
                ->with('maintenanceRecommendations')
                ->get()
                ->pluck('maintenanceRecommendations')
                ->flatten()
        ];

        $pdf = $this->pdfService->generateBranchReport($data);

        $filename = 'branch_report_' . $branch->code . '_' . now()->format('Y-m-d_H-i-s') . '.pdf';

        return $pdf->download($filename);
    }

    public function apiBranchPerformance()
    {
        $performanceData = $this->analyticsService->getBranchPerformanceSummary();

        $comparisonData = $this->analyticsService->getBranchComparisonData();

        $rankings = $this->analyticsService->getBranchPerformanceRankings();

        return response()->json([
            'performance' => $performanceData,
            'comparison' => $comparisonData,
            'rankings' => $rankings
        ]);
    }
}
