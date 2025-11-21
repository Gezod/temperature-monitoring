<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AnomalyDetectionService;
use App\Models\Temperature;
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

        $temperatures = Temperature::where('timestamp', '>=', $fromDate)
            ->whereDoesntHave('anomalies')
            ->orderBy('timestamp')
            ->get();

        $this->info("Found {$temperatures->count()} temperature readings to check.");

        $totalAnomalies = 0;
        $bar = $this->output->createProgressBar($temperatures->count());
        $bar->start();

        foreach ($temperatures as $temperature) {
            $anomalies = $this->anomalyService->checkSingleReading($temperature);
            $totalAnomalies += count($anomalies);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("Anomaly detection completed!");
        $this->info("Total anomalies detected: {$totalAnomalies}");

        return 0;
    }
}
