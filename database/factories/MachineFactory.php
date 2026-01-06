<?php

namespace Database\Factories;

use App\Models\Machine;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

class MachineFactory extends Factory
{
    protected $model = Machine::class;

    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'name' => 'Freezer Sensor ' . $this->faker->unique()->numberBetween(1, 100),
            'type' => 'freezer',

            // nullable fields
            'model' => null,
            'serial_number' => null,
            'installation_date' => null,
            'specifications' => null,

            // normal & critical ranges (sesuai default DB)
            'temp_min_normal' => -20.00,
            'temp_max_normal' => 5.00,
            'temp_critical_min' => -25.00,
            'temp_critical_max' => 10.00,

            'is_active' => true,
        ];
    }
}
