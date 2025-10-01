<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TemperatureReading extends Model
{
    use HasFactory;

    protected $fillable = [
        'machine_id',
        'recorded_at',
        'temperature',
        'reading_type',
        'source_file',
        'metadata',
        'is_anomaly'
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'temperature' => 'decimal:2',
        'metadata' => 'json',
        'is_anomaly' => 'boolean'
    ];

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    public function anomalies(): HasMany
    {
        return $this->hasMany(Anomaly::class);
    }

    public function getStatusAttribute()
    {
        $machine = $this->machine;
        $temp = $this->temperature;

        if ($temp < $machine->temp_critical_min || $temp > $machine->temp_critical_max) {
            return 'critical';
        } elseif ($temp < $machine->temp_min_normal || $temp > $machine->temp_max_normal) {
            return 'warning';
        }

        return 'normal';
    }
}
