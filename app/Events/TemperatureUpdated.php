<?php

namespace App\Events;

use App\Models\Temperature;
use Illuminate\Foundation\Events\Dispatchable;

class TemperatureUpdated
{
    use Dispatchable;

    public $temperature;
    public $oldData; // Data sebelum update

    public function __construct(Temperature $temperature, array $oldData = [])
    {
        $this->temperature = $temperature;
        $this->oldData = $oldData;
    }
}
