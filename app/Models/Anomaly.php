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

    /**
     * Machine relationship
     */
    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    /**
     * Temperature Reading relationship
     * Uses temperature_reading_id
     */
    public function temperatureReading(): BelongsTo
    {
        return $this->belongsTo(Temperature::class, 'temperature_reading_id');
    }


    /**
     * Human readable anomaly type
     */
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

    /**
     * Severity color badge
     */
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

    /**
     * Status color badge
     */
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
