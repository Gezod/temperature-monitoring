<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceRecommendation extends Model
{
    use HasFactory;

    protected $fillable = [
        'machine_id',
        'type',
        'priority',
        'title',
        'description',
        'reason',
        'recommended_date',
        'estimated_duration_hours',
        'estimated_cost',
        'required_parts',
        'status',
        'scheduled_date',
        'completed_date',
        'completion_notes',
        'trend_data'
    ];

    protected $casts = [
        'recommended_date' => 'date',
        'scheduled_date' => 'date',
        'completed_date' => 'date',
        'estimated_cost' => 'decimal:2',
        'required_parts' => 'json',
        'trend_data' => 'json'
    ];

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    public function getTypeNameAttribute()
    {
        $types = [
            'preventive' => 'Preventif',
            'predictive' => 'Prediktif',
            'corrective' => 'Korektif',
            'emergency' => 'Darurat'
        ];

        return $types[$this->type] ?? $this->type;
    }

    public function getPriorityColorAttribute()
    {
        $colors = [
            'low' => 'success',
            'medium' => 'warning',
            'high' => 'danger',
            'critical' => 'dark'
        ];

        return $colors[$this->priority] ?? 'secondary';
    }

    public function getStatusColorAttribute()
    {
        $colors = [
            'pending' => 'warning',
            'scheduled' => 'info',
            'in_progress' => 'primary',
            'completed' => 'success',
            'cancelled' => 'secondary'
        ];

        return $colors[$this->status] ?? 'secondary';
    }

    public function getIsOverdueAttribute()
    {
        return $this->status === 'pending' && $this->recommended_date < now()->toDateString();
    }
}
