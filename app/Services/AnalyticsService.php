<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Machine;
use App\Models\Temperature;
use App\Models\MonthlySummary;
use App\Models\MaintenanceRecommendation;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AnalyticsService
{
    public function getBranchPerformanceSummary()
    {
        return Branch::with(['machines'])
            ->where('is_active', true)
            ->get()
            ->map(function ($branch) {
                $readings = Temperature::whereHas('machine', function($query) use ($branch) {
                    $query->where('branch_id', $branch->id);
                })
                ->where('timestamp', '>=', now()->subMonth())
                ->get();

                // Handle empty readings
                if ($readings->isEmpty()) {
                    return [
                        'branch' => $branch,
                        'machine_count' => $branch->machines->where('is_active', true)->count(),
                        'avg_temperature' => 0,
                        'min_temperature' => 0,
                        'max_temperature' => 0,
                        'total_readings' => 0,
                        'anomaly_count' => 0,
                        'anomaly_rate' => 0,
                        'performance_score' => 0
                    ];
                }

                $anomalyCount = $readings->filter(function($reading) {
                    $machine = $reading->machine;
                    if (!$machine) return false;

                    $temp = $reading->temperature_value;
                    return $temp < $machine->temp_min_normal || $temp > $machine->temp_max_normal;
                })->count();

                return [
                    'branch' => $branch,
                    'machine_count' => $branch->machines->where('is_active', true)->count(),
                    'avg_temperature' => $readings->avg('temperature_value') ?? 0,
                    'min_temperature' => $readings->min('temperature_value') ?? 0,
                    'max_temperature' => $readings->max('temperature_value') ?? 0,
                    'total_readings' => $readings->count(),
                    'anomaly_count' => $anomalyCount,
                    'anomaly_rate' => $readings->count() > 0 ? ($anomalyCount / $readings->count()) * 100 : 0,
                    'performance_score' => $this->calculatePerformanceScore($branch, $readings, $anomalyCount)
                ];
            });
    }

    public function getTemperatureTrends($days = 30)
    {
        $fromDate = now()->subDays($days);

        $dailyAverages = Temperature::select(
                DB::raw('DATE(timestamp) as date'),
                DB::raw('AVG(temperature_value) as avg_temperature'),
                DB::raw('MIN(temperature_value) as min_temperature'),
                DB::raw('MAX(temperature_value) as max_temperature'),
                DB::raw('COUNT(*) as reading_count')
            )
            ->where('timestamp', '>=', $fromDate)
            ->groupBy(DB::raw('DATE(timestamp)'))
            ->orderBy('date')
            ->get();

        return $dailyAverages;
    }

    public function getAdvancedAnalytics($filters = [])
    {
        $query = Temperature::with(['machine.branch']);

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
            $query->where('timestamp', '>=', $filters['date_from'] . ' 00:00:00');
        }

        if (isset($filters['date_to']) && $filters['date_to']) {
            $query->where('timestamp', '<=', $filters['date_to'] . ' 23:59:59');
        }

        $readings = $query->orderBy('timestamp')->get();

       Log::info('Advanced Analytics Debug:', [
            'filters' => $filters,
            'readings_count' => $readings->count(),
            'has_readings' => $readings->isNotEmpty()
        ]);

        // Jika tidak ada data sama sekali, generate demo data
        if ($readings->isEmpty()) {
            Log::info('No readings found, generating demo data');
            return $this->generateDemoDataFromFilters($filters);
        }

        // Generate daily analytics
        $dailyData = $this->getDailyAnalytics($readings);

        Log::info('Daily Analytics Debug:', [
            'daily_data_count' => $dailyData->count(),
            'first_daily_item' => $dailyData->first()
        ]);

        // Jika data kurang dari 7 hari, enrich dengan demo data
        if ($dailyData->count() < 7) {
            Log::info('Enriching with demo data');
            return $this->enrichWithDemoData($dailyData, $readings);
        }

        return $dailyData;
    }
    private function generateDemoDataFromFilters($filters)
    {
        $demoData = collect();
        $dateFrom = $filters['date_from'] ?? now()->subDays(30)->format('Y-m-d');
        $dateTo = $filters['date_to'] ?? now()->format('Y-m-d');

        $startDate = Carbon::parse($dateFrom);
        $endDate = Carbon::parse($dateTo);
        $daysDiff = $startDate->diffInDays($endDate);

        // Base temperature untuk demo (sesuai dengan data seasonal yang ada)
        $baseTemp = -15.0;

        Log::info('Generating demo data from filters', [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'days_diff' => $daysDiff,
            'base_temp' => $baseTemp
        ]);

        for ($i = 0; $i <= $daysDiff && $i < 30; $i++) {
            $currentDate = $startDate->copy()->addDays($i);

            // Variasi temperatur
            $tempVariation = rand(-300, 300) / 100; // ±3°C variation
            $demoTemp = $baseTemp + $tempVariation;

            $demoData->push([
                'date' => $currentDate->format('Y-m-d'),
                'avg_temperature' => round($demoTemp, 2),
                'min_temperature' => round($demoTemp - rand(10, 30) / 10, 2),
                'max_temperature' => round($demoTemp + rand(10, 30) / 10, 2),
                'reading_count' => rand(5, 15),
                'anomaly_count' => rand(0, 2),
                'is_demo' => true
            ]);
        }

        Log::info('Demo data generated', ['count' => $demoData->count()]);
        return $demoData;
    }
    private function enrichWithDemoData($dailyData, $readings)
    {
        $demoData = collect();
        $baseTemp = $readings->isNotEmpty() ? $readings->avg('temperature_value') : -15.0;

        // Gunakan tanggal dari data yang ada atau default 30 hari terakhir
        $startDate = $dailyData->isNotEmpty()
            ? Carbon::parse($dailyData->first()['date'])->subDays(15) // Mulai 15 hari sebelum data pertama
            : now()->subDays(29);

        $endDate = $dailyData->isNotEmpty()
            ? Carbon::parse($dailyData->last()['date'])->addDays(15) // Tambah 15 hari setelah data terakhir
            : now();

        // Pastikan tidak lebih dari 30 hari
        $daysDiff = $startDate->diffInDays($endDate);
        if ($daysDiff > 30) {
            $endDate = $startDate->copy()->addDays(29);
        }

        Log::info('Enriching demo data', [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'base_temp' => $baseTemp,
            'existing_data_count' => $dailyData->count()
        ]);

        // Generate data untuk setiap hari dalam range
        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');

            // Cek apakah ada data real untuk tanggal ini
            $existing = $dailyData->where('date', $dateStr)->first();
            if ($existing) {
                $demoData->push($existing);
            } else {
                // Generate data demo
                $tempVariation = rand(-300, 300) / 100;
                $demoTemp = $baseTemp + $tempVariation;

                $demoData->push([
                    'date' => $dateStr,
                    'avg_temperature' => round($demoTemp, 2),
                    'min_temperature' => round($demoTemp - rand(10, 30) / 10, 2),
                    'max_temperature' => round($demoTemp + rand(10, 30) / 10, 2),
                    'reading_count' => rand(5, 15),
                    'anomaly_count' => rand(0, 2),
                    'is_demo' => true
                ]);
            }

            $currentDate->addDay();
        }

        Log::info('Enriched data generated', ['count' => $demoData->count()]);
        return $demoData->sortBy('date')->values();
    }

    private function generateDemoData($readings, $existingData)
    {
        $demoData = collect();

        // Gunakan data yang ada sebagai base
        $baseDate = $readings->first()->timestamp;
        $baseTemp = $readings->avg('temperature_value');

        // Generate 30 hari data demo berdasarkan data real
        for ($i = 29; $i >= 0; $i--) {
            $date = $baseDate->copy()->subDays($i)->format('Y-m-d');

            // Jika ada data real untuk tanggal ini, gunakan data real
            $existing = $existingData->where('date', $date)->first();
            if ($existing) {
                $demoData->push($existing);
                continue;
            }

            // Generate data demo dengan variasi
            $tempVariation = rand(-500, 500) / 100; // ±5°C variation
            $demoTemp = $baseTemp + $tempVariation;

            $demoData->push([
                'date' => $date,
                'avg_temperature' => round($demoTemp, 2),
                'min_temperature' => round($demoTemp - rand(10, 30) / 10, 2),
                'max_temperature' => round($demoTemp + rand(10, 30) / 10, 2),
                'reading_count' => rand(5, 20),
                'anomaly_count' => rand(0, 2),
                'is_demo' => true // Flag untuk data demo
            ]);
        }

        return $demoData;
    }

    public function getSeasonalAnalysis($filters = [])
    {
        // Get monthly data from Temperature table grouped by month
        $query = Temperature::select(
            DB::raw('MONTH(timestamp) as month'),
            DB::raw('AVG(temperature_value) as avg_temperature'),
            DB::raw('MIN(temperature_value) as min_temperature'),
            DB::raw('MAX(temperature_value) as max_temperature'),
            DB::raw('COUNT(*) as reading_count')
        );

        // Apply filters
        if (isset($filters['branch_id']) && $filters['branch_id']) {
            $query->whereHas('machine', function($q) use ($filters) {
                $q->where('branch_id', $filters['branch_id']);
            });
        }

        if (isset($filters['machine_id']) && $filters['machine_id']) {
            $query->where('machine_id', $filters['machine_id']);
        }

        // Untuk data terbatas, gunakan semua data yang ada (tidak perlu filter tahun)
        $monthlyData = $query->groupBy(DB::raw('MONTH(timestamp)'))
                           ->orderBy('month')
                           ->get();

        // Jika data kurang dari 6 bulan, generate data demo
        if ($monthlyData->count() < 6) {
            return $this->generateSeasonalDemoData($monthlyData);
        }

        return $monthlyData->map(function ($data) {
            return [
                'month' => $this->getMonthName($data->month),
                'month_number' => $data->month,
                'avg_temperature' => round($data->avg_temperature ?? 0, 2),
                'min_temperature' => round($data->min_temperature ?? 0, 2),
                'max_temperature' => round($data->max_temperature ?? 0, 2),
                'reading_count' => $data->reading_count ?? 0
            ];
        });
    }

    private function generateSeasonalDemoData($realData)
    {
        $demoData = collect();
        $baseTemp = $realData->isNotEmpty() ? $realData->avg('avg_temperature') : -11.18;

        for ($month = 1; $month <= 12; $month++) {
            // Cek apakah ada data real untuk bulan ini
            $realMonth = $realData->where('month', $month)->first();
            if ($realMonth) {
                $demoData->push([
                    'month' => $this->getMonthName($month),
                    'month_number' => $month,
                    'avg_temperature' => round($realMonth->avg_temperature, 2),
                    'min_temperature' => round($realMonth->min_temperature, 2),
                    'max_temperature' => round($realMonth->max_temperature, 2),
                    'reading_count' => $realMonth->reading_count,
                    'is_real' => true
                ]);
                continue;
            }

            // Generate data demo dengan pola musiman
            $seasonalVariation = 0;
            if ($month >= 3 && $month <= 5) { // Spring
                $seasonalVariation = 5;
            } elseif ($month >= 6 && $month <= 8) { // Summer
                $seasonalVariation = 10;
            } elseif ($month >= 9 && $month <= 11) { // Fall
                $seasonalVariation = 2;
            } else { // Winter
                $seasonalVariation = -5;
            }

            $demoTemp = $baseTemp + $seasonalVariation + (rand(-200, 200) / 100);

            $demoData->push([
                'month' => $this->getMonthName($month),
                'month_number' => $month,
                'avg_temperature' => round($demoTemp, 2),
                'min_temperature' => round($demoTemp - rand(15, 40) / 10, 2),
                'max_temperature' => round($demoTemp + rand(15, 40) / 10, 2),
                'reading_count' => rand(50, 200),
                'is_demo' => true
            ]);
        }

        return $demoData;
    }

    public function getBranchComparison($filters = [])
    {
        $branchData = Branch::with(['machines'])->where('is_active', true)->get();

        return $branchData->map(function ($branch) use ($filters) {
            $query = Temperature::whereHas('machine', function($q) use ($branch) {
                $q->where('branch_id', $branch->id);
            });

            // Apply date filters
            if (isset($filters['date_from']) && $filters['date_from']) {
                $query->where('timestamp', '>=', $filters['date_from'] . ' 00:00:00');
            }
            if (isset($filters['date_to']) && $filters['date_to']) {
                $query->where('timestamp', '<=', $filters['date_to'] . ' 23:59:59');
            }

            $readings = $query->get();

            // Handle empty readings
            if ($readings->isEmpty()) {
                return [
                    'branch_name' => $branch->name,
                    'machine_count' => $branch->machines->where('is_active', true)->count(),
                    'total_readings' => 0,
                    'avg_temperature' => 0,
                    'min_temperature' => 0,
                    'max_temperature' => 0,
                    'anomaly_count' => 0,
                    'anomaly_rate' => 0,
                    'performance_score' => 0
                ];
            }

            $anomalyCount = $readings->filter(function($reading) {
                $machine = $reading->machine;
                if (!$machine) return false;

                $temp = $reading->temperature_value;
                return $temp < $machine->temp_min_normal || $temp > $machine->temp_max_normal;
            })->count();

            return [
                'branch_name' => $branch->name,
                'machine_count' => $branch->machines->where('is_active', true)->count(),
                'total_readings' => $readings->count(),
                'avg_temperature' => $readings->avg('temperature_value') ?? 0,
                'min_temperature' => $readings->min('temperature_value') ?? 0,
                'max_temperature' => $readings->max('temperature_value') ?? 0,
                'anomaly_count' => $anomalyCount,
                'anomaly_rate' => $readings->count() > 0 ? ($anomalyCount / $readings->count()) * 100 : 0,
                'performance_score' => $this->calculatePerformanceScore($branch, $readings, $anomalyCount)
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
        $machines = Machine::with(['temperature' => function($query) {
            $query->where('timestamp', '>=', now()->subDays(30));
        }, 'maintenanceRecommendations' => function($query) {
            $query->where('status', 'pending');
        }])
        ->where('is_active', true)
        ->get();

        return $machines->map(function ($machine) {
            $readings = $machine->temperature;

            // Handle empty readings
            if ($readings->isEmpty()) {
                return [
                    'machine' => $machine,
                    'risk_score' => 0,
                    'trend_direction' => 'stable',
                    'trend_rate' => 0,
                    'avg_temperature' => 0,
                    'anomaly_count' => 0,
                    'days_since_maintenance' => $this->getDaysSinceLastMaintenance($machine),
                    'predicted_maintenance_date' => now()->addDays(365),
                    'recommendations' => []
                ];
            }

            $trend = $this->calculateTemperatureTrend($readings);
            $riskScore = $this->calculateMaintenanceRisk($machine, $readings, $trend);

            return [
                'machine' => $machine,
                'risk_score' => $riskScore,
                'trend_direction' => $trend['direction'],
                'trend_rate' => $trend['rate'],
                'avg_temperature' => $readings->avg('temperature_value') ?? 0,
                'anomaly_count' => $this->countAnomaliesForReadings($readings),
                'days_since_maintenance' => $this->getDaysSinceLastMaintenance($machine),
                'predicted_maintenance_date' => $this->predictMaintenanceDate($machine, $riskScore),
                'recommendations' => $this->generateMaintenanceRecommendations($machine, $riskScore, $trend)
            ];
        })->sortByDesc('risk_score')->values();
    }

    private function getDailyAnalytics($readings)
    {
        if ($readings->isEmpty()) {
            return collect([]);
        }

        return $readings->groupBy(function($reading) {
            return $reading->timestamp->format('Y-m-d');
        })->map(function($dailyReadings, $date) {
            $temperatures = $dailyReadings->pluck('temperature_value')->filter();

            return [
                'date' => $date,
                'avg_temperature' => $temperatures->isNotEmpty() ? $temperatures->avg() : 0,
                'min_temperature' => $temperatures->isNotEmpty() ? $temperatures->min() : 0,
                'max_temperature' => $temperatures->isNotEmpty() ? $temperatures->max() : 0,
                'reading_count' => $dailyReadings->count(),
                'anomaly_count' => $this->countAnomaliesForReadings($dailyReadings)
            ];
        })->values();
    }

    private function calculatePerformanceScore($branch, $readings, $anomalyCount = null)
    {
         if ($readings->isEmpty()) {
        // Return demo score jika tidak ada data
        return max(60, min(95, rand(70, 90))); // Score antara 70-90 untuk demo
    }

    $score = 100;

    // Calculate anomaly count if not provided
    if ($anomalyCount === null) {
        $anomalyCount = $this->countAnomaliesForReadings($readings);
    }

    // Reduce score based on anomaly rate
    $anomalyRate = ($anomalyCount / $readings->count()) * 100;
    $score -= $anomalyRate * 0.5;

    // Reduce score based on temperature variance
    $temperatures = $readings->pluck('temperature_value');
    $variance = $this->calculateVariance($temperatures);
    $score -= min($variance * 2, 20);

    // Reduce score based on pending maintenance
    $pendingMaintenance = MaintenanceRecommendation::whereHas('machine', function($query) use ($branch) {
        $query->where('branch_id', $branch->id);
    })->where('status', 'pending')->count();

    $score -= $pendingMaintenance * 5;

    // Ensure score is between 0 and 100
    return max(0, min(100, round($score, 2)));
    }

    private function countAnomaliesForReadings($readings)
    {
        return $readings->filter(function($reading) {
            $machine = $reading->machine;
            if (!$machine) return false;

            $temp = $reading->temperature_value;
            return $temp < $machine->temp_min_normal || $temp > $machine->temp_max_normal;
        })->count();
    }

    private function calculateVariance($values)
    {
        if ($values->isEmpty()) return 0;

        $mean = $values->avg();
        return $values->map(function($value) use ($mean) {
            return pow($value - $mean, 2);
        })->avg();
    }

    private function calculateTemperatureTrend($readings)
    {
        if ($readings->count() < 2) {
            return ['direction' => 'stable', 'rate' => 0];
        }

        $readings = $readings->sortBy('timestamp');
        $first = $readings->first();
        $last = $readings->last();

        $tempChange = $last->temperature_value - $first->temperature_value;
        $timeChange = $last->timestamp->diffInHours($first->timestamp);

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
            $risk += min($ageYears * 5, 30);
        }

        // Anomaly factor
        if ($readings->count() > 0) {
            $anomalyCount = $this->countAnomaliesForReadings($readings);
            $anomalyRate = ($anomalyCount / $readings->count()) * 100;
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
            $variance = $this->calculateVariance($readings->pluck('temperature_value'));
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
        $baseDays = 365;

        $riskMultiplier = 1 - ($riskScore / 200);
        $adjustedDays = max(30, $baseDays * $riskMultiplier);

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
