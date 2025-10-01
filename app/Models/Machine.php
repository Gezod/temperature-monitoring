<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Machine extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'name',
        'type',
        'model',
        'serial_number',
        'installation_date',
        'specifications',
        'temp_min_normal',
        'temp_max_normal',
        'temp_critical_min',
        'temp_critical_max',
        'is_active'
    ];

    protected $casts = [
        'installation_date' => 'date',
        'specifications' => 'json',
        'temp_min_normal' => 'decimal:2',
        'temp_max_normal' => 'decimal:2',
        'temp_critical_min' => 'decimal:2',
        'temp_critical_max' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function temperatureReadings(): HasMany
    {
        return $this->hasMany(TemperatureReading::class);
    }

    public function monthlySummaries(): HasMany
    {
        return $this->hasMany(MonthlySummary::class);
    }

    public function anomalies(): HasMany
    {
        return $this->hasMany(Anomaly::class);
    }

    public function maintenanceRecommendations(): HasMany
    {
        return $this->hasMany(MaintenanceRecommendation::class);
    }

    public function getLatestTemperatureAttribute()
    {
        return $this->temperatureReadings()->latest('recorded_at')->first();
    }

    public function getCurrentStatusAttribute()
    {
        $latest = $this->latest_temperature;
        if (!$latest) return 'no_data';

        $temp = $latest->temperature;

        if ($temp < $this->temp_critical_min || $temp > $this->temp_critical_max) {
            return 'critical';
        } elseif ($temp < $this->temp_min_normal || $temp > $this->temp_max_normal) {
            return 'warning';
        }

        return 'normal';
    }

    public function getActiveAnomaliesCountAttribute()
    {
        return $this->anomalies()->whereIn('status', ['new', 'acknowledged', 'investigating'])->count();
    }
}
