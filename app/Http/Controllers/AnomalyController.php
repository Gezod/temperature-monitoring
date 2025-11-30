<?php

namespace App\Http\Controllers;

use App\Models\Anomaly;
use App\Models\Machine;
use App\Models\Branch;
use App\Models\TemperatureReading;
use App\Models\Temperature;
use App\Services\AnomalyDetectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        $relatedReadings = TemperatureReading::where('machine_id', $anomaly->machine_id)
            ->whereBetween('recorded_at', [
                $anomaly->detected_at->copy()->subHours(2),
                $anomaly->detected_at->copy()->addHours(2)
            ])
            ->orderBy('recorded_at')
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

        $temperatureReadings = TemperatureReading::with('machine')
            ->orderBy('recorded_at', 'desc')
            ->limit(100)
            ->get();

        return view('anomalies.create', compact('machines', 'temperatureReadings'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'machine_id' => 'required|exists:machines,id',
            'temperature_reading_id' => 'nullable|exists:temperature_readings,id',
            'type' => 'required|in:temperature_high,temperature_low,rapid_change,sensor_error,pattern_deviation,consecutive_abnormal',
            'severity' => 'required|in:low,medium,high,critical',
            'description' => 'required|string|max:1000',
            'possible_causes' => 'nullable|string|max:1000',
            'recommendations' => 'nullable|string|max:1000',
            'detected_at' => 'required|date',
        ]);

        try {
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
        } catch (\Exception $e) {
            Log::error('Failed to create anomaly: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to create anomaly: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function edit(Anomaly $anomaly)
    {
        $machines = Machine::with('branch')->where('is_active', true)->get();

        $temperatureReadings = TemperatureReading::with('machine')
            ->where('machine_id', $anomaly->machine_id)
            ->orderBy('recorded_at', 'desc')
            ->limit(50)
            ->get();

        return view('anomalies.edit', compact('anomaly', 'machines', 'temperatureReadings'));
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
        try {
            $anomaly->delete();

            return redirect()->route('anomalies.index')
                ->with('success', 'Anomaly deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to delete anomaly: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to delete anomaly: ' . $e->getMessage());
        }
    }

    public function acknowledge(Request $request, Anomaly $anomaly)
    {
        $request->validate([
            'acknowledged_by' => 'required|string|max:255'
        ]);

        try {
            $anomaly->update([
                'status' => 'acknowledged',
                'acknowledged_at' => now(),
                'acknowledged_by' => $request->acknowledged_by
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Anomaly acknowledged successfully.'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to acknowledge anomaly: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to acknowledge anomaly: ' . $e->getMessage()
            ], 500);
        }
    }

    public function resolve(Request $request, Anomaly $anomaly)
    {
        $request->validate([
            'resolution_notes' => 'required|string|max:1000'
        ]);

        try {
            $anomaly->update([
                'status' => 'resolved',
                'resolved_at' => now(),
                'resolution_notes' => $request->resolution_notes
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Anomaly resolved successfully.'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to resolve anomaly: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to resolve anomaly: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ IMPROVED: Run anomaly check dengan duplicate prevention
     */
    public function runAnomalyCheck(Request $request)
    {
        $request->validate([
            'machine_id' => 'nullable|exists:machines,id',
            'days' => 'nullable|integer|min:1|max:30'
        ]);

        $days = $request->input('days', 7);

        try {
            // TRANSFER DATA DARI TEMPERATURE KE TEMPERATURE_READINGS
            $transferResult = $this->transferTemperatureDataWithDuplicateCheck($request->machine_id, $days);

            $transferredCount = $transferResult['count'];
            $message = $transferResult['message'];

            // JALANKAN DETEKSI ANOMALI
            if ($request->machine_id) {
                $machine = Machine::findOrFail($request->machine_id);
                $anomalyCount = $this->anomalyService->checkMachineAnomalies($machine, now()->subDays($days));
                $message .= " Anomaly check completed for {$machine->name}. Found {$anomalyCount} new anomalies.";
            } else {
                $anomalyCount = $this->anomalyService->checkUnanalyzedReadings($days);
                $message .= " Global anomaly check completed. Found {$anomalyCount} new anomalies across all machines.";
            }

            // GET DUPLICATE STATISTICS
            $duplicateStats = $this->anomalyService->getDuplicateCheckStats($request->machine_id, $days);

            Log::info("Anomaly check completed with duplicate prevention: {$message}");

            return response()->json([
                'success' => true,
                'message' => $message,
                'transferred_count' => $transferredCount,
                'anomaly_count' => $anomalyCount,
                'duplicate_stats' => $duplicateStats
            ]);

        } catch (\Exception $e) {
            Log::error('Anomaly check failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Anomaly check failed: ' . $e->getMessage(),
                'transferred_count' => 0,
                'anomaly_count' => 0
            ], 500);
        }
    }

    /**
     * ✅ IMPROVED: Transfer dengan duplicate check yang lebih baik
     */
    private function transferTemperatureDataWithDuplicateCheck($machineId = null, $days = 7)
    {
        DB::beginTransaction();
        try {
            $fromDate = Carbon::now()->subDays($days);

            Log::info("Starting temperature data transfer with duplicate check from: {$fromDate}");

            // QUERY DATA
            $query = Temperature::where('temperature_value', '>', 5)
                               ->where('created_at', '>=', $fromDate);

            if ($machineId) {
                $query->where('machine_id', $machineId);
            }

            $temperatures = $query->get();
            Log::info("Found {$temperatures->count()} temperature records to process");

            $transferredCount = 0;
            $skippedCount = 0;
            $duplicateCount = 0;

            foreach ($temperatures as $temperature) {
                try {
                    $recordedAt = $this->getSimpleRecordedAt($temperature);
                    $tempValue = (float) $temperature->temperature_value;
                    $machineIdValue = $temperature->machine_id;

                    // VALIDASI DASAR
                    if (!$recordedAt || $tempValue <= 5 || !$machineIdValue) {
                        $skippedCount++;
                        continue;
                    }

                    // ✅ IMPROVED DUPLICATE CHECK - Check lebih ketat
                    $existing = TemperatureReading::where('machine_id', $machineIdValue)
                        ->where('temperature', $tempValue)
                        ->whereBetween('recorded_at', [
                            $recordedAt->copy()->subMinutes(5), // Toleransi ±5 menit
                            $recordedAt->copy()->addMinutes(5)
                        ])
                        ->first();

                    if ($existing) {
                        $duplicateCount++;
                        continue;
                    }

                    // TRANSFER DATA
                    TemperatureReading::create([
                        'machine_id' => $machineIdValue,
                        'recorded_at' => $recordedAt,
                        'temperature' => $tempValue,
                        'reading_type' => 'transfer',
                        'source_file' => 'temp_table',
                        'is_anomaly' => false
                    ]);

                    $transferredCount++;

                } catch (\Exception $e) {
                    Log::error("Failed to transfer record ID {$temperature->id}: " . $e->getMessage());
                    $skippedCount++;
                }
            }

            DB::commit();

            $message = "Successfully transferred {$transferredCount} temperature readings";
            if ($duplicateCount > 0) {
                $message .= ", skipped {$duplicateCount} duplicates";
            }
            if ($skippedCount > 0) {
                $message .= ", skipped {$skippedCount} invalid records";
            }

            Log::info($message);

            return [
                'success' => true,
                'count' => $transferredCount,
                'skipped' => $skippedCount,
                'duplicates' => $duplicateCount,
                'message' => $message
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            $errorMsg = 'Failed to transfer temperature data: ' . $e->getMessage();
            Log::error($errorMsg);
            return [
                'success' => false,
                'count' => 0,
                'skipped' => 0,
                'duplicates' => 0,
                'message' => $errorMsg
            ];
        }
    }

    /**
     * ✅ NEW: Get duplicate check statistics
     */
    public function getDuplicateStats(Request $request)
    {
        $machineId = $request->input('machine_id');
        $days = $request->input('days', 7);

        $stats = $this->anomalyService->getDuplicateCheckStats($machineId, $days);

        return response()->json($stats);
    }

    /**
     * ✅ NEW: Cleanup duplicate anomalies
     */
    public function cleanupDuplicates(Request $request)
    {
        $dryRun = $request->input('dry_run', true);

        try {
            $result = $this->anomalyService->cleanupDuplicateAnomalies($dryRun);

            return response()->json([
                'success' => true,
                'message' => $dryRun
                    ? 'Duplicate analysis completed (dry run)'
                    : 'Duplicate cleanup completed',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to cleanup duplicates: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to cleanup duplicates: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ IMPROVED: Method untuk mendapatkan recorded_at dengan fallback yang jelas
     */
    private function getSimpleRecordedAt($temperature)
    {
        // PRIORITAS 1: Gunakan timestamp jika ada dan valid
        if (!empty($temperature->timestamp)) {
            try {
                return Carbon::parse($temperature->timestamp);
            } catch (\Exception $e) {
                // Continue ke fallback berikutnya
            }
        }

        // PRIORITAS 2: Gabungkan reading_date + reading_time jika ada
        if (!empty($temperature->reading_date)) {
            try {
                $time = !empty($temperature->reading_time) ? $temperature->reading_time : '00:00:00';
                return Carbon::parse($temperature->reading_date . ' ' . $time);
            } catch (\Exception $e) {
                // Continue ke fallback berikutnya
            }
        }

        // PRIORITAS 3: Gunakan created_at
        return $temperature->created_at;
    }

    public function testTransfer(Request $request)
    {
        try {
            $machine = Machine::first();
            if (!$machine) {
                return response()->json(['error' => 'No machines found'], 404);
            }

            // Data sample dari temperature
            $sampleData = Temperature::where('temperature_value', '>', 5)
                ->where('machine_id', $machine->id)
                ->orderBy('created_at', 'desc')
                ->limit(3)
                ->get(['id', 'machine_id', 'temperature_value', 'timestamp', 'reading_date', 'reading_time', 'created_at']);

            // Jalankan transfer dengan duplicate check
            $transferResult = $this->transferTemperatureDataWithDuplicateCheck($machine->id, 1);

            // Data yang berhasil ditransfer
            $transferredData = TemperatureReading::where('machine_id', $machine->id)
                ->where('reading_type', 'transfer')
                ->orderBy('created_at', 'desc')
                ->limit(3)
                ->get(['id', 'machine_id', 'temperature', 'recorded_at', 'reading_type']);

            // Get duplicate statistics
            $duplicateStats = $this->anomalyService->getDuplicateCheckStats($machine->id, 1);

            return response()->json([
                'machine' => $machine->name,
                'sample_temperature_data' => $sampleData,
                'transfer_result' => $transferResult,
                'transferred_readings' => $transferredData,
                'duplicate_stats' => $duplicateStats
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function emergencyTransfer(Request $request)
    {
        try {
            $temperatures = Temperature::where('temperature_value', '>', 5)->get();
            $transferredCount = 0;

            foreach ($temperatures as $temperature) {
                try {
                    $recordedAt = $this->getSimpleRecordedAt($temperature);

                    TemperatureReading::create([
                        'machine_id' => $temperature->machine_id,
                        'recorded_at' => $recordedAt,
                        'temperature' => (float) $temperature->temperature_value,
                        'reading_type' => 'emergency_transfer',
                        'source_file' => 'temperature_table',
                        'is_anomaly' => false
                    ]);

                    $transferredCount++;
                } catch (\Exception $e) {
                    // Skip error
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Emergency transfer completed. Transferred {$transferredCount} records.",
                'count' => $transferredCount
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Emergency transfer failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Manual anomaly detection untuk reading tertentu
     */
    public function manualDetection(Request $request)
    {
        $request->validate([
            'temperature_reading_id' => 'required|exists:temperature_readings,id'
        ]);

        try {
            $reading = TemperatureReading::with('machine')->findOrFail($request->temperature_reading_id);
            $anomalies = $this->anomalyService->checkSingleReading($reading);

            return response()->json([
                'success' => true,
                'message' => count($anomalies) . ' anomalies detected',
                'reading' => [
                    'id' => $reading->id,
                    'temperature' => $reading->temperature,
                    'recorded_at' => $reading->recorded_at,
                    'machine' => $reading->machine->name,
                    'reading_type' => $reading->reading_type
                ],
                'anomalies' => $anomalies
            ]);
        } catch (\Exception $e) {
            Log::error('Manual detection failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Manual detection failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getChartData(Request $request)
    {
        $days = $request->input('days', 30);

        try {
            $trendData = $this->anomalyService->getTrendingAnomalies($days);
            return response()->json($trendData);
        } catch (\Exception $e) {
            Log::error('Failed to get chart data: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to get chart data'], 500);
        }
    }

    public function apiAnomalyStats()
    {
        try {
            $stats = $this->anomalyService->getAnomalyStatistics();

            // Add recent anomalies
            $recentAnomalies = Anomaly::with(['machine.branch', 'temperatureReading'])
                ->whereIn('status', ['new', 'acknowledged', 'investigating'])
                ->orderBy('detected_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($anomaly) {
                    $temperature = null;
                    if ($anomaly->temperature_reading_id && $anomaly->temperatureReading) {
                        $temperature = $anomaly->temperatureReading->temperature;
                    }

                    return [
                        'id' => $anomaly->id,
                        'machine' => $anomaly->machine->name,
                        'branch' => $anomaly->machine->branch->name,
                        'type' => $anomaly->type_name,
                        'severity' => $anomaly->severity,
                        'detected_at' => $anomaly->detected_at->diffForHumans(),
                        'temperature' => $temperature,
                        'reading_type' => $anomaly->temperatureReading->reading_type ?? 'unknown'
                    ];
                });

            $stats['recent_anomalies'] = $recentAnomalies;

            return response()->json($stats);
        } catch (\Exception $e) {
            Log::error('Failed to get anomaly stats: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to get statistics'], 500);
        }
    }

    /**
     * Get temperature readings for a specific machine (for AJAX dropdown)
     */
    public function getMachineReadings($machineId)
    {
        try {
            $readings = TemperatureReading::where('machine_id', $machineId)
                ->orderBy('recorded_at', 'desc')
                ->limit(50)
                ->get()
                ->map(function ($reading) {
                    return [
                        'id' => $reading->id,
                        'temperature' => $reading->temperature,
                        'recorded_at' => $reading->recorded_at->format('Y-m-d H:i:s'),
                        'reading_type' => $reading->reading_type,
                        'formatted_display' => $reading->formatted_temperature . ' - ' . $reading->formatted_recorded_at . ' (' . $reading->reading_type . ')'
                    ];
                });

            return response()->json($readings);
        } catch (\Exception $e) {
            Log::error('Failed to get machine readings: ' . $e->getMessage());
            return response()->json([], 500);
        }
    }
}
