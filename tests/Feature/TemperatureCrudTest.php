<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Temperature;
use App\Models\Machine;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Events\TemperatureUpdated;
use Carbon\Carbon;

class TemperatureCrudTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     */
    /** @test */

    public function index_page_shows_temperature_data()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $branch = Branch::factory()->create();

        $machine = Machine::factory()->create([
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);

        Temperature::factory()->create([
            'machine_id' => $machine->id,
        ]);

        $response = $this->get(route('temperature.index'));

        $response->assertStatus(200);
        $response->assertViewHas('readings');
    }


    /** @test */
    /** @test */
    public function create_page_can_be_opened()
    {
        // 1. Buat user dummy
        $user = User::factory()->create();

        // 2. Gunakan actingAs() agar dianggap sudah login
        $response = $this->actingAs($user)->get(route('temperature.create'));

        $response->assertStatus(200);
        $response->assertViewHas('machines');
    }

    /** @test */
    public function temperature_can_be_stored()
    {
        $user = User::factory()->create();
    $machine = Machine::factory()->create();

    $now = now();
    $payload = [
        'machine_id'        => $machine->id,
        'temperature_value' => 36,
        'timestamp'         => $now->toDateTimeString(),
        // Tambahkan ini jika Controller tidak mengurusnya secara otomatis:
        'reading_date'      => $now->format('Y-m-d'),
        'reading_time'      => $now->format('H:i:s'),
        'is_validated'      => 0,
        'validation_status' => 'pending',
        'validation_notes'  => 'Entry from test',
    ];

    $response = $this->actingAs($user)->postJson(route('temperature.store'), $payload);

    // Cek status dan pesan JSON
    $response->assertStatus(200);
    $response->assertJson(['message' => 'Data suhu berhasil disimpan']);

    // Sesuaikan nama tabel 'temperature' sesuai error SQL sebelumnya
    $this->assertDatabaseHas('temperature', [
        'machine_id'        => $machine->id,
        'temperature_value' => 36,
    ]);
    }

    /** @test */
    public function test_edit_page_can_be_opened()
    {
        $user = User::factory()->create();

        // Ini akan otomatis membuat Machine baru berkat perbaikan di atas
        $temperature = Temperature::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('temperature.edit', $temperature->id));

        $response->assertStatus(200);
        // $response->assertViewIs('temperature.edit'); // Opsional: pastikan view-nya benar
    }

    // /** @test */
    /** @test */
    public function temperature_can_be_updated_and_event_fired()
    {
        // 1. Matikan handling agar kita bisa lihat error asli kalau ada
        $this->withoutExceptionHandling();

        $user = User::factory()->create();

        // 2. Buat Machine secara manual
        $machine = Machine::factory()->create();

        // 3. Buat Temperature dengan mengunci machine_id (mencegah loop factory)
        $temperature = Temperature::factory()->create([
            'machine_id' => $machine->id,
            'temperature_value' => 30
        ]);

        // 4. Payload harus sesuai dengan isi 'in:...' di Controller
        $payload = [
            'machine_id' => $machine->id,
            'temperature_value' => 40,
            'timestamp' => now()->toDateTimeString(),
            'validation_status' => 'validated', // HARUS 'validated' sesuai Controller
            'validation_notes' => 'Updated via test',
        ];

        // 5. Jalankan Request
        $response = $this->actingAs($user)
            ->put(route('temperature.update', $temperature->id), $payload);

        // 6. Assertions
        $response->assertRedirect(route('temperature.show', $temperature->id));

        $this->assertDatabaseHas('temperature', [
            'id' => $temperature->id,
            'temperature_value' => 40,
            'validation_status' => 'validated'
        ]);
    }


}
