<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Temperature>
 */
class TemperatureFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // $timestamp = fake()->dateTimeBetween('-1 month', 'now');

        // return [
        //     'machine_id' => 1, // Sesuaikan range ID mesin Anda
        //     'temperature_value' => fake()->numberBetween(20, 100), // Contoh suhu 20-100 derajat
        //     'timestamp' => $timestamp,
        //     'reading_date' => Carbon::parse($timestamp)->format('Y-m-d'),
        //     'reading_time' => Carbon::parse($timestamp)->format('H:i:s'),
        //     'is_validated' => fake()->boolean(),
        //     'validation_status' => fake()->randomElement(['pending']),
        //     'validation_notes' => fake()->optional()->sentence(),
        //     'created_at' => Carbon::now(),
        //     'updated_at' => Carbon::now(),
        // ];

        $timestamp = now();
        return [
            'machine_id' => \App\Models\Machine::factory(),
            'temperature_value' => fake()->numberBetween(20, 40),
            'timestamp' => $timestamp,
            'reading_date' => $timestamp->format('Y-m-d'),
            'reading_time' => $timestamp->format('H:i:s'),
            'is_validated' => 1,
            'validation_status' => 'pending',

            // Ubah dari string biasa ke format JSON
            'validation_notes' => json_encode(['note' => 'Auto-generated']),

            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ];

        
    }
}
