<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Anomaly extends Model
{
    use HasFactory;

    protected $fillable = [
        'machine_id',
        'temperature_reading_id',
        'severity',
        'type',
        'description',
        'possible_causes',
        'recommendations',
        'status',
        'detected_at',
        'acknowledged_at',
        'resolved_at',
        'acknowledged_by',
        'resolution_notes'
    ];

    protected $casts = [
        'detected_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime'
    ];

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    public function temperatureReading(): BelongsTo
    {
        return $this->belongsTo(TemperatureReading::class);
    }

    public function getTypeNameAttribute()
    {
        $types = [
            'temperature_high' => 'Suhu Terlalu Tinggi',
            'temperature_low' => 'Suhu Terlalu Rendah',
            'rapid_change' => 'Perubahan Suhu Cepat',
            'sensor_error' => 'Error Sensor',
            'pattern_deviation' => 'Penyimpangan Pola'
        ];

        return $types[$this->type] ?? $this->type;
    }

    public function getSeverityColorAttribute()
    {
        $colors = [
            'low' => 'success',
            'medium' => 'warning',
            'high' => 'danger',
            'critical' => 'dark'
        ];

        return $colors[$this->severity] ?? 'secondary';
    }

    public function getStatusColorAttribute()
    {
        $colors = [
            'new' => 'danger',
            'acknowledged' => 'warning',
            'investigating' => 'info',
            'resolved' => 'success',
            'false_positive' => 'secondary'
        ];

        return $colors[$this->status] ?? 'secondary';
    }
}
