<?php

namespace Database\Factories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

class BranchFactory extends Factory
{
    protected $model = Branch::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company,
            'code' => strtoupper($this->faker->unique()->bothify('BR-###')),
            'address' => $this->faker->address,
            'city' => $this->faker->city,
            'region' => $this->faker->state,
            'contact_info' => $this->faker->phoneNumber,
            'is_active' => 1,
        ];
    }
}
