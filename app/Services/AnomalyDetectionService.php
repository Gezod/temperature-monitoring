<?php

namespace App\Services;

use App\Models\TemperatureReading;
use App\Models\Machine;
use App\Models\Anomaly;
use App\Models\SystemAlert;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AnomalyDetectionService
{
    private $config;

    public function __construct()
    {
        $this->config = [
            'temp_normal_min' => config('app.temp_normal_min', -20),
            'temp_normal_max' => config('app.temp_normal_max', 5),
            'temp_warning_threshold' => config('app.temp_warning_threshold', 3),
            'rapid_change_threshold' => 5, // °C change in 1 hour
            'pattern_deviation_threshold' => 2, // Standard deviations
        ];
    }

    public function checkSingleReading(TemperatureReading $reading)
    {
        $machine = $reading->machine;
        $anomalies = [];

        // Check temperature bounds
        if ($reading->temperature < $machine->temp_critical_min) {
            $anomalies[] = $this->createAnomaly($reading, 'temperature_low', 'critical',
                "Temperature critically low: {$reading->temperature}°C (Critical minimum: {$machine->temp_critical_min}°C)",
                $this->getLowTempCauses(),
                $this->getLowTempRecommendations()
            );
        } elseif ($reading->temperature > $machine->temp_critical_max) {
            $anomalies[] = $this->createAnomaly($reading, 'temperature_high', 'critical',
                "Temperature critically high: {$reading->temperature}°C (Critical maximum: {$machine->temp_critical_max}°C)",
                $this->getHighTempCauses(),
                $this->getHighTempRecommendations()
            );
        } elseif ($reading->temperature < $machine->temp_min_normal) {
            $anomalies[] = $this->createAnomaly($reading, 'temperature_low', 'medium',
                "Temperature below normal: {$reading->temperature}°C (Normal minimum: {$machine->temp_min_normal}°C)",
                $this->getLowTempCauses(),
                $this->getLowTempRecommendations()
            );
        } elseif ($reading->temperature > $machine->temp_max_normal) {
            $anomalies[] = $this->createAnomaly($reading, 'temperature_high', 'medium',
                "Temperature above normal: {$reading->temperature}°C (Normal maximum: {$machine->temp_max_normal}°C)",
                $this->getHighTempCauses(),
                $this->getHighTempRecommendations()
            );
        }

        // Check rapid temperature changes
        $recentReading = TemperatureReading::where('machine_id', $reading->machine_id)
            ->where('recorded_at', '>=', $reading->recorded_at->subHour())
            ->where('recorded_at', '<', $reading->recorded_at)
            ->orderBy('recorded_at', 'desc')
            ->first();

        if ($recentReading) {
            $tempChange = abs($reading->temperature - $recentReading->temperature);
            if ($tempChange >= $this->config['rapid_change_threshold']) {
                $anomalies[] = $this->createAnomaly($reading, 'rapid_change', 'high',
                    "Rapid temperature change: {$tempChange}°C in " .
                    $reading->recorded_at->diffInMinutes($recentReading->recorded_at) . " minutes",
                    $this->getRapidChangeCauses(),
                    $this->getRapidChangeRecommendations()
                );
            }
        }

        // Check pattern deviation (requires more historical data)
        $patternAnomaly = $this->checkPatternDeviation($reading);
        if ($patternAnomaly) {
            $anomalies[] = $patternAnomaly;
        }

        // Mark reading as anomaly if any detected
        if (!empty($anomalies)) {
            $reading->update(['is_anomaly' => true]);

            // Create system alerts for critical anomalies
            foreach ($anomalies as $anomaly) {
                if ($anomaly->severity === 'critical') {
                    $this->createSystemAlert($anomaly);
                }
            }
        }

        return $anomalies;
    }

    public function checkMachineAnomalies(Machine $machine, Carbon $fromDate = null)
    {
        if (!$fromDate) {
            $fromDate = Carbon::now()->subDays(7);
        }

        $readings = $machine->temperatureReadings()
            ->where('recorded_at', '>=', $fromDate)
            ->orderBy('recorded_at')
            ->get();

        $totalAnomalies = 0;

        foreach ($readings as $reading) {
            $anomalies = $this->checkSingleReading($reading);
            $totalAnomalies += count($anomalies);
        }

        Log::info("Checked {$readings->count()} readings for machine {$machine->name}, found {$totalAnomalies} anomalies");

        return $totalAnomalies;
    }

    public function checkAllMachines()
    {
        $machines = Machine::where('is_active', true)->get();
        $totalAnomalies = 0;

        foreach ($machines as $machine) {
            $totalAnomalies += $this->checkMachineAnomalies($machine);
        }

        return $totalAnomalies;
    }

    private function checkPatternDeviation(TemperatureReading $reading)
    {
        // Get historical data for the same time pattern (same hour of day, same day of week)
        $historicalReadings = TemperatureReading::where('machine_id', $reading->machine_id)
            ->whereRaw('HOUR(recorded_at) = ?', [$reading->recorded_at->hour])
            ->whereRaw('DAYOFWEEK(recorded_at) = ?', [$reading->recorded_at->dayOfWeek + 1])
            ->where('recorded_at', '<', $reading->recorded_at->subWeek())
            ->where('recorded_at', '>=', $reading->recorded_at->subMonths(3))
            ->pluck('temperature');

        if ($historicalReadings->count() < 10) {
            return null; // Not enough historical data
        }

        $mean = $historicalReadings->avg();
        $variance = $historicalReadings->map(function($temp) use ($mean) {
            return pow($temp - $mean, 2);
        })->avg();

        $stdDev = sqrt($variance);
        $zScore = abs($reading->temperature - $mean) / $stdDev;

        if ($zScore > $this->config['pattern_deviation_threshold']) {
            return $this->createAnomaly($reading, 'pattern_deviation', 'medium',
                "Temperature deviates from historical pattern: {$reading->temperature}°C (Expected: {$mean}±{$stdDev}°C, Z-score: {$zScore})",
                $this->getPatternDeviationCauses(),
                $this->getPatternDeviationRecommendations()
            );
        }

        return null;
    }

    private function createAnomaly(TemperatureReading $reading, $type, $severity, $description, $causes, $recommendations)
    {
        return Anomaly::create([
            'machine_id' => $reading->machine_id,
            'temperature_reading_id' => $reading->id,
            'type' => $type,
            'severity' => $severity,
            'description' => $description,
            'possible_causes' => $causes,
            'recommendations' => $recommendations,
            'status' => 'new',
            'detected_at' => now()
        ]);
    }

    private function createSystemAlert(Anomaly $anomaly)
    {
        SystemAlert::create([
            'type' => 'anomaly',
            'level' => $anomaly->severity === 'critical' ? 'critical' : 'warning',
            'title' => "Critical Temperature Anomaly Detected",
            'message' => "Machine: {$anomaly->machine->name} ({$anomaly->machine->branch->name})\n{$anomaly->description}",
            'data' => [
                'anomaly_id' => $anomaly->id,
                'machine_id' => $anomaly->machine_id,
                'temperature' => $anomaly->temperatureReading->temperature,
                'severity' => $anomaly->severity
            ]
        ]);
    }

    private function getLowTempCauses()
    {
        return "Possible causes: Excessive cooling, refrigerant leak, faulty temperature sensor, environmental factors, power issues with heating elements.";
    }

    private function getLowTempRecommendations()
    {
        return "Recommendations: Check refrigerant levels, inspect insulation, verify sensor calibration, check for leaks, review cooling settings.";
    }

    private function getHighTempCauses()
    {
        return "Possible causes: Insufficient cooling, blocked air vents, refrigerant issues, high ambient temperature, equipment malfunction.";
    }

    private function getHighTempRecommendations()
    {
        return "Recommendations: Clean air filters, check cooling system, inspect for blockages, verify refrigerant levels, reduce heat load.";
    }

    private function getRapidChangeCauses()
    {
        return "Possible causes: Door left open, equipment malfunction, power fluctuations, sensor error, external temperature changes.";
    }

    private function getRapidChangeRecommendations()
    {
        return "Recommendations: Check door seals, inspect sensors, verify power supply stability, review recent maintenance activities.";
    }

    private function getPatternDeviationCauses()
    {
        return "Possible causes: Gradual equipment degradation, seasonal changes, operational pattern changes, maintenance needed.";
    }

    private function getPatternDeviationRecommendations()
    {
        return "Recommendations: Schedule preventive maintenance, review operational procedures, consider seasonal adjustments, monitor trends closely.";
    }

    public function getAnomalyStatistics()
    {
        return [
            'total_anomalies' => Anomaly::count(),
            'new_anomalies' => Anomaly::where('status', 'new')->count(),
            'critical_anomalies' => Anomaly::where('severity', 'critical')->count(),
            'anomalies_today' => Anomaly::whereDate('detected_at', today())->count(),
            'anomalies_this_week' => Anomaly::whereBetween('detected_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'anomalies_by_type' => Anomaly::select('type')
                ->selectRaw('count(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type'),
            'anomalies_by_severity' => Anomaly::select('severity')
                ->selectRaw('count(*) as count')
                ->groupBy('severity')
                ->pluck('count', 'severity'),
        ];
    }
}
