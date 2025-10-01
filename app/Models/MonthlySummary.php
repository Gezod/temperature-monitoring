<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlySummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'machine_id',
        'year',
        'month',
        'temp_avg',
        'temp_min',
        'temp_max',
        'total_readings',
        'anomaly_count',
        'uptime_percentage',
        'daily_averages'
    ];

    protected $casts = [
        'temp_avg' => 'decimal:2',
        'temp_min' => 'decimal:2',
        'temp_max' => 'decimal:2',
        'uptime_percentage' => 'decimal:2',
        'daily_averages' => 'json'
    ];

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    public function getMonthNameAttribute()
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        return $months[$this->month];
    }

    public function getPerformanceScoreAttribute()
    {
        $score = 100;

        // Reduce score based on anomalies
        if ($this->anomaly_count > 0 && $this->total_readings > 0) {
            $anomalyRate = ($this->anomaly_count / $this->total_readings) * 100;
            $score -= $anomalyRate * 0.5;
        }

        // Reduce score based on uptime
        $score = $score * ($this->uptime_percentage / 100);

        return max(0, round($score, 2));
    }
}
