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
        'temperature', // PERBAIKAN: Ganti 'temperature' dengan 'temperature_value'
        'reading_type',
        'source_file',
        'metadata',
        'is_anomaly'
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'temperature' => 'decimal:2', // PERBAIKAN: Ganti 'temperature' dengan 'temperature_value'
        'metadata' => 'json',
        'is_anomaly' => 'boolean'
    ];

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    public function anomalies(): HasMany
    {
        return $this->hasMany(Anomaly::class, 'temperature_reading_id');
    }

    public function getStatusAttribute()
    {
        $machine = $this->machine;
        $temp = $this->temperature; // PERBAIKAN: Ganti 'temperature' dengan 'temperature_value'

        if (!$machine) {
            return 'unknown';
        }

        if ($temp < $machine->temp_critical_min || $temp > $machine->temp_critical_max) {
            return 'critical';
        } elseif ($temp < $machine->temp_min_normal || $temp > $machine->temp_max_normal) {
            return 'warning';
        }

        return 'normal';
    }

    /**
     * Scope untuk membaca yang belum memiliki anomali
     */
    public function scopeWithoutAnomalies($query)
    {
        return $query->whereDoesntHave('anomalies');
    }

    /**
     * Scope untuk membaca dalam rentang waktu
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('recorded_at', [$startDate, $endDate]);
    }

    /**
     * Scope untuk mesin tertentu
     */
    public function scopeForMachine($query, $machineId)
    {
        return $query->where('machine_id', $machineId);
    }

    /**
     * Accessor untuk format tampilan suhu
     */
    public function getFormattedTemperatureAttribute()
    {
        return number_format($this->temperature, 1) . '°C';
    }

    /**
     * Accessor untuk waktu yang diformat
     */
    public function getFormattedRecordedAtAttribute()
    {
        return $this->recorded_at->format('d/m/Y H:i:s');
    }

    /**
     * Cek apakah reading ini normal
     */
    public function isNormal()
    {
        return $this->status === 'normal';
    }

    /**
     * Cek apakah reading ini warning
     */
    public function isWarning()
    {
        return $this->status === 'warning';
    }

    /**
     * Cek apakah reading ini critical
     */
    public function isCritical()
    {
        return $this->status === 'critical';
    }
    public function scopeAboveFiveDegrees($query)
    {
        return $query->where('temperature', '>', 5);
    }

    /**
     * Scope untuk membaca dari transfer (bukan langsung dari sensor)
     */
    public function scopeTransferred($query)
    {
        return $query->where('reading_type', 'transferred')
                    ->orWhere('source_file', 'temperature_table');
    }
}
