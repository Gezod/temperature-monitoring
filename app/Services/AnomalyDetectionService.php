<?php

namespace App\Services;

use App\Models\Temperature;
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
            'temp_normal_range_deviation' => 2, // deviasi dari range normal
            'rapid_change_threshold' => 5, // perubahan °C dalam 1 jam
            'pattern_deviation_threshold' => 2, // standar deviasi
            'consecutive_readings_threshold' => 3, // jumlah pembacaan berturut-turut di luar normal
        ];
    }

    /**
     * Check anomali untuk single reading
     */
    public function checkSingleReading($temperature)
    {
        $machine = $temperature->machine;
        $anomalies = [];

        if (!$machine) {
            Log::warning("No machine found for temperature reading ID: {$temperature->id}");
            return $anomalies;
        }

        // 1. Check temperature bounds (Critical)
        if ($temperature->temperature_value < $machine->temp_critical_min) {
            $anomalies[] = $this->createAnomaly($temperature, 'temperature_low', 'critical',
                "Temperature critically low: {$temperature->temperature_value}°C (Critical minimum: {$machine->temp_critical_min}°C)",
                $this->getLowTempCauses(),
                $this->getLowTempRecommendations()
            );
        } elseif ($temperature->temperature_value > $machine->temp_critical_max) {
            $anomalies[] = $this->createAnomaly($temperature, 'temperature_high', 'critical',
                "Temperature critically high: {$temperature->temperature_value}°C (Critical maximum: {$machine->temp_critical_max}°C)",
                $this->getHighTempCauses(),
                $this->getHighTempRecommendations()
            );
        }

        // 2. Check normal range (High/Medium)
        if ($temperature->temperature_value < $machine->temp_min_normal) {
            $severity = abs($temperature->temperature_value - $machine->temp_min_normal) > 5 ? 'high' : 'medium';
            $anomalies[] = $this->createAnomaly($temperature, 'temperature_low', $severity,
                "Temperature below normal: {$temperature->temperature_value}°C (Normal minimum: {$machine->temp_min_normal}°C)",
                $this->getLowTempCauses(),
                $this->getLowTempRecommendations()
            );
        } elseif ($temperature->temperature_value > $machine->temp_max_normal) {
            $severity = abs($temperature->temperature_value - $machine->temp_max_normal) > 5 ? 'high' : 'medium';
            $anomalies[] = $this->createAnomaly($temperature, 'temperature_high', $severity,
                "Temperature above normal: {$temperature->temperature_value}°C (Normal maximum: {$machine->temp_max_normal}°C)",
                $this->getHighTempCauses(),
                $this->getHighTempRecommendations()
            );
        }

        // 3. Check rapid temperature changes
        $rapidChangeAnomaly = $this->checkRapidChange($temperature);
        if ($rapidChangeAnomaly) {
            $anomalies[] = $rapidChangeAnomaly;
        }

        // 4. Check pattern deviation
        $patternAnomaly = $this->checkPatternDeviation($temperature);
        if ($patternAnomaly) {
            $anomalies[] = $patternAnomaly;
        }

        // 5. Check consecutive abnormal readings
        $consecutiveAnomaly = $this->checkConsecutiveAbnormalReadings($temperature);
        if ($consecutiveAnomaly) {
            $anomalies[] = $consecutiveAnomaly;
        }

        // Create system alerts for critical anomalies
        foreach ($anomalies as $anomaly) {
            if (in_array($anomaly->severity, ['critical', 'high'])) {
                $this->createSystemAlert($anomaly);
            }
        }

        return $anomalies;
    }

    /**
     * Check anomali untuk mesin dalam rentang waktu tertentu
     */
    public function checkMachineAnomalies(Machine $machine, Carbon $fromDate = null)
    {
        if (!$fromDate) {
            $fromDate = Carbon::now()->subDays(7);
        }

        $temperatures = Temperature::where('machine_id', $machine->id)
            ->where('timestamp', '>=', $fromDate)
            ->orderBy('timestamp')
            ->get();

        $totalAnomalies = 0;

        foreach ($temperatures as $temperature) {
            $anomalies = $this->checkSingleReading($temperature);
            $totalAnomalies += count($anomalies);
        }

        Log::info("Checked {$temperatures->count()} temperature readings for machine {$machine->name}, found {$totalAnomalies} anomalies");

        return $totalAnomalies;
    }

    /**
     * Check semua mesin aktif
     */
    public function checkAllMachines()
    {
        $machines = Machine::where('is_active', true)->get();
        $totalAnomalies = 0;

        foreach ($machines as $machine) {
            $totalAnomalies += $this->checkMachineAnomalies($machine);
        }

        Log::info("Total anomalies found across all machines: {$totalAnomalies}");

        return $totalAnomalies;
    }

    /**
     * Check perubahan suhu yang cepat
     */
    private function checkRapidChange($temperature)
    {
        $recentReading = Temperature::where('machine_id', $temperature->machine_id)
            ->where('timestamp', '<', $temperature->timestamp)
            ->where('timestamp', '>=', $temperature->timestamp->subHours(2))
            ->orderBy('timestamp', 'desc')
            ->first();

        if ($recentReading) {
            $tempChange = abs($temperature->temperature_value - $recentReading->temperature_value);
            $timeChange = $temperature->timestamp->diffInMinutes($recentReading->timestamp);

            if ($timeChange > 0) {
                $changeRate = $tempChange / ($timeChange / 60); // per hour

                if ($changeRate >= $this->config['rapid_change_threshold']) {
                    $severity = $changeRate > 10 ? 'high' : 'medium';

                    return $this->createAnomaly($temperature, 'rapid_change', $severity,
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
     * Check penyimpangan pola suhu
     */
    private function checkPatternDeviation($temperature)
    {
        // Get historical data for the same time pattern
        $historicalTemps = Temperature::where('machine_id', $temperature->machine_id)
            ->whereRaw('HOUR(timestamp) BETWEEN ? AND ?', [
                $temperature->timestamp->hour - 1,
                $temperature->timestamp->hour + 1
            ])
            ->where('timestamp', '<', $temperature->timestamp->subDays(7))
            ->where('timestamp', '>=', $temperature->timestamp->subDays(30))
            ->pluck('temperature_value');

        if ($historicalTemps->count() < 5) {
            return null; // Not enough historical data
        }

        $mean = $historicalTemps->avg();
        $variance = $historicalTemps->map(function($temp) use ($mean) {
            return pow($temp - $mean, 2);
        })->avg();

        $stdDev = sqrt($variance);
        $zScore = $stdDev > 0 ? abs($temperature->temperature_value - $mean) / $stdDev : 0;

        if ($zScore > $this->config['pattern_deviation_threshold']) {
            $severity = $zScore > 3 ? 'high' : 'medium';

            return $this->createAnomaly($temperature, 'pattern_deviation', $severity,
                "Temperature deviates from historical pattern: {$temperature->temperature_value}°C (Expected: " .
                number_format($mean, 1) . "±" . number_format($stdDev, 1) . "°C, Z-score: " . number_format($zScore, 2) . ")",
                $this->getPatternDeviationCauses(),
                $this->getPatternDeviationRecommendations()
            );
        }

        return null;
    }

    /**
     * Check pembacaan abnormal berturut-turut
     */
    private function checkConsecutiveAbnormalReadings($temperature)
    {
        $machine = $temperature->machine;

        $recentReadings = Temperature::where('machine_id', $temperature->machine_id)
            ->where('timestamp', '<=', $temperature->timestamp)
            ->orderBy('timestamp', 'desc')
            ->limit($this->config['consecutive_readings_threshold'])
            ->get();

        if ($recentReadings->count() < $this->config['consecutive_readings_threshold']) {
            return null;
        }

        $abnormalCount = 0;
        foreach ($recentReadings as $reading) {
            if ($reading->temperature_value < $machine->temp_min_normal ||
                $reading->temperature_value > $machine->temp_max_normal) {
                $abnormalCount++;
            } else {
                break; // Stop if we find a normal reading
            }
        }

        if ($abnormalCount >= $this->config['consecutive_readings_threshold']) {
            return $this->createAnomaly($temperature, 'consecutive_abnormal', 'high',
                "Consecutive abnormal readings detected: {$abnormalCount} readings outside normal range",
                "Equipment malfunction, sensor drift, environmental changes, system failure.",
                "Immediate inspection required, check equipment status, verify sensor calibration, review maintenance schedule."
            );
        }

        return null;
    }

    /**
     * Create anomaly record
     */
    private function createAnomaly($temperature, $type, $severity, $description, $causes, $recommendations)
    {
        // Check if similar anomaly already exists (to avoid duplicates)
        $existingAnomaly = Anomaly::where('machine_id', $temperature->machine_id)
            ->where('type', $type)
            ->where('detected_at', '>=', now()->subHours(1))
            ->where('status', '!=', 'resolved')
            ->first();

        if ($existingAnomaly) {
            return $existingAnomaly;
        }

        return Anomaly::create([
            'machine_id' => $temperature->machine_id,
            'temperature_reading_id' => $temperature->id,
            'type' => $type,
            'severity' => $severity,
            'description' => $description,
            'possible_causes' => $causes,
            'recommendations' => $recommendations,
            'status' => 'new',
            'detected_at' => now()
        ]);
    }

    /**
     * Create system alert
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
                    Temperature::find($anomaly->temperature_reading_id)?->temperature_value : null,
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
