<?php

namespace App\Services;

use App\Models\TemperatureReading;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;

class DataImportService
{
    public function importTemperatureData($temperatureData, $machineId, $readingType = 'imported', $sourceFile = null)
    {
        $importedCount = 0;

        DB::beginTransaction();

        try {
            foreach ($temperatureData as $data) {
                // Check if reading already exists
                $existingReading = TemperatureReading::where('machine_id', $machineId)
                    ->where('recorded_at', $data['recorded_at'])
                    ->first();

                if (!$existingReading) {
                    TemperatureReading::create([
                        'machine_id' => $machineId,
                        'recorded_at' => $data['recorded_at'],
                        'temperature' => $data['temperature'],
                        'reading_type' => $readingType,
                        'source_file' => $sourceFile,
                        'metadata' => isset($data['metadata']) ? $data['metadata'] : null
                    ]);

                    $importedCount++;
                }
            }

            DB::commit();

            Log::info("Imported {$importedCount} temperature readings for machine {$machineId}");

            return $importedCount;

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Data import error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function processExcelFile($filePath)
    {
        try {
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            $temperatureData = [];

            // Skip header row and process data
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];

                if (count($row) >= 2) {
                    try {
                        // Assume first column is datetime, second is temperature
                        $dateTime = $this->parseDateTime($row[0]);
                        $temperature = floatval($row[1]);

                        if ($dateTime && is_numeric($temperature)) {
                            $temperatureData[] = [
                                'recorded_at' => $dateTime,
                                'temperature' => $temperature
                            ];
                        }

                    } catch (\Exception $e) {
                        Log::warning("Failed to parse row {$i}: " . json_encode($row));
                        continue;
                    }
                }
            }

            return $temperatureData;

        } catch (\Exception $e) {
            Log::error('Excel processing error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function processCsvFile($filePath)
    {
        try {
            $temperatureData = [];

            if (($handle = fopen($filePath, 'r')) !== FALSE) {
                $header = fgetcsv($handle); // Skip header

                while (($data = fgetcsv($handle)) !== FALSE) {
                    if (count($data) >= 2) {
                        try {
                            $dateTime = $this->parseDateTime($data[0]);
                            $temperature = floatval($data[1]);

                            if ($dateTime && is_numeric($temperature)) {
                                $temperatureData[] = [
                                    'recorded_at' => $dateTime,
                                    'temperature' => $temperature
                                ];
                            }

                        } catch (\Exception $e) {
                            Log::warning("Failed to parse CSV row: " . json_encode($data));
                            continue;
                        }
                    }
                }

                fclose($handle);
            }

            return $temperatureData;

        } catch (\Exception $e) {
            Log::error('CSV processing error: ' . $e->getMessage());
            throw $e;
        }
    }

    private function parseDateTime($dateString)
    {
        if (is_numeric($dateString)) {
            // Handle Excel timestamp
            return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateString));
        }

        // Try various date formats
        $formats = [
            'Y-m-d H:i:s',
            'd/m/Y H:i:s',
            'm/d/Y H:i:s',
            'Y-m-d H:i',
            'd/m/Y H:i',
            'm/d/Y H:i',
            'Y-m-d',
            'd/m/Y',
            'm/d/Y'
        ];

        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $dateString);
            } catch (\Exception $e) {
                continue;
            }
        }

        // Try general parsing
        try {
            return Carbon::parse($dateString);
        } catch (\Exception $e) {
            Log::warning("Cannot parse date: {$dateString}");
            return null;
        }
    }

    public function exportTemperatureData($machineIds = [], $dateFrom = null, $dateTo = null, $format = 'excel')
    {
        $query = TemperatureReading::with(['machine.branch'])
            ->orderBy('recorded_at');

        if (!empty($machineIds)) {
            $query->whereIn('machine_id', $machineIds);
        }

        if ($dateFrom) {
            $query->where('recorded_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('recorded_at', '<=', $dateTo . ' 23:59:59');
        }

        $readings = $query->get();

        if ($format === 'csv') {
            return $this->generateCsvExport($readings);
        } else {
            return $this->generateExcelExport($readings);
        }
    }

    private function generateExcelExport($readings)
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        // Set headers
        $worksheet->setCellValue('A1', 'Date/Time');
        $worksheet->setCellValue('B1', 'Branch');
        $worksheet->setCellValue('C1', 'Machine');
        $worksheet->setCellValue('D1', 'Temperature (°C)');
        $worksheet->setCellValue('E1', 'Status');
        $worksheet->setCellValue('F1', 'Reading Type');
        $worksheet->setCellValue('G1', 'Source File');

        // Style headers
        $headerStyle = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF4CAF50']
            ]
        ];
        $worksheet->getStyle('A1:G1')->applyFromArray($headerStyle);

        // Add data
        $row = 2;
        foreach ($readings as $reading) {
            $worksheet->setCellValue('A' . $row, $reading->recorded_at->format('Y-m-d H:i:s'));
            $worksheet->setCellValue('B' . $row, $reading->machine->branch->name);
            $worksheet->setCellValue('C' . $row, $reading->machine->name);
            $worksheet->setCellValue('D' . $row, $reading->temperature);
            $worksheet->setCellValue('E' . $row, $reading->status);
            $worksheet->setCellValue('F' . $row, $reading->reading_type);
            $worksheet->setCellValue('G' . $row, $reading->source_file);
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'G') as $column) {
            $worksheet->getColumnDimension($column)->setAutoSize(true);
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');

        $filename = 'temperature_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'temp_export');
        $writer->save($tempFile);

        return [
            'path' => $tempFile,
            'filename' => $filename,
            'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ];
    }

    private function generateCsvExport($readings)
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'temp_csv_export');
        $handle = fopen($tempFile, 'w');

        // Headers
        fputcsv($handle, [
            'Date/Time',
            'Branch',
            'Machine',
            'Temperature (°C)',
            'Status',
            'Reading Type',
            'Source File'
        ]);

        // Data
        foreach ($readings as $reading) {
            fputcsv($handle, [
                $reading->recorded_at->format('Y-m-d H:i:s'),
                $reading->machine->branch->name,
                $reading->machine->name,
                $reading->temperature,
                $reading->status,
                $reading->reading_type,
                $reading->source_file
            ]);
        }

        fclose($handle);

        $filename = 'temperature_export_' . now()->format('Y-m-d_H-i-s') . '.csv';

        return [
            'path' => $tempFile,
            'filename' => $filename,
            'mime' => 'text/csv'
        ];
    }
}
