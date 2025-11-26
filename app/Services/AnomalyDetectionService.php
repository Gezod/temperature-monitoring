<?php

namespace App\Services;

use App\Models\TemperatureReading;
use App\Models\Machine;
use App\Models\Anomaly;
use App\Models\SystemAlert;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnomalyDetectionService
{
    private $config;

    public function __construct()
    {
        $this->config = [
            'temp_normal_range_deviation' => 2,
            'rapid_change_threshold' => 5,
            'pattern_deviation_threshold' => 2,
            'consecutive_readings_threshold' => 3,
            'min_temperature_threshold' => 5,
        ];
    }

    /**
     * Check anomali untuk semua temperature readings yang belum dianalisa
     */
    public function checkUnanalyzedReadings($days = 7)
    {
        $fromDate = Carbon::now()->subDays($days);

        $readings = TemperatureReading::with('machine')
            ->where('recorded_at', '>=', $fromDate)
            ->whereDoesntHave('anomalies')
            ->orderBy('recorded_at')
            ->get();

        $totalAnomalies = 0;

        foreach ($readings as $reading) {
            $anomalies = $this->checkSingleReading($reading);
            $totalAnomalies += count($anomalies);
        }

        Log::info("Checked {$readings->count()} unanalyzed temperature readings, found {$totalAnomalies} anomalies");

        return $totalAnomalies;
    }
    /**
     * Check anomali untuk single reading - VERSION FIXED
     */
    public function checkSingleReading($temperatureReading)
    {

        if (
            !$temperatureReading instanceof \App\Models\TemperatureReading &&
            !$temperatureReading instanceof \App\Models\Temperature
        ) {
            Log::warning("Invalid parameter type: " . get_class($temperatureReading));
            return [];
        }

        // Load machine relationship jika belum diload
        if (!$temperatureReading->relationLoaded('machine')) {
            $temperatureReading->load('machine');
        }
        // Load machine relationship jika belum diload
        if (!$temperatureReading->relationLoaded('machine')) {
            $temperatureReading->load('machine');
        }

        $machine = $temperatureReading->machine;
        $anomalies = [];

        if (!$machine) {
            Log::warning("No machine found for temperature reading ID: {$temperatureReading->id}");
            return $anomalies;
        }

        $currentTemp = $temperatureReading->temperature;

        // VALIDASI: Hanya proses jika suhu > 5°C
        if ($currentTemp <= $this->config['min_temperature_threshold']) {
            Log::info("Temperature {$currentTemp}°C is below threshold, skipping anomaly check");
            return $anomalies;
        }

        Log::info("Checking anomaly for machine {$machine->name}, temperature: {$currentTemp}°C, normal range: {$machine->temp_min_normal}°C - {$machine->temp_max_normal}°C");

        // ... (method anomaly detection lainnya tetap sama)
        // 1. Check critical bounds
        if ($currentTemp <= $machine->temp_critical_min) {
            $anomalies[] = $this->createAnomaly(
                $temperatureReading,
                'temperature_low',
                'critical',
                "Temperature critically low: {$currentTemp}°C (Critical minimum: {$machine->temp_critical_min}°C)",
                $this->getLowTempCauses(),
                $this->getLowTempRecommendations()
            );
            Log::info("Critical low temperature anomaly detected: {$currentTemp}°C");
        } elseif ($currentTemp >= $machine->temp_critical_max) {
            $anomalies[] = $this->createAnomaly(
                $temperatureReading,
                'temperature_high',
                'critical',
                "Temperature critically high: {$currentTemp}°C (Critical maximum: {$machine->temp_critical_max}°C)",
                $this->getHighTempCauses(),
                $this->getHighTempRecommendations()
            );
            Log::info("Critical high temperature anomaly detected: {$currentTemp}°C");
        }

        // 2. Check normal range bounds
        if ($currentTemp < $machine->temp_min_normal) {
            $severity = ($machine->temp_min_normal - $currentTemp) > 5 ? 'high' : 'medium';
            $anomalies[] = $this->createAnomaly(
                $temperatureReading,
                'temperature_low',
                $severity,
                "Temperature below normal: {$currentTemp}°C (Normal minimum: {$machine->temp_min_normal}°C)",
                $this->getLowTempCauses(),
                $this->getLowTempRecommendations()
            );
            Log::info("Low temperature anomaly detected: {$currentTemp}°C");
        } elseif ($currentTemp > $machine->temp_max_normal) {
            $severity = ($currentTemp - $machine->temp_max_normal) > 5 ? 'high' : 'medium';
            $anomalies[] = $this->createAnomaly(
                $temperatureReading,
                'temperature_high',
                $severity,
                "Temperature above normal: {$currentTemp}°C (Normal maximum: {$machine->temp_max_normal}°C)",
                $this->getHighTempCauses(),
                $this->getHighTempRecommendations()
            );
            Log::info("High temperature anomaly detected: {$currentTemp}°C");
        }

        // 3. Check rapid changes
        if (empty($anomalies)) {
            $rapidChangeAnomaly = $this->checkRapidChange($temperatureReading);
            if ($rapidChangeAnomaly) {
                $anomalies[] = $rapidChangeAnomaly;
            }
        }

        // 4. Check pattern deviation
        $patternAnomaly = $this->checkPatternDeviation($temperatureReading);
        if ($patternAnomaly) {
            $anomalies[] = $patternAnomaly;
        }

        // 5. Check consecutive abnormal readings
        $consecutiveAnomaly = $this->checkConsecutiveAbnormalReadings($temperatureReading);
        if ($consecutiveAnomaly) {
            $anomalies[] = $consecutiveAnomaly;
        }

        // Update temperature reading status jika ada anomaly
        if (!empty($anomalies)) {
            $temperatureReading->update(['is_anomaly' => true]);

            // Create system alerts for critical/high anomalies
            foreach ($anomalies as $anomaly) {
                if (in_array($anomaly->severity, ['critical', 'high'])) {
                    $this->createSystemAlert($anomaly);
                }
            }
        }

        return $anomalies;
    }

    /**
     * Check semua mesin aktif - METHOD YANG DIPERBAIKI
     */
    public function checkAllMachines()
    {
        return $this->checkUnanalyzedReadings(7);
    }

    /**
     * Check mesin tertentu - METHOD YANG DIPERBAIKI
     */
    public function checkMachineAnomalies(Machine $machine, Carbon $fromDate = null)
    {
        if (!$fromDate) {
            $fromDate = Carbon::now()->subDays(7);
        }

        $readings = TemperatureReading::with('machine')
            ->where('machine_id', $machine->id)
            ->where('recorded_at', '>=', $fromDate)
            ->whereDoesntHave('anomalies')
            ->orderBy('recorded_at')
            ->get();

        $totalAnomalies = 0;

        foreach ($readings as $reading) {
            $anomalies = $this->checkSingleReading($reading);
            $totalAnomalies += count($anomalies);
        }

        Log::info("Machine {$machine->name}: Checked {$readings->count()} readings, found {$totalAnomalies} anomalies");

        return $totalAnomalies;
    }

    /**
     * Check perubahan suhu yang cepat - VERSION FIXED
     */
    private function checkRapidChange(TemperatureReading $temperatureReading)
    {
        $recentReading = TemperatureReading::where('machine_id', $temperatureReading->machine_id)
            ->where('recorded_at', '<', $temperatureReading->recorded_at)
            ->where('recorded_at', '>=', $temperatureReading->recorded_at->subHours(2))
            ->orderBy('recorded_at', 'desc')
            ->first();

        if ($recentReading) {
            $tempChange = abs($temperatureReading->temperature - $recentReading->temperature); // GUNAKAN 'temperature'
            $timeChange = $temperatureReading->recorded_at->diffInMinutes($recentReading->recorded_at);

            if ($timeChange > 0) {
                $changeRate = $tempChange / ($timeChange / 60); // per hour

                if ($changeRate >= $this->config['rapid_change_threshold']) {
                    $severity = $changeRate > 10 ? 'high' : 'medium';

                    return $this->createAnomaly(
                        $temperatureReading,
                        'rapid_change',
                        $severity,
                        "Rapid temperature change: {$tempChange}°C in {$timeChange} minutes (Rate: " . number_format($changeRate, 1) . "°C/hour)",
                        $this->getRapidChangeCauses(),
                        $this->getRapidChangeRecommendations()
                    );
                }
            }
        }

        return null;
    }

    /**
     * Check penyimpangan pola suhu - VERSION FIXED
     */
    private function checkPatternDeviation(TemperatureReading $temperatureReading)
    {
        // Get historical data for the same time pattern
        $historicalTemps = TemperatureReading::where('machine_id', $temperatureReading->machine_id)
            ->whereRaw('HOUR(recorded_at) BETWEEN ? AND ?', [
                $temperatureReading->recorded_at->hour - 1,
                $temperatureReading->recorded_at->hour + 1
            ])
            ->where('recorded_at', '<', $temperatureReading->recorded_at->subDays(7))
            ->where('recorded_at', '>=', $temperatureReading->recorded_at->subDays(30))
            ->pluck('temperature'); // GUNAKAN 'temperature'

        if ($historicalTemps->count() < 5) {
            return null;
        }

        $mean = $historicalTemps->avg();
        $variance = $historicalTemps->map(function ($temp) use ($mean) {
            return pow($temp - $mean, 2);
        })->avg();

        $stdDev = sqrt($variance);
        $zScore = $stdDev > 0 ? abs($temperatureReading->temperature - $mean) / $stdDev : 0; // GUNAKAN 'temperature'

        if ($zScore > $this->config['pattern_deviation_threshold']) {
            $severity = $zScore > 3 ? 'high' : 'medium';

            return $this->createAnomaly(
                $temperatureReading,
                'pattern_deviation',
                $severity,
                "Temperature deviates from historical pattern: {$temperatureReading->temperature}°C (Expected: " . // GUNAKAN 'temperature'
                    number_format($mean, 1) . "±" . number_format($stdDev, 1) . "°C, Z-score: " . number_format($zScore, 2) . ")",
                $this->getPatternDeviationCauses(),
                $this->getPatternDeviationRecommendations()
            );
        }

        return null;
    }

    /**
     * Check pembacaan abnormal berturut-turut - VERSION FIXED
     */
    private function checkConsecutiveAbnormalReadings(TemperatureReading $temperatureReading)
    {
        $machine = $temperatureReading->machine;

        $recentReadings = TemperatureReading::where('machine_id', $temperatureReading->machine_id)
            ->where('recorded_at', '<=', $temperatureReading->recorded_at)
            ->orderBy('recorded_at', 'desc')
            ->limit($this->config['consecutive_readings_threshold'])
            ->get();

        if ($recentReadings->count() < $this->config['consecutive_readings_threshold']) {
            return null;
        }

        $abnormalCount = 0;
        foreach ($recentReadings as $reading) {
            if (
                $reading->temperature < $machine->temp_min_normal || // GUNAKAN 'temperature'
                $reading->temperature > $machine->temp_max_normal
            ) { // GUNAKAN 'temperature'
                $abnormalCount++;
            } else {
                break;
            }
        }

        if ($abnormalCount >= $this->config['consecutive_readings_threshold']) {
            return $this->createAnomaly(
                $temperatureReading,
                'consecutive_abnormal',
                'high',
                "Consecutive abnormal readings detected: {$abnormalCount} readings outside normal range",
                "Equipment malfunction, sensor drift, environmental changes, system failure.",
                "Immediate inspection required, check equipment status, verify sensor calibration, review maintenance schedule."
            );
        }

        return null;
    }

    /**
     * Create anomaly record - VERSION FIXED
     */
    private function createAnomaly(TemperatureReading $temperatureReading, $type, $severity, $description, $causes, $recommendations)
    {
        // Check if similar anomaly already exists (to avoid duplicates)
        $existingAnomaly = Anomaly::where('machine_id', $temperatureReading->machine_id)
            ->where('temperature_reading_id', $temperatureReading->id)
            ->where('type', $type)
            ->where('detected_at', '>=', now()->subHours(1))
            ->where('status', '!=', 'resolved')
            ->first();

        if ($existingAnomaly) {
            Log::info("Duplicate anomaly prevented for reading ID: {$temperatureReading->id}, type: {$type}");
            return $existingAnomaly;
        }

        try {
            $anomaly = Anomaly::create([
                'machine_id' => $temperatureReading->machine_id,
                'temperature_reading_id' => $temperatureReading->id, // INI TIDAK AKAN KOSONG LAGI
                'type' => $type,
                'severity' => $severity,
                'description' => $description,
                'possible_causes' => $causes,
                'recommendations' => $recommendations,
                'status' => 'new',
                'detected_at' => $temperatureReading->recorded_at // GUNAKAN WAKTU RECORDING
            ]);

            Log::info("Anomaly created: ID {$anomaly->id} for reading ID: {$temperatureReading->id}");

            return $anomaly;
        } catch (\Exception $e) {
            Log::error("Failed to create anomaly: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Create system alert - VERSION FIXED
     */
    private function createSystemAlert(Anomaly $anomaly)
    {
        $levelMapping = [
            'critical' => 'critical',
            'high' => 'error',
            'medium' => 'warning',
            'low' => 'info'
        ];

        SystemAlert::create([
            'type' => 'anomaly',
            'level' => $levelMapping[$anomaly->severity] ?? 'warning',
            'title' => "Temperature Anomaly Detected - {$anomaly->machine->name}",
            'message' => "{$anomaly->description}\n\nMachine: {$anomaly->machine->name}\nBranch: {$anomaly->machine->branch->name}\nTime: {$anomaly->detected_at->format('d/m/Y H:i:s')}",
            'data' => [
                'anomaly_id' => $anomaly->id,
                'machine_id' => $anomaly->machine_id,
                'temperature' => $anomaly->temperature_reading_id ?
                    TemperatureReading::find($anomaly->temperature_reading_id)?->temperature : null, // GUNAKAN 'temperature'
                'severity' => $anomaly->severity
            ]
        ]);
    }

    /**
     * Get anomaly statistics
     */
    public function getAnomalyStatistics()
    {
        $stats = [
            'total_anomalies' => Anomaly::count(),
            'new_anomalies' => Anomaly::where('status', 'new')->count(),
            'critical_anomalies' => Anomaly::where('severity', 'critical')->count(),
            'anomalies_today' => Anomaly::whereDate('detected_at', today())->count(),
            'anomalies_this_week' => Anomaly::whereBetween('detected_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])->count(),
            'anomalies_by_type' => Anomaly::select('type')
                ->selectRaw('count(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type'),
            'anomalies_by_severity' => Anomaly::select('severity')
                ->selectRaw('count(*) as count')
                ->groupBy('severity')
                ->pluck('count', 'severity'),
            'anomalies_by_machine' => Anomaly::with('machine')
                ->select('machine_id')
                ->selectRaw('count(*) as count')
                ->groupBy('machine_id')
                ->get()
                ->map(function ($item) {
                    return [
                        'machine_name' => $item->machine?->name ?? 'Unknown',
                        'count' => $item->count
                    ];
                }),
        ];

        return $stats;
    }

    /**
     * Get trending anomalies
     */
    public function getTrendingAnomalies($days = 30)
    {
        return Anomaly::select(
            DB::raw('DATE(detected_at) as date'),
            DB::raw('COUNT(*) as count'),
            'severity'
        )
            ->where('detected_at', '>=', now()->subDays($days))
            ->groupBy('date', 'severity')
            ->orderBy('date')
            ->get()
            ->groupBy('date')
            ->map(function ($dailyAnomalies, $date) {
                return [
                    'date' => $date,
                    'critical' => $dailyAnomalies->where('severity', 'critical')->sum('count'),
                    'high' => $dailyAnomalies->where('severity', 'high')->sum('count'),
                    'medium' => $dailyAnomalies->where('severity', 'medium')->sum('count'),
                    'low' => $dailyAnomalies->where('severity', 'low')->sum('count'),
                    'total' => $dailyAnomalies->sum('count'),
                ];
            });
    }

    // Helper methods for causes and recommendations
    private function getLowTempCauses()
    {
        return "Possible causes: Excessive cooling system operation, refrigerant leak, faulty temperature sensor, environmental factors, power issues with heating elements, blocked air circulation.";
    }

    private function getLowTempRecommendations()
    {
        return "Recommendations: Check refrigerant levels and system pressure, inspect insulation and door seals, verify sensor calibration and placement, check for air leaks, review cooling system settings, inspect heating elements if applicable.";
    }

    private function getHighTempCauses()
    {
        return "Possible causes: Insufficient cooling capacity, blocked air vents or filters, refrigerant issues, high ambient temperature, equipment malfunction, excessive heat load, compressor problems.";
    }

    private function getHighTempRecommendations()
    {
        return "Recommendations: Clean or replace air filters, check cooling system operation, inspect for blockages in air circulation, verify refrigerant levels, reduce heat load if possible, check compressor operation and electrical connections.";
    }

    private function getRapidChangeCauses()
    {
        return "Possible causes: Door or access panel left open, equipment cycling on/off rapidly, power fluctuations, sensor malfunction, external temperature changes, system instability.";
    }

    private function getRapidChangeRecommendations()
    {
        return "Recommendations: Check door seals and closure mechanisms, inspect sensors and wiring, verify power supply stability, review recent maintenance activities, check system controls and settings.";
    }

    private function getPatternDeviationCauses()
    {
        return "Possible causes: Gradual equipment degradation, seasonal environmental changes, operational pattern changes, sensor drift, maintenance needed, system aging.";
    }

    private function getPatternDeviationRecommendations()
    {
        return "Recommendations: Schedule comprehensive system inspection, review and update operational procedures, consider seasonal adjustments, perform sensor calibration, monitor trends closely, plan preventive maintenance.";
    }
}
