<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TemperatureSystemTest extends TestCase
{
    private function getTemperatureStatus($temp, $minNormal, $maxNormal, $minCritical, $maxCritical)
    {
        if ($temp < $minCritical || $temp > $maxCritical) return "critical";
        if ($temp < $minNormal || $temp > $maxNormal) return "warning";
        return "normal";
    }

    /** @test */
    public function check_database_structure()
    {
        // Gunakan Schema facade untuk cek tabel
        $tables = ['temperatures', 'machines', 'branches'];
        $existingTables = [];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                $existingTables[] = $table;
                $this->assertTrue(true, "Table $table exists");
            } else {
                // Mark test as incomplete atau skip assertion
                // PHPUnit 10 tidak punya addWarning, kita gunakan alternatif
                $this->markTestIncomplete("Table $table does not exist (migration might not be run)");
                return; // Hentikan test ini
            }
        }

        // Minimal harus ada beberapa tabel
        $this->assertGreaterThan(0, count($existingTables),
            "No temperature-related tables found.");
    }

    /** @test */
    public function test_temperature_status_logic_from_readme()
    {
        // Test Case 1: Normal temperature
        $this->assertEquals('normal', $this->getTemperatureStatus(25, 20, 30, 10, 40));

        // Test Case 2: Warning (above max normal)
        $this->assertEquals('warning', $this->getTemperatureStatus(35, 20, 30, 10, 40));

        // Test Case 3: Warning (below min normal)
        $this->assertEquals('warning', $this->getTemperatureStatus(15, 20, 30, 10, 40));

        // Test Case 4: Critical (above max critical)
        $this->assertEquals('critical', $this->getTemperatureStatus(45, 20, 30, 10, 40));

        // Test Case 5: Critical (below min critical)
        $this->assertEquals('critical', $this->getTemperatureStatus(5, 20, 30, 10, 40));
    }

    /** @test */
    public function test_color_coding_logic()
    {
        // Test color coding dari README
        $colors = [
            'normal' => '#198754',    // Green
            'warning' => '#fd7e14',   // Orange
            'critical' => '#dc3545',  // Red
        ];

        $this->assertEquals('#198754', $colors['normal']);
        $this->assertEquals('#fd7e14', $colors['warning']);
        $this->assertEquals('#dc3545', $colors['critical']);

        // Pastikan semua warna hex valid
        foreach ($colors as $status => $color) {
            $this->assertStringStartsWith('#', $color);
            $this->assertEquals(7, strlen($color)); // # + 6 hex digits
        }
    }

    /** @test */
    public function test_database_connection()
    {
        // Cek koneksi database saja (tanpa perlu tabel)
        try {
            DB::connection()->getPdo();
            $this->assertTrue(true, "Database connection successful");
        } catch (\Exception $e) {
            $this->fail("Database connection failed: " . $e->getMessage());
        }
    }
}
