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
        'timestamp'
    ];

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }
}
