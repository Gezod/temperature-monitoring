<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Temperature extends Model
{
    use HasFactory;

    protected $table = 'temperature';

    protected $fillable = [
        'machine_id',
        'temperature_value',
        'timestamp',
        'reading_date',
        'reading_time',
        'validation_status',
        'notes',
        'is_validated'
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'is_validated' => 'boolean'
    ];

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }

    // Helper methods
    public function getFormattedTimestampAttribute()
    {
        return $this->timestamp->format('Y-m-d H:i:s');
    }

    public function getTemperatureDisplayAttribute()
    {
        return $this->temperature_value . '°C';
    }

    // New method untuk mendapatkan status warna
    public function getValidationColorAttribute()
    {
        if ($this->is_validated === 1) {
            return 'success'; // Hijau untuk validated
        } elseif ($this->is_validated === 0) {
            return 'danger'; // Merah untuk rejected
        } else {
            return 'warning'; // Kuning untuk needs_review, manual_entry, imported, edited
        }
    }

    // New method untuk mendapatkan teks status
    public function getValidationTextAttribute()
    {
        if ($this->is_validated === 1) {
            return 'Validated';
        } elseif ($this->is_validated === 0) {
            return 'Rejected';
        } else {
            return ucfirst(str_replace('_', ' ', $this->validation_status));
        }
    }
    public function anomalies()
    {
        return $this->hasMany(Anomaly::class, 'temperature_reading_id');
    }
}
