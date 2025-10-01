<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceRecommendation;
use App\Models\Machine;
use App\Models\Branch;
use App\Services\AnalyticsService;
use App\Services\PdfProcessingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MaintenanceController extends Controller
{
    protected $analyticsService;
    protected $pdfService;

    public function __construct(AnalyticsService $analyticsService, PdfProcessingService $pdfService)
    {
        $this->analyticsService = $analyticsService;
        $this->pdfService = $pdfService;
    }

    public function index(Request $request)
    {
        $query = MaintenanceRecommendation::with(['machine.branch'])
            ->orderBy('recommended_date')
            ->orderBy('priority');

        // Apply filters
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('priority') && $request->priority) {
            $query->where('priority', $request->priority);
        }

        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        if ($request->has('machine_id') && $request->machine_id) {
            $query->where('machine_id', $request->machine_id);
        }

        if ($request->has('date_from') && $request->date_from) {
            $query->where('recommended_date', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->where('recommended_date', '<=', $request->date_to);
        }

        $recommendations = $query->paginate(20);

        // Get filter options
        $machines = Machine::with('branch')->where('is_active', true)->get();
        $branches = Branch::where('is_active', true)->get();

        // Statistics
        $stats = [
            'pending' => MaintenanceRecommendation::where('status', 'pending')->count(),
            'scheduled' => MaintenanceRecommendation::where('status', 'scheduled')->count(),
            'in_progress' => MaintenanceRecommendation::where('status', 'in_progress')->count(),
            'completed' => MaintenanceRecommendation::where('status', 'completed')->count(),
            'overdue' => MaintenanceRecommendation::where('status', 'pending')
                ->where('recommended_date', '<', now()->toDateString())
                ->count(),
            'this_week' => MaintenanceRecommendation::whereBetween('recommended_date', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])->count(),
            'this_month' => MaintenanceRecommendation::whereMonth('recommended_date', now()->month)
                ->whereYear('recommended_date', now()->year)
                ->count(),
            'critical' => MaintenanceRecommendation::where('priority', 'critical')->count(),
        ];

        return view('maintenance.index', compact('recommendations', 'machines', 'branches', 'stats'));
    }

    public function create()
    {
        $machines = Machine::with('branch')->where('is_active', true)->get();
        return view('maintenance.create', compact('machines'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'machine_id' => 'required|exists:machines,id',
            'type' => 'required|in:preventive,predictive,corrective,emergency',
            'priority' => 'required|in:low,medium,high,critical',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'reason' => 'required|string',
            'recommended_date' => 'required|date',
            'estimated_duration_hours' => 'nullable|integer|min:1',
            'estimated_cost' => 'nullable|numeric|min:0',
            'required_parts' => 'nullable|json'
        ]);

        MaintenanceRecommendation::create([
            'machine_id' => $request->machine_id,
            'type' => $request->type,
            'priority' => $request->priority,
            'title' => $request->title,
            'description' => $request->description,
            'reason' => $request->reason,
            'recommended_date' => $request->recommended_date,
            'estimated_duration_hours' => $request->estimated_duration_hours,
            'estimated_cost' => $request->estimated_cost,
            'required_parts' => $request->required_parts ? json_decode($request->required_parts, true) : null,
            'status' => 'pending'
        ]);

        return redirect()->route('maintenance.index')
            ->with('success', 'Maintenance recommendation created successfully.');
    }

    public function show(MaintenanceRecommendation $maintenance)
    {
        $maintenance->load(['machine.branch']);

        // Get machine's recent temperature readings
        $recentReadings = $maintenance->machine->temperatureReadings()
            ->orderBy('recorded_at', 'desc')
            ->limit(50)
            ->get();

        // Get machine's anomaly history
        $recentAnomalies = $maintenance->machine->anomalies()
            ->orderBy('detected_at', 'desc')
            ->limit(10)
            ->get();

        return view('maintenance.show', compact('maintenance', 'recentReadings', 'recentAnomalies'));
    }

    public function edit(MaintenanceRecommendation $maintenance)
    {
        $machines = Machine::with('branch')->where('is_active', true)->get();
        return view('maintenance.edit', compact('maintenance', 'machines'));
    }

    public function update(Request $request, MaintenanceRecommendation $maintenance)
    {
        $request->validate([
            'machine_id' => 'required|exists:machines,id',
            'type' => 'required|in:preventive,predictive,corrective,emergency',
            'priority' => 'required|in:low,medium,high,critical',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'reason' => 'required|string',
            'recommended_date' => 'required|date',
            'estimated_duration_hours' => 'nullable|integer|min:1',
            'estimated_cost' => 'nullable|numeric|min:0',
            'required_parts' => 'nullable|json',
            'status' => 'required|in:pending,scheduled,in_progress,completed,cancelled'
        ]);

        $updateData = $request->only([
            'machine_id', 'type', 'priority', 'title', 'description', 'reason',
            'recommended_date', 'estimated_duration_hours', 'estimated_cost', 'status'
        ]);

        if ($request->required_parts) {
            $updateData['required_parts'] = json_decode($request->required_parts, true);
        }

        $maintenance->update($updateData);

        return redirect()->route('maintenance.show', $maintenance)
            ->with('success', 'Maintenance recommendation updated successfully.');
    }

    public function destroy(MaintenanceRecommendation $maintenance)
    {
        $maintenance->delete();

        return redirect()->route('maintenance.index')
            ->with('success', 'Maintenance recommendation deleted successfully.');
    }

    public function schedule(Request $request, MaintenanceRecommendation $recommendation)
    {
        $request->validate([
            'scheduled_date' => 'required|date|after_or_equal:today'
        ]);

        $recommendation->update([
            'status' => 'scheduled',
            'scheduled_date' => $request->scheduled_date
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Maintenance scheduled successfully.'
        ]);
    }

    public function complete(Request $request, MaintenanceRecommendation $recommendation)
    {
        $request->validate([
            'completion_notes' => 'required|string|max:1000'
        ]);

        $recommendation->update([
            'status' => 'completed',
            'completed_date' => now(),
            'completion_notes' => $request->completion_notes
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Maintenance marked as completed.'
        ]);
    }

    public function exportPdf(Request $request)
    {
        $filters = $request->only(['status', 'priority', 'machine_id', 'date_from', 'date_to']);

        $pdf = $this->pdfService->generateMaintenanceReport($filters);

        $filename = 'maintenance_report_' . now()->format('Y-m-d_H-i-s') . '.pdf';

        return $pdf->download($filename);
    }

    public function apiMaintenanceInsights()
    {
        $insights = $this->analyticsService->getPredictiveMaintenanceInsights();

        // Add summary statistics
        $summary = [
            'total_machines' => Machine::where('is_active', true)->count(),
            'high_risk_machines' => $insights->where('risk_score', '>', 70)->count(),
            'medium_risk_machines' => $insights->whereBetween('risk_score', [40, 70])->count(),
            'low_risk_machines' => $insights->where('risk_score', '<=', 40)->count(),
            'pending_maintenance' => MaintenanceRecommendation::where('status', 'pending')->count(),
            'overdue_maintenance' => MaintenanceRecommendation::where('status', 'pending')
                ->where('recommended_date', '<', now()->toDateString())
                ->count()
        ];

        return response()->json([
            'insights' => $insights,
            'summary' => $summary
        ]);
    }
}
