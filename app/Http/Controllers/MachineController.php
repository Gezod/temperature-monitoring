<?php

namespace App\Http\Controllers;

use App\Models\Machine;
use App\Models\Branch;
use App\Services\AnomalyDetectionService;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MachineController extends Controller
{
    protected $anomalyService;
    protected $analyticsService;

    public function __construct(AnomalyDetectionService $anomalyService, AnalyticsService $analyticsService)
    {
        $this->anomalyService = $anomalyService;
        $this->analyticsService = $analyticsService;
    }

    public function index(Request $request)
    {
        $query = Machine::with(['branch'])
            ->orderBy('name');

        // Apply filters
        if ($request->has('branch_id') && $request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('type') && $request->type) {
            $query->where('type', 'like', '%' . $request->type . '%');
        }

        if ($request->has('status') && $request->status) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $machines = $query->paginate(20);

        // Get filter options
        $branches = Branch::where('is_active', true)->get();
        $types = Machine::select('type')->distinct()->pluck('type');

        // Statistics
        $stats = [
            'total' => Machine::count(),
            'active' => Machine::where('is_active', true)->count(),
            'inactive' => Machine::where('is_active', false)->count(),
            'with_anomalies' => Machine::whereHas('anomalies', function($query) {
                $query->whereIn('status', ['new', 'acknowledged', 'investigating']);
            })->count(),
            'needs_maintenance' => Machine::whereHas('maintenanceRecommendations', function($query) {
                $query->where('status', 'pending');
            })->count()
        ];

        return view('machines.index', compact('machines', 'branches', 'types', 'stats'));
    }

    public function create()
    {
        $branches = Branch::where('is_active', true)->get();
        return view('machines.create', compact('branches'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:100',
            'model' => 'nullable|string|max:100',
            'serial_number' => 'nullable|string|max:100',
            'installation_date' => 'nullable|date',
            'specifications' => 'nullable|json',
            'temp_min_normal' => 'required|numeric',
            'temp_max_normal' => 'required|numeric',
            'temp_critical_min' => 'required|numeric',
            'temp_critical_max' => 'required|numeric'
        ]);

        $specifications = null;
        if ($request->specifications) {
            $specifications = json_decode($request->specifications, true);
        }

        Machine::create([
            'branch_id' => $request->branch_id,
            'name' => $request->name,
            'type' => $request->type,
            'model' => $request->model,
            'serial_number' => $request->serial_number,
            'installation_date' => $request->installation_date,
            'specifications' => $specifications,
            'temp_min_normal' => $request->temp_min_normal,
            'temp_max_normal' => $request->temp_max_normal,
            'temp_critical_min' => $request->temp_critical_min,
            'temp_critical_max' => $request->temp_critical_max,
            'is_active' => true
        ]);

        return redirect()->route('machines.index')
            ->with('success', 'Machine created successfully.');
    }

    public function show(Machine $machine)
    {
        $machine->load(['branch']);

        // Get machine statistics
        $stats = [
            'total_readings' => $machine->temperatureReadings()->count(),
            'readings_this_month' => $machine->temperatureReadings()
                ->whereMonth('recorded_at', now()->month)
                ->whereYear('recorded_at', now()->year)
                ->count(),
            'avg_temperature' => $machine->temperatureReadings()
                ->where('recorded_at', '>=', now()->subMonth())
                ->avg('temperature'),
            'anomaly_count' => $machine->anomalies()->count(),
            'active_anomalies' => $machine->anomalies()
                ->whereIn('status', ['new', 'acknowledged', 'investigating'])
                ->count(),
            'pending_maintenance' => $machine->maintenanceRecommendations()
                ->where('status', 'pending')
                ->count(),
            'current_status' => $machine->current_status
        ];

        // Get recent temperature readings
        $recentReadings = $machine->temperatureReadings()
            ->orderBy('recorded_at', 'desc')
            ->limit(50)
            ->get();

        // Get recent anomalies
        $recentAnomalies = $machine->anomalies()
            ->with('temperatureReading')
            ->orderBy('detected_at', 'desc')
            ->limit(10)
            ->get();

        // Get maintenance recommendations
        $maintenanceRecommendations = $machine->maintenanceRecommendations()
            ->orderBy('recommended_date')
            ->limit(10)
            ->get();

        // Get monthly summaries for the current year
        $monthlySummaries = $machine->monthlySummaries()
            ->where('year', now()->year)
            ->orderBy('month')
            ->get();

        return view('machines.show', compact(
            'machine',
            'stats',
            'recentReadings',
            'recentAnomalies',
            'maintenanceRecommendations',
            'monthlySummaries'
        ));
    }

    public function edit(Machine $machine)
    {
        $branches = Branch::where('is_active', true)->get();
        return view('machines.edit', compact('machine', 'branches'));
    }

    public function update(Request $request, Machine $machine)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:100',
            'model' => 'nullable|string|max:100',
            'serial_number' => 'nullable|string|max:100',
            'installation_date' => 'nullable|date',
            'specifications' => 'nullable|json',
            'temp_min_normal' => 'required|numeric',
            'temp_max_normal' => 'required|numeric',
            'temp_critical_min' => 'required|numeric',
            'temp_critical_max' => 'required|numeric',
            'is_active' => 'boolean'
        ]);

        $specifications = null;
        if ($request->specifications) {
            $specifications = json_decode($request->specifications, true);
        }

        $machine->update([
            'branch_id' => $request->branch_id,
            'name' => $request->name,
            'type' => $request->type,
            'model' => $request->model,
            'serial_number' => $request->serial_number,
            'installation_date' => $request->installation_date,
            'specifications' => $specifications,
            'temp_min_normal' => $request->temp_min_normal,
            'temp_max_normal' => $request->temp_max_normal,
            'temp_critical_min' => $request->temp_critical_min,
            'temp_critical_max' => $request->temp_critical_max,
            'is_active' => $request->has('is_active')
        ]);

        return redirect()->route('machines.show', $machine)
            ->with('success', 'Machine updated successfully.');
    }

    public function destroy(Machine $machine)
    {
        // Check if machine has temperature readings
        if ($machine->temperatureReadings()->count() > 0) {
            return redirect()->route('machines.index')
                ->with('error', 'Cannot delete machine with temperature readings. Please deactivate instead.');
        }

        $machine->delete();

        return redirect()->route('machines.index')
            ->with('success', 'Machine deleted successfully.');
    }

    public function temperatureHistory(Machine $machine, Request $request)
    {
        $query = $machine->temperatureReadings()
            ->orderBy('recorded_at', 'desc');

        // Apply date filters
        if ($request->has('date_from') && $request->date_from) {
            $query->where('recorded_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->where('recorded_at', '<=', $request->date_to . ' 23:59:59');
        }

        $readings = $query->paginate(100);

        // Get temperature statistics
        $allReadings = $machine->temperatureReadings();
        if ($request->date_from) {
            $allReadings->where('recorded_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $allReadings->where('recorded_at', '<=', $request->date_to . ' 23:59:59');
        }

        $temperatureStats = [
            'avg' => $allReadings->avg('temperature'),
            'min' => $allReadings->min('temperature'),
            'max' => $allReadings->max('temperature'),
            'count' => $allReadings->count(),
            'anomaly_count' => $allReadings->where('is_anomaly', true)->count()
        ];

        return view('machines.temperature-history', compact('machine', 'readings', 'temperatureStats'));
    }

    public function maintenanceHistory(Machine $machine)
    {
        $maintenanceHistory = $machine->maintenanceRecommendations()
            ->orderBy('recommended_date', 'desc')
            ->paginate(20);

        // Get maintenance statistics
        $maintenanceStats = [
            'total' => $machine->maintenanceRecommendations()->count(),
            'completed' => $machine->maintenanceRecommendations()->where('status', 'completed')->count(),
            'pending' => $machine->maintenanceRecommendations()->where('status', 'pending')->count(),
            'overdue' => $machine->maintenanceRecommendations()
                ->where('status', 'pending')
                ->where('recommended_date', '<', now()->toDateString())
                ->count(),
            'last_maintenance' => $machine->maintenanceRecommendations()
                ->where('status', 'completed')
                ->latest('completed_date')
                ->first()?->completed_date
        ];

        return view('machines.maintenance-history', compact('machine', 'maintenanceHistory', 'maintenanceStats'));
    }

    public function runAnomalyCheck(Machine $machine)
    {
        try {
            $anomalyCount = $this->anomalyService->checkMachineAnomalies($machine);

            return response()->json([
                'success' => true,
                'message' => "Anomaly check completed. Found {$anomalyCount} anomalies.",
                'anomaly_count' => $anomalyCount
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error running anomaly check: ' . $e->getMessage()
            ], 500);
        }
    }

    public function apiMachineInfo(Machine $machine)
    {
        $machine->load('branch');

        return response()->json([
            'success' => true,
            'machine' => $machine
        ]);
    }
}
