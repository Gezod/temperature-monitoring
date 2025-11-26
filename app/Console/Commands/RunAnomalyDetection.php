<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AnomalyDetectionService;
use App\Models\Temperature; // BACA DARI TEMPERATURE
use App\Models\TemperatureReading; // SIMPAN KE TEMPERATURE_READINGS
use Carbon\Carbon;

class RunAnomalyDetection extends Command
{
    protected $signature = 'anomaly:detect {--days=1 : Number of days to check}';
    protected $description = 'Run anomaly detection on temperature data';

    protected $anomalyService;

    public function __construct(AnomalyDetectionService $anomalyService)
    {
        parent::__construct();
        $this->anomalyService = $anomalyService;
    }

    public function handle()
    {
        $days = $this->option('days');
        $fromDate = Carbon::now()->subDays($days);

        $this->info("Starting anomaly detection for the last {$days} days...");
        $this->info("Checking temperatures from: {$fromDate->format('Y-m-d H:i:s')}");
        $this->info("Only processing temperatures above 5°C");

        // BACA DARI TABEL TEMPERATURE DENGAN FILTER > 5°C
        $temperatures = Temperature::with('machine')
            ->where('recorded_at', '>=', $fromDate)
            ->where('temperature', '>', 5) // FILTER SUHU > 5°C
            ->orderBy('recorded_at')
            ->get();

        $this->info("Found {$temperatures->count()} temperature readings (>5°C) to process.");

        $totalAnomalies = 0;
        $processedCount = 0;
        $bar = $this->output->createProgressBar($temperatures->count());
        $bar->start();

        foreach ($temperatures as $temperature) {
            // TRANSFER DATA KE TEMPERATURE_READINGS JIKA SUHU > 5°C
            $temperatureReading = $this->transferToTemperatureReadings($temperature);

            if ($temperatureReading) {
                // CHECK ANOMALI PADA TEMPERATURE_READINGS
                $anomalies = $this->anomalyService->checkSingleReading($temperatureReading);
                $totalAnomalies += count($anomalies);
                $processedCount++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("Anomaly detection completed!");
        $this->info("Processed {$processedCount} temperature readings (>5°C)");
        $this->info("Total anomalies detected: {$totalAnomalies}");

        return 0;
    }

    /**
     * Transfer data dari Temperature ke TemperatureReading untuk suhu > 5°C
     */
    private function transferToTemperatureReadings(Temperature $temperature)
    {
        try {
            // Cek apakah sudah ada di temperature_readings untuk menghindari duplikasi
            $existingReading = TemperatureReading::where('machine_id', $temperature->machine_id)
                ->where('recorded_at', $temperature->recorded_at)
                ->where('temperature', $temperature->temperature)
                ->first();

            if ($existingReading) {
                return $existingReading;
            }

            // Buat record baru di temperature_readings
            $temperatureReading = TemperatureReading::create([
                'machine_id' => $temperature->machine_id,
                'recorded_at' => $temperature->recorded_at,
                'temperature' => $temperature->temperature,
                'reading_type' => $temperature->reading_type ?? 'transferred',
                'source_file' => $temperature->source_file ?? 'temperature_table',
                'metadata' => $temperature->metadata,
                'is_anomaly' => false
            ]);

            $this->info("Transferred temperature {$temperature->temperature}°C to temperature_readings table");

            return $temperatureReading;

        } catch (\Exception $e) {
            $this->error("Failed to transfer temperature to readings: " . $e->getMessage());
            return null;
        }
    }
}
