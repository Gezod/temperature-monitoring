<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Temperature;
use App\Models\TemperatureReading;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SyncTemperatureReadings extends Command
{
    protected $signature = 'sync:temperature-readings {id?}';
    protected $description = 'Sync Temperature records to TemperatureReadings table';

    public function handle()
    {
        $temperatureId = $this->argument('id');

        if ($temperatureId) {
            // Sync specific temperature
            $temperature = Temperature::find($temperatureId);
            if ($temperature) {
                $this->syncSingleTemperature($temperature);
            }
        } else {
            // Sync all temperatures
            $temperatures = Temperature::where('validation_status', 'validated')->get();
            $this->info("Syncing {$temperatures->count()} temperature records...");

            foreach ($temperatures as $temperature) {
                $this->syncSingleTemperature($temperature);
            }
        }

        $this->info('Sync completed!');
    }

    private function syncSingleTemperature($temperature)
    {
        $readingData = [
            'machine_id' => $temperature->machine_id,
            'temperature' => $temperature->temperature_value,
            'recorded_at' => $temperature->timestamp,
            'reading_type' => 'manual_entry',
            'source_file' => 'temperature_source',
            'is_anomaly' => false,
            'created_at' => now(),
            'updated_at' => now()
        ];

        // Add temperature_id if column exists
        if (Schema::hasColumn('temperature_readings', 'temperature_id')) {
            $readingData['temperature_id'] = $temperature->id;
        }

        // Check if already exists
        $existing = TemperatureReading::where('machine_id', $temperature->machine_id)
            ->where('recorded_at', $temperature->timestamp)
            ->first();

        if ($existing) {
            $existing->update($readingData);
            $this->info("Updated: Temperature {$temperature->id} → Reading {$existing->id}");
        } else {
            $reading = TemperatureReading::create($readingData);
            $this->info("Created: Temperature {$temperature->id} → Reading {$reading->id}");
        }
    }
}
