<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\TemperatureReading;
use App\Models\Machine;

class PdfProcessingService
{
    public function extractTemperatureData($pdfPath)
    {
        try {
            // Use pdf2txt or similar tool to extract text from PDF
            $text = $this->extractTextFromPdf($pdfPath);

            // Parse the extracted text to find temperature data
            return $this->parseTemperatureData($text);
        } catch (\Exception $e) {
            Log::error('PDF processing error: ' . $e->getMessage());
            throw $e;
        }
    }

    private function extractTextFromPdf($pdfPath)
    {
        // For demo purposes, we'll simulate PDF text extraction
        // In production, use libraries like Smalot\PdfParser or shell commands like pdftotext

        // Simulated extraction based on your PDF sample
        $sampleData = [
            '2025-08-09 00:27:50 -11,2',
            '2025-08-09 01:27:50 -11,6',
            '2025-08-09 02:27:50 -11,8',
            '2025-08-09 03:27:50 -6,3',
            '2025-08-09 04:27:50 -9,2',
            '2025-08-09 05:27:50 -10,8',
            '2025-08-09 06:27:50 -11,5',
            '2025-08-09 07:27:50 -11,9',
            '2025-08-09 08:27:50 -12,1',
            '2025-08-09 09:27:50 -11,8',
            '2025-08-09 10:27:50 -6,5',
            '2025-08-09 11:27:50 -8,8',
            '2025-08-09 12:27:50 -10,4',
            '2025-08-09 13:27:50 -10,8',
            '2025-08-09 14:27:50 -11,1',
            '2025-08-09 15:27:50 -11,3',
            '2025-08-09 16:27:50 -11,5',
            '2025-08-09 17:27:50 -7,5',
            '2025-08-09 18:27:50 -8,2',
            '2025-08-09 19:27:50 -10,2',
            '2025-08-09 20:27:50 -11,1',
            '2025-08-09 21:27:50 -11,6',
            '2025-08-09 22:27:50 -12,0',
            '2025-08-09 23:27:50 -12,2',
        ];

        return implode("\n", $sampleData);
    }

    private function parseTemperatureData($text)
    {
        $temperatureData = [];
        $lines = explode("\n", $text);

        foreach ($lines as $line) {
            $line = trim($line);

            // Pattern to match: YYYY-MM-DD HH:MM:SS TEMPERATURE
            if (preg_match('/(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})\s+([-+]?\d+[,.]?\d*)/', $line, $matches)) {
                try {
                    $dateTime = Carbon::createFromFormat('Y-m-d H:i:s', $matches[1]);
                    $temperature = floatval(str_replace(',', '.', $matches[2])); // Handle comma decimal separator

                    $temperatureData[] = [
                        'recorded_at' => $dateTime,
                        'temperature' => $temperature
                    ];
                } catch (\Exception $e) {
                    Log::warning('Failed to parse line: ' . $line);
                    continue;
                }
            }
        }

        return $temperatureData;
    }

    public function generateTemperatureReport($filters = [])
    {
        $query = TemperatureReading::with(['machine.branch']);

        // Apply filters
        if (isset($filters['machine_id']) && $filters['machine_id']) {
            $query->where('machine_id', $filters['machine_id']);
        }

        if (isset($filters['date_from']) && $filters['date_from']) {
            $query->where('recorded_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to']) && $filters['date_to']) {
            $query->where('recorded_at', '<=', $filters['date_to'] . ' 23:59:59');
        }

        $readings = $query->orderBy('recorded_at')->get();

        // Calculate statistics
        $stats = [
            'total_readings' => $readings->count(),
            'avg_temperature' => $readings->avg('temperature'),
            'min_temperature' => $readings->min('temperature'),
            'max_temperature' => $readings->max('temperature'),
            'anomaly_count' => $readings->where('is_anomaly', true)->count(),
        ];

        // Group by machine for detailed analysis
        $machineData = $readings->groupBy('machine_id')->map(function ($machineReadings) {
            $machine = $machineReadings->first()->machine;
            return [
                'machine' => $machine,
                'readings' => $machineReadings,
                'stats' => [
                    'count' => $machineReadings->count(),
                    'avg' => $machineReadings->avg('temperature'),
                    'min' => $machineReadings->min('temperature'),
                    'max' => $machineReadings->max('temperature'),
                ]
            ];
        });

        $pdf = PDF::loadView('reports.temperature-report', compact('readings', 'stats', 'machineData', 'filters'));

        return $pdf;
    }
    public function generateBranchReport($data)
    {
        // $data akan berisi: branch, machines, recent_readings, anomalies, maintenance
        $pdf = Pdf::loadView('reports.branch-report', $data)
            ->setPaper('a4', 'landscape'); // opsional: bisa portrait atau landscape

        return $pdf;
    }

    public function generateAnomalyReport($filters = [])
    {
        // Implementation for anomaly report generation
        $anomalies = \App\Models\Anomaly::with(['machine.branch', 'temperatureReading'])
            ->orderBy('detected_at', 'desc');

        if (isset($filters['severity'])) {
            $anomalies->where('severity', $filters['severity']);
        }

        if (isset($filters['date_from'])) {
            $anomalies->where('detected_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $anomalies->where('detected_at', '<=', $filters['date_to'] . ' 23:59:59');
        }

        $anomalies = $anomalies->get();

        $pdf = PDF::loadView('reports.anomaly-report', compact('anomalies', 'filters'));

        return $pdf;
    }

    public function generateMaintenanceReport($filters = [])
    {
        // Implementation for maintenance report generation
        $recommendations = \App\Models\MaintenanceRecommendation::with(['machine.branch'])
            ->orderBy('recommended_date');

        if (isset($filters['status'])) {
            $recommendations->where('status', $filters['status']);
        }

        if (isset($filters['priority'])) {
            $recommendations->where('priority', $filters['priority']);
        }

        $recommendations = $recommendations->get();

        $pdf = PDF::loadView('reports.maintenance-report', compact('recommendations', 'filters'));

        return $pdf;
    }
}
