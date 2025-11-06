<?php

namespace App\Http\Controllers;

use App\Models\TemperatureValidation;
use App\Models\Machine;
use App\Models\Temperature;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TemperatureValidationController extends Controller
{
    public function uploadWithValidation(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:10240',
            'branch_id' => 'required|exists:branches,id',
            'machine_name' => 'required|string|max:255',
            'machine_type' => 'nullable|string|max:100',
        ]);

        try {
            DB::beginTransaction();

            // Find or create machine
            $machine = Machine::where('branch_id', $request->branch_id)
                ->where('name', $request->machine_name)
                ->first();

            if (!$machine) {
                $machine = Machine::create([
                    'branch_id' => $request->branch_id,
                    'name' => $request->machine_name,
                    'type' => $request->machine_type ?? 'Unknown',
                    'temp_min_normal' => -20,
                    'temp_max_normal' => 5,
                    'temp_critical_min' => -25,
                    'temp_critical_max' => 10,
                    'is_active' => true
                ]);
            }

            // Upload to Python API
            $file = $request->file('file');
            $response = Http::attach(
                'file',
                file_get_contents($file->getRealPath()),
                $file->getClientOriginalName()
            )->post('http://127.0.0.1:5000/upload', [
                'machine_id' => $machine->id
            ]);

            if ($response->failed()) {
                throw new \Exception('Failed to process PDF file');
            }

            $data = $response->json();
            $sessionId = Str::uuid();

            // Validate data and detect errors
            $validationErrors = $this->validateTemperatureData($data['temperature_data'], $machine);

            // Store validation record
            $validation = TemperatureValidation::create([
                'machine_id' => $machine->id,
                'upload_session_id' => $sessionId,
                'raw_data' => $data['temperature_data'],
                'validation_errors' => $validationErrors,
                'is_validated' => false,
                'validation_status' => count($validationErrors) > 0 ? 'needs_review' : 'pending',
                'uploaded_at' => now()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'File uploaded and validated successfully',
                'data' => [
                    'session_id' => $sessionId,
                    'machine' => $machine,
                    'total_records' => count($data['temperature_data']),
                    'validation_errors' => count($validationErrors),
                    'validation_status' => $validation->validation_status
                ],
                'redirect_url' => route('temperature.validation.review', $sessionId)
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error processing file: ' . $e->getMessage()
            ], 400);
        }
    }

    public function reviewValidation($sessionId)
    {
        $validation = TemperatureValidation::with('machine.branch')
            ->where('upload_session_id', $sessionId)
            ->firstOrFail();

        // Group data by date for better visualization
        $groupedData = collect($validation->raw_data)->groupBy(function ($item) {
            return Carbon::parse($item['timestamp'])->format('Y-m-d');
        });

        return view('temperature.validation.review', compact('validation', 'groupedData'));
    }

    public function updateValidationData(Request $request, $sessionId)
    {
        $validation = TemperatureValidation::where('upload_session_id', $sessionId)
            ->firstOrFail();

        $request->validate([
            'corrections' => 'required|array',
            'corrections.*.index' => 'required|integer',
            'corrections.*.temperature' => 'required|numeric',
            'corrections.*.timestamp' => 'required|date'
        ]);

        $rawData = $validation->raw_data;

        // Apply corrections
        foreach ($request->corrections as $correction) {
            $index = $correction['index'];
            if (isset($rawData[$index])) {
                $rawData[$index]['temperature'] = $correction['temperature'];
                $rawData[$index]['timestamp'] = $correction['timestamp'];
                $rawData[$index]['corrected'] = true;
            }
        }

        // Re-validate data
        $validationErrors = $this->validateTemperatureData($rawData, $validation->machine);

        $validation->update([
            'raw_data' => $rawData,
            'validation_errors' => $validationErrors,
            'validation_status' => count($validationErrors) > 0 ? 'needs_review' : 'pending'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data updated successfully',
            'validation_errors' => count($validationErrors)
        ]);
    }

    public function importValidatedData($sessionId)
    {
        $validation = TemperatureValidation::where('upload_session_id', $sessionId)
            ->whereIn('validation_status', ['pending', 'ready'])
            ->firstOrFail();

        try {
            DB::beginTransaction();

            $importedCount = 0;
            foreach ($validation->raw_data as $item) {
                $timestamp = Carbon::parse($item['timestamp']);

                Temperature::updateOrCreate([
                    'machine_id' => $validation->machine_id,
                    'timestamp' => $timestamp
                ], [
                    'temperature_value' => $item['temperature'],
                    'reading_date' => $timestamp->format('Y-m-d'),
                    'reading_time' => $timestamp->format('H:i:s'),
                    'validation_status' => 'imported'
                ]);

                $importedCount++;
            }

            $validation->update([
                'is_imported' => true,
                'validation_status' => 'imported'
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully imported {$importedCount} temperature readings",
                'imported_count' => $importedCount
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error importing data: ' . $e->getMessage()
            ], 500);
        }
    }

    private function validateTemperatureData($data, $machine)
    {
        $errors = [];
        $previousTemp = null;
        $previousTime = null;

        foreach ($data as $index => $item) {
            $temperature = $item['temperature'] ?? null;
            $timestamp = $item['timestamp'] ?? null;

            // Basic validation
            if ($temperature === null) {
                $errors[] = [
                    'index' => $index,
                    'type' => 'missing_temperature',
                    'message' => 'Temperature value is missing'
                ];
                continue;
            }

            if ($timestamp === null) {
                $errors[] = [
                    'index' => $index,
                    'type' => 'missing_timestamp',
                    'message' => 'Timestamp is missing'
                ];
                continue;
            }

            try {
                $time = Carbon::parse($timestamp);
            } catch (\Exception $e) {
                $errors[] = [
                    'index' => $index,
                    'type' => 'invalid_timestamp',
                    'message' => 'Invalid timestamp format'
                ];
                continue;
            }

            // Temperature range validation
            if ($temperature < -50 || $temperature > 50) {
                $errors[] = [
                    'index' => $index,
                    'type' => 'temperature_out_of_range',
                    'message' => "Temperature {$temperature}°C is outside reasonable range (-50°C to 50°C)"
                ];
            }

            // Rapid change detection
            if ($previousTemp !== null && $previousTime !== null) {
                $tempDiff = abs($temperature - $previousTemp);
                $timeDiff = $time->diffInMinutes($previousTime);

                if ($timeDiff > 0 && $tempDiff / $timeDiff > 5) { // More than 5°C per minute
                    $errors[] = [
                        'index' => $index,
                        'type' => 'rapid_temperature_change',
                        'message' => "Rapid temperature change detected: {$tempDiff}°C in {$timeDiff} minutes"
                    ];
                }
            }

            // Duplicate timestamp check
            if ($previousTime !== null && $time->equalTo($previousTime)) {
                $errors[] = [
                    'index' => $index,
                    'type' => 'duplicate_timestamp',
                    'message' => 'Duplicate timestamp detected'
                ];
            }

            $previousTemp = $temperature;
            $previousTime = $time;
        }

        return $errors;
    }

    public function getValidationHistory(Request $request)
    {
        $query = TemperatureValidation::with('machine.branch')
            ->orderBy('uploaded_at', 'desc');

        if ($request->has('validation_status') && $request->validation_status) {
            $query->where('validation_status', $request->validation_status);
        }

        if ($request->has('machine_id') && $request->machine_id) {
            $query->where('machine_id', $request->machine_id);
        }

        $validations = $query->paginate(20);
        $machines = Machine::with('branch')->get();

        return view('temperature.validation.history', compact('validations', 'machines'));
    }
}
