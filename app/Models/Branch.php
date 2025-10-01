<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'address',
        'city',
        'region',
        'contact_info',
        'is_active'
    ];

    protected $casts = [
        'contact_info' => 'json',
        'is_active' => 'boolean'
    ];

    public function machines(): HasMany
    {
        return $this->hasMany(Machine::class);
    }

    public function activeMachines(): HasMany
    {
        return $this->hasMany(Machine::class)->where('is_active', true);
    }

    public function temperatureReadings()
    {
        return $this->hasManyThrough(TemperatureReading::class, Machine::class);
    }

    public function monthlySummaries()
    {
        return $this->hasManyThrough(MonthlySummary::class, Machine::class);
    }

    public function anomalies()
    {
        return $this->hasManyThrough(Anomaly::class, Machine::class);
    }

    public function getAverageTemperatureAttribute()
    {
        return $this->temperatureReadings()->avg('temperature');
    }

    public function getActiveAnomaliesCountAttribute()
    {
        return $this->anomalies()->whereIn('status', ['new', 'acknowledged', 'investigating'])->count();
    }
}
