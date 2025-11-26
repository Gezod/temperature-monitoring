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
     * Run anomaly check - VERSION FINAL DENGAN DEBUGGING DETAIL
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
            $transferResult = $this->transferTemperatureDataFixed($request->machine_id, $days);

            $transferredCount = $transferResult['count'];
            $message = $transferResult['message'];

            // JALANKAN DETEKSI ANOMALI
            if ($request->machine_id) {
                $machine = Machine::findOrFail($request->machine_id);
                $anomalyCount = $this->anomalyService->checkMachineAnomalies($machine, now()->subDays($days));
                $message .= " Anomaly check completed for {$machine->name}. Found {$anomalyCount} anomalies.";
            } else {
                $anomalyCount = $this->anomalyService->checkUnanalyzedReadings($days);
                $message .= " Global anomaly check completed. Found {$anomalyCount} anomalies across all machines.";
            }

            Log::info("Anomaly check completed: {$message}");

            return response()->json([
                'success' => true,
                'message' => $message,
                'transferred_count' => $transferredCount,
                'anomaly_count' => $anomalyCount
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


    private function transferTemperatureDataFixed($machineId = null, $days = 7)
    {
        DB::beginTransaction();
        try {
            $fromDate = Carbon::now()->subDays($days);

            Log::info("Starting temperature data transfer from: {$fromDate}");

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

            foreach ($temperatures as $temperature) {
                try {
                    // TENTUKAN recorded_at
                    $recordedAt = $this->getSimpleRecordedAt($temperature);

                    // AMBIL NILAI
                    $tempValue = (float) $temperature->temperature_value;
                    $machineIdValue = $temperature->machine_id;

                    // VALIDASI DASAR
                    if (!$recordedAt || $tempValue <= 5 || !$machineIdValue) {
                        $skippedCount++;
                        continue;
                    }

                    // GUNAKAN READING_TYPE YANG LEBIH PENDEK
                    $readingType = 'transfer'; // Hanya 7 karakter

                    // CEK DUPLIKASI
                    $existing = TemperatureReading::where('machine_id', $machineIdValue)
                        ->where('temperature', $tempValue)
                        ->whereDate('recorded_at', $recordedAt->format('Y-m-d'))
                        ->exists();

                    if ($existing) {
                        $skippedCount++;
                        continue;
                    }

                    // TRANSFER DATA - GUNAKAN READING_TYPE PENDEK
                    TemperatureReading::create([
                        'machine_id' => $machineIdValue,
                        'recorded_at' => $recordedAt,
                        'temperature' => $tempValue,
                        'reading_type' => $readingType, // Hanya 'transfer'
                        'source_file' => 'temp_table', // Juga diperpendek
                        'is_anomaly' => false
                    ]);

                    $transferredCount++;
                    Log::info("Transferred: {$tempValue}°C for machine {$machineIdValue}");

                } catch (\Exception $e) {
                    Log::error("Failed to transfer record ID {$temperature->id}: " . $e->getMessage());
                    $skippedCount++;
                }
            }

            DB::commit();

            $message = "Successfully transferred {$transferredCount} temperature readings to temperature_readings table";
            if ($skippedCount > 0) {
                $message .= ", skipped {$skippedCount} records";
            }

            Log::info($message);

            return [
                'success' => true,
                'count' => $transferredCount,
                'skipped' => $skippedCount,
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
                'message' => $errorMsg
            ];
        }
    }
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

            // Jalankan transfer
            $transferResult = $this->transferTemperatureDataWorking($machine->id, 1);

            // Data yang berhasil ditransfer
            $transferredData = TemperatureReading::where('machine_id', $machine->id)
                ->where('reading_type', 'transferred')
                ->orderBy('created_at', 'desc')
                ->limit(3)
                ->get(['id', 'machine_id', 'temperature', 'recorded_at', 'reading_type']);

            return response()->json([
                'machine' => $machine->name,
                'sample_temperature_data' => $sampleData,
                'transfer_result' => $transferResult,
                'transferred_readings' => $transferredData
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
     * Transfer data dari Temperature ke TemperatureReading untuk suhu > 5°C
     * VERSION FIXED - Dengan debugging yang sangat detail
     */
    private function transferTemperatureData($machineId = null, $days = 7)
    {
        $debugInfo = [
            'query_conditions' => [],
            'records_found' => 0,
            'validation_errors' => [],
            'transfer_details' => []
        ];

        try {
            $fromDate = Carbon::now()->subDays($days);

            // BUAT QUERY DENGAN KOLOM YANG SESUAI
            $query = Temperature::query();

            // Filter berdasarkan timestamp
            $query->where('timestamp', '>=', $fromDate);
            $debugInfo['query_conditions']['timestamp'] = $fromDate->format('Y-m-d H:i:s');

            // Filter suhu > 5°C - GUNAKAN temperature_value
            $query->where('temperature_value', '>', 5);
            $debugInfo['query_conditions']['temperature_value'] = '> 5';

            if ($machineId) {
                $query->where('machine_id', $machineId);
                $debugInfo['query_conditions']['machine_id'] = $machineId;
            }

            $temperatures = $query->get();
            $debugInfo['records_found'] = $temperatures->count();

            Log::info("Found {$temperatures->count()} temperature records to process (>5°C)");

            $transferredCount = 0;
            $skippedCount = 0;
            $invalidData = [];

            foreach ($temperatures as $temperature) {
                $recordDebug = [
                    'id' => $temperature->id,
                    'temperature_value' => $temperature->temperature_value,
                    'timestamp' => $temperature->timestamp,
                    'machine_id' => $temperature->machine_id,
                    'validation_errors' => [],
                    'status' => 'processed'
                ];

                try {
                    // DAPATKAN NILAI DARI KOLOM YANG SESUAI
                    $recordedAt = $this->getDateTimeValue($temperature, 'timestamp');
                    $tempValue = $this->getTemperatureValue($temperature, 'temperature_value');
                    $machineIdValue = $this->getMachineIdValue($temperature, 'machine_id');

                    // DEBUG: Log data yang diproses
                    Log::debug("Processing temperature record:", [
                        'id' => $temperature->id,
                        'temperature_value' => $tempValue,
                        'timestamp_raw' => $temperature->timestamp,
                        'timestamp_parsed' => $recordedAt,
                        'machine_id' => $machineIdValue
                    ]);

                    // VALIDASI DATA
                    $validationErrors = [];

                    if (!$recordedAt) {
                        $errorMsg = "Invalid recorded_at: " . ($temperature->timestamp ?? 'null');
                        $validationErrors[] = $errorMsg;
                        $recordDebug['validation_errors'][] = $errorMsg;
                    }

                    if (!$tempValue || $tempValue <= 5) {
                        $errorMsg = "Invalid temperature value: " . $tempValue;
                        $validationErrors[] = $errorMsg;
                        $recordDebug['validation_errors'][] = $errorMsg;
                    }

                    if (!$machineIdValue) {
                        $errorMsg = "Invalid machine_id: " . ($temperature->machine_id ?? 'null');
                        $validationErrors[] = $errorMsg;
                        $recordDebug['validation_errors'][] = $errorMsg;
                    }

                    // Cek apakah machine_id valid
                    if ($machineIdValue && !Machine::where('id', $machineIdValue)->exists()) {
                        $errorMsg = "Machine not found: " . $machineIdValue;
                        $validationErrors[] = $errorMsg;
                        $recordDebug['validation_errors'][] = $errorMsg;
                    }

                    // Jika ada error validasi, skip dan log
                    if (!empty($validationErrors)) {
                        $invalidData[] = [
                            'id' => $temperature->id,
                            'errors' => $validationErrors,
                            'data' => [
                                'temperature_value' => $tempValue,
                                'timestamp' => $temperature->timestamp,
                                'machine_id' => $machineIdValue
                            ]
                        ];
                        $recordDebug['status'] = 'skipped_invalid';
                        $skippedCount++;
                        $debugInfo['transfer_details'][] = $recordDebug;
                        continue;
                    }

                    // CEK DUPLIKASI - Lebih fleksibel dalam pengecekan
                    $existing = TemperatureReading::where('machine_id', $machineIdValue)
                        ->where('temperature', $tempValue)
                        ->whereBetween('recorded_at', [
                            $recordedAt->copy()->subMinutes(10), // Beri toleransi ±10 menit
                            $recordedAt->copy()->addMinutes(10)
                        ])
                        ->exists();

                    if ($existing) {
                        Log::debug("Skipped duplicate temperature: {$tempValue}°C for machine {$machineIdValue} at {$recordedAt}");
                        $recordDebug['status'] = 'skipped_duplicate';
                        $skippedCount++;
                        $debugInfo['transfer_details'][] = $recordDebug;
                        continue;
                    }

                    // TRANSFER DATA
                    $temperatureReading = TemperatureReading::create([
                        'machine_id' => $machineIdValue,
                        'recorded_at' => $recordedAt,
                        'temperature' => $tempValue,
                        'reading_type' => 'transferred',
                        'source_file' => 'temperature_table',
                        'metadata' => [
                            'transferred_from' => 'temperature_table',
                            'original_id' => $temperature->id,
                            'original_columns' => [
                                'date' => 'timestamp',
                                'temperature' => 'temperature_value',
                                'machine' => 'machine_id'
                            ],
                            'original_data' => [
                                'temperature_value' => $tempValue,
                                'timestamp' => $recordedAt,
                                'machine_id' => $machineIdValue,
                                'reading_date' => $temperature->reading_date ?? null,
                                'reading_time' => $temperature->reading_time ?? null
                            ],
                            'transfer_time' => now()->toISOString()
                        ],
                        'is_anomaly' => false
                    ]);

                    $transferredCount++;
                    $recordDebug['status'] = 'transferred';
                    $recordDebug['new_reading_id'] = $temperatureReading->id;
                    Log::info("Transferred temperature: {$tempValue}°C for machine {$machineIdValue} at {$recordedAt} (ID: {$temperatureReading->id})");
                } catch (\Exception $e) {
                    Log::error("Failed to transfer temperature record ID " . ($temperature->id ?? 'unknown') . ": " . $e->getMessage(), [
                        'data' => $temperature->toArray()
                    ]);
                    $recordDebug['status'] = 'error';
                    $recordDebug['error'] = $e->getMessage();
                    $skippedCount++;
                }

                $debugInfo['transfer_details'][] = $recordDebug;
            }

            // LOG HASIL DETAIL
            $debugInfo['validation_errors'] = $invalidData;
            $debugInfo['summary'] = [
                'transferred' => $transferredCount,
                'skipped' => $skippedCount,
                'invalid_count' => count($invalidData)
            ];

            if (!empty($invalidData)) {
                Log::warning("Invalid temperature records found:", [
                    'total_invalid' => count($invalidData),
                    'samples' => array_slice($invalidData, 0, 3) // Log 3 sample pertama
                ]);
            }

            $message = "Successfully transferred {$transferredCount} temperature readings (>5°C) to temperature_readings table";
            if ($skippedCount > 0) {
                $message .= ", skipped {$skippedCount} records";
                if (!empty($invalidData)) {
                    $message .= " (" . count($invalidData) . " invalid, " . ($skippedCount - count($invalidData)) . " duplicates)";
                }
            }

            Log::info($message);

            return [
                'success' => true,
                'count' => $transferredCount,
                'skipped' => $skippedCount,
                'invalid_data' => $invalidData,
                'message' => $message,
                'debug_info' => $debugInfo
            ];
        } catch (\Exception $e) {
            $errorMsg = 'Failed to transfer temperature data: ' . $e->getMessage();
            Log::error($errorMsg);
            return [
                'success' => false,
                'count' => 0,
                'skipped' => 0,
                'message' => $errorMsg,
                'debug_info' => $debugInfo
            ];
        }
    }

    /**
     * Dapatkan nilai datetime dari kolom timestamp - VERSION IMPROVED
     */
    private function getDateTimeValue($temperature, $dateColumn)
    {
        $value = null; // <-- Definisikan dulu agar selalu ada

        if (!isset($temperature->$dateColumn) || empty($temperature->$dateColumn)) {
            return null;
        }

        try {
            $value = $temperature->$dateColumn;

            // Jika sudah Carbon instance
            if ($value instanceof \Carbon\Carbon) {
                return $value;
            }

            // Jika null atau string kosong
            if ($value === null || $value === '') {
                return null;
            }

            // Coba parse sebagai datetime
            return Carbon::parse($value);
        } catch (\Exception $e) {
            Log::warning("Failed to parse date from column {$dateColumn}: " . $e->getMessage() . " - Value: " . $value . " - Type: " . gettype($value));
            return null;
        }
    }


    /**
     * Dapatkan nilai temperature dari temperature_value - VERSION IMPROVED
     */
    private function getTemperatureValue($temperature, $temperatureColumn)
    {
        if (!isset($temperature->$temperatureColumn)) {
            return null;
        }

        $value = $temperature->$temperatureColumn;

        // Handle null, empty string, atau non-numeric
        if ($value === null || $value === '' || !is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    /**
     * Dapatkan nilai machine_id - VERSION IMPROVED
     */
    private function getMachineIdValue($temperature, $machineColumn)
    {
        if (!isset($temperature->$machineColumn)) {
            return null;
        }

        $value = $temperature->$machineColumn;

        // Handle null, empty string
        if ($value === null || $value === '') {
            return null;
        }

        return $value;
    }

    /**
     * Test anomaly detection untuk debugging - VERSION SUPER DETAILED
     */
    public function testAnomalyDetection(Request $request)
    {
        try {
            $machine = Machine::with('branch')->first();
            if (!$machine) {
                return response()->json(['error' => 'No machines found'], 404);
            }

            // Test query langsung untuk melihat data yang ada
            $fromDate = Carbon::now()->subDays(1);
            $rawData = Temperature::where('temperature_value', '>', 5)
                ->where('timestamp', '>=', $fromDate)
                ->where('machine_id', $machine->id)
                ->orderBy('timestamp', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($temp) {
                    $parsedTimestamp = null;
                    try {
                        $parsedTimestamp = $temp->timestamp ? Carbon::parse($temp->timestamp)->format('Y-m-d H:i:s') : null;
                    } catch (\Exception $e) {
                        $parsedTimestamp = 'parse_error';
                    }

                    return [
                        'id' => $temp->id,
                        'machine_id' => $temp->machine_id,
                        'temperature_value' => $temp->temperature_value,
                        'timestamp_raw' => $temp->timestamp,
                        'timestamp_parsed' => $parsedTimestamp,
                        'timestamp_type' => gettype($temp->timestamp),
                        'reading_date' => $temp->reading_date,
                        'reading_time' => $temp->reading_time,
                        'is_validated' => $temp->is_validated,
                        'validation_status' => $temp->validation_status
                    ];
                });

            // Cek data yang sudah ada di temperature_readings
            $existingReadings = TemperatureReading::where('machine_id', $machine->id)
                ->where('reading_type', 'transferred')
                ->orderBy('recorded_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($reading) {
                    return [
                        'id' => $reading->id,
                        'machine_id' => $reading->machine_id,
                        'temperature' => $reading->temperature,
                        'recorded_at' => $reading->recorded_at,
                        'reading_type' => $reading->reading_type,
                        'metadata' => $reading->metadata
                    ];
                });

            // Transfer data untuk testing
            $transferResult = $this->transferTemperatureData($machine->id, 1);

            // Cari temperature readings yang melebihi batas normal
            $abnormalReadings = TemperatureReading::where('machine_id', $machine->id)
                ->where(function ($query) use ($machine) {
                    $query->where('temperature', '>', $machine->temp_max_normal)
                        ->orWhere('temperature', '<', $machine->temp_min_normal);
                })
                ->whereDoesntHave('anomalies')
                ->get();

            $results = [
                'machine' => $machine->name,
                'branch' => $machine->branch->name,
                'normal_range' => "{$machine->temp_min_normal}°C - {$machine->temp_max_normal}°C",
                'critical_range' => "{$machine->temp_critical_min}°C - {$machine->temp_critical_max}°C",

                // Data dari temperature table
                'raw_temperature_data' => $rawData,
                'existing_transferred_readings' => $existingReadings,

                // Hasil transfer
                'data_transfer' => $transferResult,

                // Hasil deteksi
                'abnormal_readings_count' => $abnormalReadings->count(),
                'abnormal_readings' => $abnormalReadings->map(function ($reading) use ($machine) {
                    $status = $reading->temperature > $machine->temp_max_normal ? 'above_normal' : 'below_normal';
                    $deviation = abs($reading->temperature -
                        ($reading->temperature > $machine->temp_max_normal ? $machine->temp_max_normal : $machine->temp_min_normal));

                    return [
                        'id' => $reading->id,
                        'temperature' => $reading->temperature,
                        'recorded_at' => $reading->recorded_at->format('Y-m-d H:i:s'),
                        'status' => $status,
                        'deviation' => round($deviation, 2),
                        'reading_type' => $reading->reading_type
                    ];
                })
            ];

            // Jalankan anomaly detection pada readings abnormal
            $detectedAnomalies = [];
            foreach ($abnormalReadings as $reading) {
                $anomalies = $this->anomalyService->checkSingleReading($reading);
                if (count($anomalies) > 0) {
                    $detectedAnomalies[] = [
                        'reading_id' => $reading->id,
                        'temperature' => $reading->temperature,
                        'anomalies_count' => count($anomalies),
                        'anomaly_types' => collect($anomalies)->pluck('type')->toArray()
                    ];
                }
            }

            $results['detected_anomalies'] = $detectedAnomalies;

            return response()->json($results);
        } catch (\Exception $e) {
            Log::error('Test anomaly detection failed: ' . $e->getMessage());
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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
