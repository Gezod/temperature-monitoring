<?php

namespace App\Http\Controllers;

use App\Models\Anomaly;
use App\Models\Machine;
use App\Models\Branch;
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
        $query = Anomaly::with(['machine.branch', 'temperatureReading'])
            ->orderBy('detected_at', 'desc');

        // Apply filters
        if ($request->has('severity') && $request->severity) {
            $query->where('severity', $request->severity);
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('machine_id') && $request->machine_id) {
            $query->where('machine_id', $request->machine_id);
        }

        if ($request->has('date_from') && $request->date_from) {
            $query->where('detected_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
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

        return view('anomalies.index', compact('anomalies', 'machines', 'branches', 'stats'));
    }

    public function show(Anomaly $anomaly)
    {
        $anomaly->load(['machine.branch', 'temperatureReading']);

        // Get related temperature readings for context
        $relatedReadings = $anomaly->machine->temperatureReadings()
            ->where('recorded_at', '>=', $anomaly->detected_at->subHours(2))
            ->where('recorded_at', '<=', $anomaly->detected_at->addHours(2))
            ->orderBy('recorded_at')
            ->get();

        // Get similar anomalies
        $similarAnomalies = Anomaly::where('machine_id', $anomaly->machine_id)
            ->where('type', $anomaly->type)
            ->where('id', '!=', $anomaly->id)
            ->orderBy('detected_at', 'desc')
            ->limit(5)
            ->get();

        return view('anomalies.show', compact('anomaly', 'relatedReadings', 'similarAnomalies'));
    }

    public function update(Request $request, Anomaly $anomaly)
    {
        $request->validate([
            'status' => 'required|in:new,acknowledged,investigating,resolved,false_positive',
            'resolution_notes' => 'nullable|string|max:1000'
        ]);

        $updateData = [
            'status' => $request->status,
            'resolution_notes' => $request->resolution_notes
        ];

        if ($request->status === 'resolved') {
            $updateData['resolved_at'] = now();
        }

        $anomaly->update($updateData);

        return redirect()->route('anomalies.show', $anomaly)
            ->with('success', 'Anomaly status updated successfully.');
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
                return [
                    'id' => $anomaly->id,
                    'machine' => $anomaly->machine->name,
                    'branch' => $anomaly->machine->branch->name,
                    'type' => $anomaly->type_name,
                    'severity' => $anomaly->severity,
                    'detected_at' => $anomaly->detected_at->diffForHumans(),
                    'temperature' => $anomaly->temperatureReading->temperature ?? null
                ];
            });

        $stats['recent_anomalies'] = $recentAnomalies;

        return response()->json($stats);
    }
}
