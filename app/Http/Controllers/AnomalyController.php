<?php

namespace App\Http\Controllers;

use App\Models\Anomaly;
use App\Models\Machine;
use App\Models\Branch;
use App\Models\Temperature;
use App\Services\AnomalyDetectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnomalyController extends Controller
{
    protected $anomalyService;

    public function __construct(AnomalyDetectionService $anomalyService)
    {
        $this->anomalyService = $anomalyService;
    }

    public function index(Request $request)
    {
        $query = Anomaly::with(['machine.branch'])
            ->orderBy('detected_at', 'desc');

        // Apply filters
        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('machine_id')) {
            $query->where('machine_id', $request->machine_id);
        }

        if ($request->filled('date_from')) {
            $query->where('detected_at', '>=', $request->date_from . ' 00:00:00');
        }

        if ($request->filled('date_to')) {
            $query->where('detected_at', '<=', $request->date_to . ' 23:59:59');
        }

        $anomalies = $query->paginate(20);

        // Get filter options
        $machines = Machine::with('branch')->where('is_active', true)->get();
        $branches = Branch::where('is_active', true)->get();

        // Statistics
        $stats = [
            'total' => Anomaly::count(),
            'new' => Anomaly::where('status', 'new')->count(),
            'acknowledged' => Anomaly::where('status', 'acknowledged')->count(),
            'investigating' => Anomaly::where('status', 'investigating')->count(),
            'resolved' => Anomaly::where('status', 'resolved')->count(),
            'critical' => Anomaly::where('severity', 'critical')->count(),
            'high' => Anomaly::where('severity', 'high')->count(),
            'medium' => Anomaly::where('severity', 'medium')->count(),
            'low' => Anomaly::where('severity', 'low')->count(),
        ];

        // Get chart data for trends
        $trendData = $this->anomalyService->getTrendingAnomalies(30);

        return view('anomalies.index', compact('anomalies', 'machines', 'branches', 'stats', 'trendData'));
    }

    public function show(Anomaly $anomaly)
    {
        // Load essential relationships
        $anomaly->load(['machine.branch', 'temperatureReading']);

        // Get related temperature readings (±2 hours)
        $relatedReadings = Temperature::where('machine_id', $anomaly->machine_id)
            ->whereBetween('timestamp', [
                $anomaly->detected_at->copy()->subHours(2),
                $anomaly->detected_at->copy()->addHours(2)
            ])
            ->orderBy('timestamp')
            ->get();

        // Get similar anomalies
        $similarAnomalies = Anomaly::where('machine_id', $anomaly->machine_id)
            ->where('type', $anomaly->type)
            ->where('id', '!=', $anomaly->id)
            ->orderBy('detected_at', 'desc')
            ->limit(5)
            ->get();

        return view('anomalies.show', compact(
            'anomaly',
            'relatedReadings',
            'similarAnomalies'
        ));
    }


    public function create()
    {
        $machines = Machine::with('branch')->where('is_active', true)->get();
        $temperatures = Temperature::with('machine')
            ->orderBy('timestamp', 'desc')
            ->limit(100)
            ->get();

        return view('anomalies.create', compact('machines', 'temperatures'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'machine_id' => 'required|exists:machines,id',
            'temperature_reading_id' => 'nullable|exists:temperature,id',
            'type' => 'required|in:temperature_high,temperature_low,rapid_change,sensor_error,pattern_deviation,consecutive_abnormal',
            'severity' => 'required|in:low,medium,high,critical',
            'description' => 'required|string|max:1000',
            'possible_causes' => 'nullable|string|max:1000',
            'recommendations' => 'nullable|string|max:1000',
            'detected_at' => 'required|date',
        ]);

        Anomaly::create([
            'machine_id' => $request->machine_id,
            'temperature_reading_id' => $request->temperature_reading_id,
            'type' => $request->type,
            'severity' => $request->severity,
            'description' => $request->description,
            'possible_causes' => $request->possible_causes,
            'recommendations' => $request->recommendations,
            'status' => 'new',
            'detected_at' => $request->detected_at,
        ]);

        return redirect()->route('anomalies.index')
            ->with('success', 'Anomaly created successfully.');
    }

    public function edit(Anomaly $anomaly)
    {
        $machines = Machine::with('branch')->where('is_active', true)->get();
        $temperatures = Temperature::with('machine')
            ->where('machine_id', $anomaly->machine_id)
            ->orderBy('timestamp', 'desc')
            ->limit(50)
            ->get();

        return view('anomalies.edit', compact('anomaly', 'machines', 'temperatures'));
    }

    public function update(Request $request, Anomaly $anomaly)
    {
        $request->validate([
            'status' => 'required|in:new,acknowledged,investigating,resolved,false_positive',
            'resolution_notes' => 'nullable|string|max:1000',
            'severity' => 'sometimes|in:low,medium,high,critical',
            'description' => 'sometimes|string|max:1000',
        ]);

        $updateData = $request->only(['status', 'resolution_notes']);

        if ($request->filled('severity')) {
            $updateData['severity'] = $request->severity;
        }

        if ($request->filled('description')) {
            $updateData['description'] = $request->description;
        }

        if ($request->status === 'resolved') {
            $updateData['resolved_at'] = now();
        }

        $anomaly->update($updateData);

        return redirect()->route('anomalies.show', $anomaly)
            ->with('success', 'Anomaly updated successfully.');
    }

    public function destroy(Anomaly $anomaly)
    {
        $anomaly->delete();

        return redirect()->route('anomalies.index')
            ->with('success', 'Anomaly deleted successfully.');
    }

    public function acknowledge(Request $request, Anomaly $anomaly)
    {
        $request->validate([
            'acknowledged_by' => 'required|string|max:255'
        ]);

        $anomaly->update([
            'status' => 'acknowledged',
            'acknowledged_at' => now(),
            'acknowledged_by' => $request->acknowledged_by
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Anomaly acknowledged successfully.'
        ]);
    }

    public function resolve(Request $request, Anomaly $anomaly)
    {
        $request->validate([
            'resolution_notes' => 'required|string|max:1000'
        ]);

        $anomaly->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolution_notes' => $request->resolution_notes
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Anomaly resolved successfully.'
        ]);
    }

    public function runAnomalyCheck(Request $request)
    {
        $request->validate([
            'machine_id' => 'nullable|exists:machines,id',
            'days' => 'nullable|integer|min:1|max:30'
        ]);

        $days = $request->input('days', 7);
        $fromDate = now()->subDays($days);

        if ($request->machine_id) {
            $machine = Machine::findOrFail($request->machine_id);
            $anomalyCount = $this->anomalyService->checkMachineAnomalies($machine, $fromDate);
            $message = "Anomaly check completed for {$machine->name}. Found {$anomalyCount} anomalies.";
        } else {
            $anomalyCount = $this->anomalyService->checkAllMachines();
            $message = "Global anomaly check completed. Found {$anomalyCount} anomalies across all machines.";
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'anomaly_count' => $anomalyCount
        ]);
    }

    public function getChartData(Request $request)
    {
        $days = $request->input('days', 30);
        $trendData = $this->anomalyService->getTrendingAnomalies($days);

        return response()->json($trendData);
    }

    public function apiAnomalyStats()
    {
        $stats = $this->anomalyService->getAnomalyStatistics();

        // Add recent anomalies
        $recentAnomalies = Anomaly::with(['machine.branch'])
            ->whereIn('status', ['new', 'acknowledged', 'investigating'])
            ->orderBy('detected_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($anomaly) {
                $temperature = null;
                if ($anomaly->temperature_reading_id) {
                    $tempReading = Temperature::find($anomaly->temperature_reading_id);
                    $temperature = $tempReading?->temperature_value;
                }

                return [
                    'id' => $anomaly->id,
                    'machine' => $anomaly->machine->name,
                    'branch' => $anomaly->machine->branch->name,
                    'type' => $anomaly->type_name,
                    'severity' => $anomaly->severity,
                    'detected_at' => $anomaly->detected_at->diffForHumans(),
                    'temperature' => $temperature
                ];
            });

        $stats['recent_anomalies'] = $recentAnomalies;

        return response()->json($stats);
    }
}
