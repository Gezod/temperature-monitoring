<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AnomalyDetectionService;
use App\Models\Temperature;
use App\Models\TemperatureReading;
use Carbon\Carbon;

class RunAnomalyDetection extends Command
{
    protected $signature = 'anomaly:detect {--days=1 : Number of days to check} {--cleanup : Cleanup duplicates before detection}';
    protected $description = 'Run anomaly detection on temperature data with duplicate prevention';

    protected $anomalyService;

    public function __construct(AnomalyDetectionService $anomalyService)
    {
        parent::__construct();
        $this->anomalyService = $anomalyService;
    }

    public function handle()
    {
        $days = $this->option('days');
        $cleanup = $this->option('cleanup');
        $fromDate = Carbon::now()->subDays($days);

        $this->info("Starting anomaly detection with duplicate prevention for the last {$days} days...");
        $this->info("Checking temperatures from: {$fromDate->format('Y-m-d H:i:s')}");
        $this->info("Only processing temperatures above 5°C");

        // ✅ Optional cleanup before detection
        if ($cleanup) {
            $this->info("🧹 Cleaning up duplicate anomalies first...");
            $cleanupResult = $this->anomalyService->cleanupDuplicateAnomalies(false);
            $this->info("🧹 Cleaned up {$cleanupResult['exact_count']} exact duplicates and {$cleanupResult['similar_count']} similar duplicates");
        }

        // BACA DARI TABEL TEMPERATURE DENGAN FILTER > 5°C
        $temperatures = Temperature::with('machine')
            ->where('recorded_at', '>=', $fromDate)
            ->where('temperature', '>', 5)
            ->orderBy('recorded_at')
            ->get();

        $this->info("Found {$temperatures->count()} temperature readings (>5°C) to process.");

        $totalAnomalies = 0;
        $duplicatesPrevented = 0;
        $processedCount = 0;
        $bar = $this->output->createProgressBar($temperatures->count());
        $bar->start();

        foreach ($temperatures as $temperature) {
            // TRANSFER DATA KE TEMPERATURE_READINGS JIKA SUHU > 5°C
            $temperatureReading = $this->transferToTemperatureReadings($temperature);

            if ($temperatureReading) {
                // CHECK ANOMALI PADA TEMPERATURE_READINGS dengan duplicate prevention
                $anomalies = $this->anomalyService->checkSingleReading($temperatureReading);

                // Count successful anomaly creation vs prevented duplicates
                $successCount = 0;
                $preventedCount = 0;

                foreach ($anomalies as $anomaly) {
                    if ($anomaly && isset($anomaly->id)) {
                        $successCount++;
                    } else {
                        $preventedCount++;
                    }
                }

                $totalAnomalies += $successCount;
                $duplicatesPrevented += $preventedCount;
                $processedCount++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        // ✅ Show comprehensive results
        $this->info("Anomaly detection completed with duplicate prevention!");
        $this->info("Processed {$processedCount} temperature readings (>5°C)");
        $this->info("New anomalies detected: {$totalAnomalies}");

        if ($duplicatesPrevented > 0) {
            $this->warn("Duplicates prevented: {$duplicatesPrevented}");
        }

        // ✅ Show duplicate prevention stats
        $stats = $this->anomalyService->getDuplicateCheckStats(null, $days);
        $this->info("\n📊 Duplicate Prevention Statistics:");
        $this->info("- Total anomalies in period: {$stats['total_anomalies']}");
        $this->info("- Today's anomalies: {$stats['today_count']}");
        $this->info("- Configuration:");
        $this->info("  * Check window: {$stats['config']['duplicate_check_hours']} hours");
        $this->info("  * Temperature tolerance: {$stats['config']['temperature_tolerance']}°C");
        $this->info("  * Similar anomaly window: {$stats['config']['similar_anomaly_window_hours']} hours");
        $this->info("  * Daily limit per type: {$stats['config']['max_same_type_per_day']}");

        return 0;
    }

    /**
     * Transfer data dari Temperature ke TemperatureReading untuk suhu > 5°C
     */
    private function transferToTemperatureReadings(Temperature $temperature)
    {
        try {
            // ✅ Improved duplicate check with tolerance
            $recordedAt = $this->getRecordedAt($temperature);

            $existingReading = TemperatureReading::where('machine_id', $temperature->machine_id)
                ->where('temperature', $temperature->temperature)
                ->whereBetween('recorded_at', [
                    $recordedAt->copy()->subMinutes(5),
                    $recordedAt->copy()->addMinutes(5)
                ])
                ->first();

            if ($existingReading) {
                return $existingReading;
            }

            // Buat record baru di temperature_readings
            $temperatureReading = TemperatureReading::create([
                'machine_id' => $temperature->machine_id,
                'recorded_at' => $recordedAt,
                'temperature' => $temperature->temperature,
                'reading_type' => $temperature->reading_type ?? 'transferred',
                'source_file' => $temperature->source_file ?? 'temperature_table',
                'metadata' => $temperature->metadata,
                'is_anomaly' => false
            ]);

            return $temperatureReading;

        } catch (\Exception $e) {
            $this->error("Failed to transfer temperature to readings: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get recorded_at value from temperature record
     */
    private function getRecordedAt(Temperature $temperature)
    {
        if ($temperature->recorded_at) {
            return Carbon::parse($temperature->recorded_at);
        }

        if ($temperature->timestamp) {
            return Carbon::parse($temperature->timestamp);
        }

        return $temperature->created_at;
    }
}
