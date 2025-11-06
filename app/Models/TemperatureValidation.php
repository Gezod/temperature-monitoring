<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemperatureValidation extends Model
{
    use HasFactory;

    protected $fillable = [
        'machine_id',
        'upload_session_id',
        'raw_data',
        'validation_errors',
        'is_validated',
        'is_imported',
        'status',
        'uploaded_at'
    ];

    protected $casts = [
        'raw_data' => 'json',
        'validation_errors' => 'json',
        'is_validated' => 'boolean',
        'is_imported' => 'boolean',
        'uploaded_at' => 'datetime'
    ];

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    public function getValidationErrorsCountAttribute(): int
    {
        return is_array($this->validation_errors) ? count($this->validation_errors) : 0;
    }

    public function getDataCountAttribute(): int
    {
        return is_array($this->raw_data) ? count($this->raw_data) : 0;
    }
}
