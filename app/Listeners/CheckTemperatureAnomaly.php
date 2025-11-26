<?php

namespace App\Listeners;

use App\Events\TemperatureUpdated;
use App\Services\AnomalyDetectionService;
use App\Models\TemperatureReading;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CheckTemperatureAnomaly
{
    public function handle(TemperatureUpdated $event)
    {
        try {
            $anomalyService = new AnomalyDetectionService();

            // SMART CHECK: Only proceed if significant change or validation
            if (!$this->shouldCheckAnomaly($event)) {
                Log::info("Anomaly check skipped for temperature {$event->temperature->id}");
                return;
            }

            // Cari atau buat temperature reading
            $reading = $this->findOrCreateTemperatureReading($event->temperature);

            if ($reading) {
                $anomalies = $anomalyService->checkSingleReading($reading);

                Log::info("Auto anomaly check for temperature {$event->temperature->id}: " .
                    count($anomalies) . " anomalies found");

                // Bisa tambahkan notification logic di sini
                if (count($anomalies) > 0) {
                    // Optional: Store anomaly info in session untuk user feedback
                    session()->flash('anomaly_detected', true);
                    session()->flash('anomaly_count', count($anomalies));
                }
            }
        } catch (\Exception $e) {
            Log::error("Anomaly check failed for temperature {$event->temperature->id}: " . $e->getMessage());
        }
    }
    private function debugData($temperature)
    {
        Log::debug("=== DEBUG Temperature Data ===");
        Log::debug("Temperature ID: " . $temperature->id);
        Log::debug("Machine ID: " . $temperature->machine_id);
        Log::debug("Temperature Value: " . $temperature->temperature_value);
        Log::debug("Timestamp: " . $temperature->timestamp);
        Log::debug("Validation Status: " . $temperature->validation_status);

        // Cek existing readings
        $existingReadings = TemperatureReading::where('machine_id', $temperature->machine_id)
            ->where('recorded_at', $temperature->timestamp)
            ->get();

        Log::debug("Existing TemperatureReadings count: " . $existingReadings->count());
    }
    /**
     * Determine if anomaly check should be performed
     */
    private function shouldCheckAnomaly(TemperatureUpdated $event): bool
    {
        // Jika tidak ada old data, always check
        if (empty($event->oldData)) {
            return true;
        }

        $oldTemp = $event->oldData['temperature_value'] ?? null;
        $newTemp = $event->temperature->temperature_value;
        $oldStatus = $event->oldData['validation_status'] ?? null;
        $newStatus = $event->temperature->validation_status;

        // Check temperature change (≥ 2°C)
        if ($oldTemp && abs($oldTemp - $newTemp) >= 2.0) {
            return true;
        }

        // Check validation status change to 'validated'
        if ($oldStatus !== 'validated' && $newStatus === 'validated') {
            return true;
        }

        // Check if this is a new validation
        if (!$oldStatus && $newStatus === 'validated') {
            return true;
        }

        return false;
    }

    /**
     * Find or create TemperatureReading
     */
    private function findOrCreateTemperatureReading($temperature)
    {
        try {
            // Coba updateOrCreate langsung
            $readingData = [
                'temperature' => $temperature->temperature_value,
                'reading_type' => 'manual_entry',
                'is_anomaly' => false,
                'updated_at' => now()
            ];

            // Tambahkan temperature_id jika fieldnya ada
            if (Schema::hasColumn('temperature_readings', 'temperature_id')) {
                $readingData['temperature_id'] = $temperature->id;
            }

            $reading = TemperatureReading::updateOrCreate(
                [
                    'machine_id' => $temperature->machine_id,
                    'recorded_at' => $temperature->timestamp
                ],
                $readingData
            );

            Log::info("TemperatureReading synced: ID {$reading->id} for Temperature ID {$temperature->id}");

            return $reading;
        } catch (\Exception $e) {
            Log::error("Error syncing TemperatureReading for Temperature {$temperature->id}: " . $e->getMessage());

            // Fallback: Try to find existing
            $existing = TemperatureReading::where('machine_id', $temperature->machine_id)
                ->where('recorded_at', $temperature->timestamp)
                ->first();

            if ($existing) {
                $existing->update(['temperature' => $temperature->temperature_value]);
                return $existing;
            }

            return null;
        }
    }
}
