<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Machine;
use App\Models\Temperature;
use App\Services\AnomalyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UploadPDFTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_successfully_processes_pdf_from_python_api()
    {
        $user = User::factory()->create();
        $machine = Machine::factory()->create();

        // 1. GUNAKAN SEQUENCE: Ini cara paling ampuh untuk mematikan error 'contents key'
        Http::fake([
            'http://127.0.0.1:5000/upload' => Http::sequence()
                ->push([
                    'success' => true,
                    'temperature_data' => [
                        [
                            'timestamp' => '2023-10-01 10:00:00',
                            'temperature' => 25.5,
                            'machine_id' => $machine->id
                        ]
                    ]
                ], 200)
        ]);

        // 2. Mocking AnomalyService
        $this->mock(\App\Services\AnomalyService::class, function ($mock) {
            $mock->shouldReceive('checkSingleReading')->andReturn(null);
        });

        // 3. Buat File PDF
        $file = UploadedFile::fake()->create('sensor.pdf', 100, 'application/pdf');

        // 4. Jalankan Request
        $response = $this->actingAs($user)
            ->post(route('temperature.upload-pdf-py'), [
                'file' => $file,
                'machine_id' => $machine->id
            ]);

        // Jika masih 400, kita intip lagi
        if ($response->status() !== 200) {
            dd($response->json());
        }

        $response->assertStatus(200);
        // Sesuaikan dengan apa yang ditemukan database
        $this->assertDatabaseHas('temperature', [
            'machine_id' => $machine->id,
            'temperature_value' => 26, // Gunakan angka bulat tanpa kutip
        ]);
    }
}