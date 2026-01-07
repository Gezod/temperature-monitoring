<?php
// tests/Unit/SimpleAnomalyTest.php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class SimpleAnomalyTest extends TestCase
{
    /** @test */
    public function test_normal_temperature()
    {
        $temperature = 25;
        $minNormal = 20;
        $maxNormal = 30;
        $minCritical = 15;
        $maxCritical = 35;

        $status = $this->getTemperatureStatus($temperature, $minNormal, $maxNormal, $minCritical, $maxCritical);

        $this->assertEquals('normal', $status);
    }

    /** @test */
    public function test_warning_temperature()
    {
        $temperature = 32;
        $minNormal = 20;
        $maxNormal = 30;
        $minCritical = 15;
        $maxCritical = 35;

        $status = $this->getTemperatureStatus($temperature, $minNormal, $maxNormal, $minCritical, $maxCritical);

        $this->assertEquals('warning', $status);
    }

    /** @test */
    public function test_critical_temperature_high()
    {
        $temperature = 40;
        $minNormal = 20;
        $maxNormal = 30;
        $minCritical = 15;
        $maxCritical = 35;

        $status = $this->getTemperatureStatus($temperature, $minNormal, $maxNormal, $minCritical, $maxCritical);

        $this->assertEquals('critical', $status);
    }

    /** @test */
    public function test_critical_temperature_low()
    {
        $temperature = 10;
        $minNormal = 20;
        $maxNormal = 30;
        $minCritical = 15;
        $maxCritical = 35;

        $status = $this->getTemperatureStatus($temperature, $minNormal, $maxNormal, $minCritical, $maxCritical);

        $this->assertEquals('critical', $status);
    }

    /** @test */
    public function test_basic_anomaly_detection()
    {
        $readings = [25, 26, 24, 25, 100]; // 100 adalah anomali
        $anomalies = $this->detectAnomalies($readings);

        $this->assertCount(1, $anomalies);
        $this->assertEquals(100, $anomalies[0]);
    }

    /** @test */
    public function test_no_anomalies_in_normal_data()
    {
        $readings = [25, 26, 24, 25, 26];
        $anomalies = $this->detectAnomalies($readings);

        $this->assertCount(0, $anomalies);
    }

    /** @test */
    public function test_validate_temperature_data()
    {
        // Data valid
        $validData = ['temperature' => 25.5, 'machine_id' => 1];
        $this->assertTrue($this->validateTemperatureData($validData));

        // Data tidak valid
        $invalidData = ['temperature' => 'invalid', 'machine_id' => null];
        $this->assertFalse($this->validateTemperatureData($invalidData));
    }

    /**
     * Helper function untuk menentukan status temperature
     */
    private function getTemperatureStatus($temp, $minNormal, $maxNormal, $minCritical, $maxCritical): string
    {
        if ($temp < $minCritical || $temp > $maxCritical) {
            return 'critical';
        }

        if ($temp < $minNormal || $temp > $maxNormal) {
            return 'warning';
        }

        return 'normal';
    }

    /**
     * Deteksi anomali sederhana (nilai > 50 dianggap anomali)
     */
    private function detectAnomalies(array $readings): array
    {
        $anomalies = [];

        foreach ($readings as $reading) {
            if ($reading > 50) {
                $anomalies[] = $reading;
            }
        }

        return $anomalies;
    }

    /**
     * Validasi data temperature sederhana
     */
    private function validateTemperatureData(array $data): bool
    {
        if (!isset($data['temperature']) || !is_numeric($data['temperature'])) {
            return false;
        }

        if (!isset($data['machine_id']) || empty($data['machine_id'])) {
            return false;
        }

        if ($data['temperature'] < -273.15) { // Absolute zero
            return false;
        }

        return true;
    }
}
