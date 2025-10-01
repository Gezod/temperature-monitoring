<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'level',
        'title',
        'message',
        'data',
        'is_read',
        'is_dismissed',
        'expires_at'
    ];

    protected $casts = [
        'data' => 'json',
        'is_read' => 'boolean',
        'is_dismissed' => 'boolean',
        'expires_at' => 'datetime'
    ];

    public function scopeActive($query)
    {
        return $query->where('is_dismissed', false)
                    ->where(function($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function getLevelColorAttribute()
    {
        $colors = [
            'info' => 'info',
            'warning' => 'warning',
            'error' => 'danger',
            'critical' => 'dark'
        ];

        return $colors[$this->level] ?? 'secondary';
    }
}
