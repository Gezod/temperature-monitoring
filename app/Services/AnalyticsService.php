<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Machine;
use App\Models\TemperatureReading;
use App\Models\MonthlySummary;
use App\Models\MaintenanceRecommendation;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsService
{
    public function getBranchPerformanceSummary()
    {
        return Branch::with(['machines', 'temperatureReadings'])
            ->where('is_active', true)
            ->get()
            ->map(function ($branch) {
                $readings = $branch->temperatureReadings()
                    ->where('recorded_at', '>=', now()->subMonth())
                    ->get();

                return [
                    'branch' => $branch,
                    'machine_count' => $branch->machines->where('is_active', true)->count(),
                    'avg_temperature' => $readings->avg('temperature'),
                    'min_temperature' => $readings->min('temperature'),
                    'max_temperature' => $readings->max('temperature'),
                    'total_readings' => $readings->count(),
                    'anomaly_count' => $readings->where('is_anomaly', true)->count(),
                    'anomaly_rate' => $readings->count() > 0 ?
                        ($readings->where('is_anomaly', true)->count() / $readings->count()) * 100 : 0,
                    'performance_score' => $this->calculatePerformanceScore($branch, $readings)
                ];
            });
    }

    public function getTemperatureTrends($days = 30)
    {
        $fromDate = now()->subDays($days);

        $dailyAverages = TemperatureReading::select(
                DB::raw('DATE(recorded_at) as date'),
                DB::raw('AVG(temperature) as avg_temperature'),
                DB::raw('MIN(temperature) as min_temperature'),
                DB::raw('MAX(temperature) as max_temperature'),
                DB::raw('COUNT(*) as reading_count')
            )
            ->where('recorded_at', '>=', $fromDate)
            ->groupBy(DB::raw('DATE(recorded_at)'))
            ->orderBy('date')
            ->get();

        return $dailyAverages;
    }

    public function getAdvancedAnalytics($filters = [])
    {
        $query = TemperatureReading::with(['machine.branch']);

        // Apply filters
        if (isset($filters['branch_id']) && $filters['branch_id']) {
            $query->whereHas('machine', function($q) use ($filters) {
                $q->where('branch_id', $filters['branch_id']);
            });
        }

        if (isset($filters['machine_id']) && $filters['machine_id']) {
            $query->where('machine_id', $filters['machine_id']);
        }

        if (isset($filters['date_from']) && $filters['date_from']) {
            $query->where('recorded_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to']) && $filters['date_to']) {
            $query->where('recorded_at', '<=', $filters['date_to'] . ' 23:59:59');
        }

        $readings = $query->orderBy('recorded_at')->get();

        // Generate different chart data based on chart type
        $chartType = $filters['chart_type'] ?? 'line';

        switch ($chartType) {
            case 'hourly':
                return $this->getHourlyAnalytics($readings);
            case 'daily':
                return $this->getDailyAnalytics($readings);
            case 'monthly':
                return $this->getMonthlyAnalytics($readings);
            default:
                return $this->getLineChartAnalytics($readings);
        }
    }

    public function getSeasonalAnalysis($filters = [])
    {
        $monthlyData = MonthlySummary::select(
                'month',
                DB::raw('AVG(temp_avg) as avg_temperature'),
                DB::raw('AVG(temp_min) as min_temperature'),
                DB::raw('AVG(temp_max) as max_temperature'),
                DB::raw('COUNT(*) as machine_count')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return $monthlyData->map(function ($data) {
            return [
                'month' => $this->getMonthName($data->month),
                'month_number' => $data->month,
                'avg_temperature' => round($data->avg_temperature, 2),
                'min_temperature' => round($data->min_temperature, 2),
                'max_temperature' => round($data->max_temperature, 2),
                'machine_count' => $data->machine_count
            ];
        });
    }

    public function getBranchComparison($filters = [])
    {
        $branchData = Branch::with(['machines', 'temperatureReadings' => function($query) use ($filters) {
            if (isset($filters['date_from'])) {
                $query->where('recorded_at', '>=', $filters['date_from']);
            }
            if (isset($filters['date_to'])) {
                $query->where('recorded_at', '<=', $filters['date_to'] . ' 23:59:59');
            }
        }])
        ->where('is_active', true)
        ->get();

        return $branchData->map(function ($branch) {
            $readings = $branch->temperatureReadings;

            return [
                'branch_name' => $branch->name,
                'machine_count' => $branch->machines->where('is_active', true)->count(),
                'total_readings' => $readings->count(),
                'avg_temperature' => $readings->avg('temperature'),
                'min_temperature' => $readings->min('temperature'),
                'max_temperature' => $readings->max('temperature'),
                'anomaly_count' => $readings->where('is_anomaly', true)->count(),
                'anomaly_rate' => $readings->count() > 0 ?
                    ($readings->where('is_anomaly', true)->count() / $readings->count()) * 100 : 0,
                'performance_score' => $this->calculatePerformanceScore($branch, $readings)
            ];
        });
    }

    public function getBranchComparisonData()
    {
        return $this->getBranchComparison();
    }

    public function getBranchPerformanceRankings()
    {
        $branchData = $this->getBranchPerformanceSummary();

        return $branchData->sortByDesc('performance_score')->values();
    }

    public function getPredictiveMaintenanceInsights()
    {
        $machines = Machine::with(['temperatureReadings' => function($query) {
            $query->where('recorded_at', '>=', now()->subDays(30));
        }, 'maintenanceRecommendations' => function($query) {
            $query->where('status', 'pending');
        }])
        ->where('is_active', true)
        ->get();

        return $machines->map(function ($machine) {
            $readings = $machine->temperatureReadings;
            $trend = $this->calculateTemperatureTrend($readings);

            $riskScore = $this->calculateMaintenanceRisk($machine, $readings, $trend);

            return [
                'machine' => $machine,
                'risk_score' => $riskScore,
                'trend_direction' => $trend['direction'],
                'trend_rate' => $trend['rate'],
                'avg_temperature' => $readings->avg('temperature'),
                'anomaly_count' => $readings->where('is_anomaly', true)->count(),
                'days_since_maintenance' => $this->getDaysSinceLastMaintenance($machine),
                'predicted_maintenance_date' => $this->predictMaintenanceDate($machine, $riskScore),
                'recommendations' => $this->generateMaintenanceRecommendations($machine, $riskScore, $trend)
            ];
        })->sortByDesc('risk_score')->values();
    }

    private function getHourlyAnalytics($readings)
    {
        return $readings->groupBy(function($reading) {
            return $reading->recorded_at->format('Y-m-d H:00:00');
        })->map(function($hourlyReadings, $hour) {
            return [
                'time' => $hour,
                'avg_temperature' => $hourlyReadings->avg('temperature'),
                'min_temperature' => $hourlyReadings->min('temperature'),
                'max_temperature' => $hourlyReadings->max('temperature'),
                'reading_count' => $hourlyReadings->count()
            ];
        })->values();
    }

    private function getDailyAnalytics($readings)
    {
        return $readings->groupBy(function($reading) {
            return $reading->recorded_at->format('Y-m-d');
        })->map(function($dailyReadings, $date) {
            return [
                'date' => $date,
                'avg_temperature' => $dailyReadings->avg('temperature'),
                'min_temperature' => $dailyReadings->min('temperature'),
                'max_temperature' => $dailyReadings->max('temperature'),
                'reading_count' => $dailyReadings->count(),
                'anomaly_count' => $dailyReadings->where('is_anomaly', true)->count()
            ];
        })->values();
    }

    private function getMonthlyAnalytics($readings)
    {
        return $readings->groupBy(function($reading) {
            return $reading->recorded_at->format('Y-m');
        })->map(function($monthlyReadings, $month) {
            return [
                'month' => $month,
                'avg_temperature' => $monthlyReadings->avg('temperature'),
                'min_temperature' => $monthlyReadings->min('temperature'),
                'max_temperature' => $monthlyReadings->max('temperature'),
                'reading_count' => $monthlyReadings->count(),
                'anomaly_count' => $monthlyReadings->where('is_anomaly', true)->count()
            ];
        })->values();
    }

    private function getLineChartAnalytics($readings)
    {
        return $readings->map(function($reading) {
            return [
                'time' => $reading->recorded_at->format('Y-m-d H:i:s'),
                'temperature' => $reading->temperature,
                'machine' => $reading->machine->name,
                'branch' => $reading->machine->branch->name,
                'is_anomaly' => $reading->is_anomaly
            ];
        });
    }

    private function calculatePerformanceScore($branch, $readings)
    {
        if ($readings->isEmpty()) return 0;

        $score = 100;

        // Reduce score based on anomaly rate
        $anomalyRate = ($readings->where('is_anomaly', true)->count() / $readings->count()) * 100;
        $score -= $anomalyRate * 0.5;

        // Reduce score based on temperature variance
        $temperatures = $readings->pluck('temperature');
        $variance = $this->calculateVariance($temperatures);
        $score -= min($variance * 2, 20); // Max 20 point reduction for high variance

        // Reduce score based on pending maintenance
        $pendingMaintenance = MaintenanceRecommendation::whereHas('machine', function($query) use ($branch) {
            $query->where('branch_id', $branch->id);
        })->where('status', 'pending')->count();

        $score -= $pendingMaintenance * 5; // 5 points per pending maintenance

        return max(0, round($score, 2));
    }

    private function calculateVariance($values)
    {
        if ($values->isEmpty()) return 0;

        $mean = $values->avg();
        $variance = $values->map(function($value) use ($mean) {
            return pow($value - $mean, 2);
        })->avg();

        return $variance;
    }

    private function calculateTemperatureTrend($readings)
    {
        if ($readings->count() < 2) {
            return ['direction' => 'stable', 'rate' => 0];
        }

        $readings = $readings->sortBy('recorded_at');
        $first = $readings->first();
        $last = $readings->last();

        $tempChange = $last->temperature - $first->temperature;
        $timeChange = $last->recorded_at->diffInHours($first->recorded_at);

        $rate = $timeChange > 0 ? $tempChange / $timeChange : 0;

        if (abs($rate) < 0.01) {
            $direction = 'stable';
        } elseif ($rate > 0) {
            $direction = 'increasing';
        } else {
            $direction = 'decreasing';
        }

        return [
            'direction' => $direction,
            'rate' => round($rate, 4)
        ];
    }

    private function calculateMaintenanceRisk($machine, $readings, $trend)
    {
        $risk = 0;

        // Age factor
        if ($machine->installation_date) {
            $ageYears = Carbon::parse($machine->installation_date)->diffInYears(now());
            $risk += min($ageYears * 5, 30); // Max 30 points for age
        }

        // Anomaly factor
        if ($readings->count() > 0) {
            $anomalyRate = ($readings->where('is_anomaly', true)->count() / $readings->count()) * 100;
            $risk += $anomalyRate * 0.8;
        }

        // Trend factor
        if ($trend['direction'] === 'increasing' && $trend['rate'] > 0.1) {
            $risk += min($trend['rate'] * 100, 25);
        }

        // Days since last maintenance
        $daysSinceMaintenance = $this->getDaysSinceLastMaintenance($machine);
        if ($daysSinceMaintenance > 365) {
            $risk += min(($daysSinceMaintenance - 365) * 0.1, 20);
        }

        // Temperature variance
        if ($readings->count() > 0) {
            $variance = $this->calculateVariance($readings->pluck('temperature'));
            $risk += min($variance * 2, 15);
        }

        return min(round($risk, 2), 100);
    }

    private function getDaysSinceLastMaintenance($machine)
    {
        $lastMaintenance = MaintenanceRecommendation::where('machine_id', $machine->id)
            ->where('status', 'completed')
            ->latest('completed_date')
            ->first();

        if (!$lastMaintenance || !$lastMaintenance->completed_date) {
            return $machine->installation_date ?
                Carbon::parse($machine->installation_date)->diffInDays(now()) : 9999;
        }

        return Carbon::parse($lastMaintenance->completed_date)->diffInDays(now());
    }

    private function predictMaintenanceDate($machine, $riskScore)
    {
        $baseDays = 365; // Base maintenance interval

        // Adjust based on risk score
        $riskMultiplier = 1 - ($riskScore / 200); // Higher risk = sooner maintenance
        $adjustedDays = max(30, $baseDays * $riskMultiplier); // Minimum 30 days

        $lastMaintenance = MaintenanceRecommendation::where('machine_id', $machine->id)
            ->where('status', 'completed')
            ->latest('completed_date')
            ->first();

        $baseDate = $lastMaintenance && $lastMaintenance->completed_date ?
            Carbon::parse($lastMaintenance->completed_date) :
            ($machine->installation_date ? Carbon::parse($machine->installation_date) : now());

        return $baseDate->addDays($adjustedDays);
    }

    private function generateMaintenanceRecommendations($machine, $riskScore, $trend)
    {
        $recommendations = [];

        if ($riskScore > 70) {
            $recommendations[] = [
                'type' => 'immediate',
                'priority' => 'high',
                'description' => 'Schedule immediate inspection due to high risk score'
            ];
        }

        if ($trend['direction'] === 'increasing' && $trend['rate'] > 0.2) {
            $recommendations[] = [
                'type' => 'cooling_system',
                'priority' => 'medium',
                'description' => 'Check cooling system performance due to increasing temperature trend'
            ];
        }

        $daysSince = $this->getDaysSinceLastMaintenance($machine);
        if ($daysSince > 365) {
            $recommendations[] = [
                'type' => 'routine',
                'priority' => 'medium',
                'description' => "Overdue for routine maintenance ({$daysSince} days since last service)"
            ];
        }

        return $recommendations;
    }

    private function getMonthName($monthNumber)
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        return $months[$monthNumber] ?? 'Unknown';
    }
}
