<?php

namespace App\Http\Controllers;

use App\Models\Machine;
use App\Models\TemperatureReading;
use App\Models\MonthlySummary;
use App\Services\PdfProcessingService;
use App\Services\DataImportService;
use App\Services\AnomalyDetectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class TemperatureController extends Controller
{
    protected $pdfService;
    protected $importService;
    protected $anomalyService;

    public function __construct(
        PdfProcessingService $pdfService,
        DataImportService $importService,
        AnomalyDetectionService $anomalyService
    ) {
        $this->pdfService = $pdfService;
        $this->importService = $importService;
        $this->anomalyService = $anomalyService;
    }

    public function index(Request $request)
    {
        $query = TemperatureReading::with(['machine.branch'])
            ->orderBy('recorded_at', 'desc');

        if ($request->has('machine_id') && $request->machine_id) {
            $query->where('machine_id', $request->machine_id);
        }

        if ($request->has('date_from') && $request->date_from) {
            $query->where('recorded_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->where('recorded_at', '<=', $request->date_to . ' 23:59:59');
        }

        $readings = $query->paginate(50);
        $machines = Machine::with('branch')->where('is_active', true)->get();

        return view('layouts.temperature.index', compact('readings', 'machines'));
    }

    public function create()
    {
        $machines = Machine::with('branch')->where('is_active', true)->get();
        return view('layouts.temperature.create', compact('machines'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'machine_id' => 'required|exists:machines,id',
            'recorded_at' => 'required|date',
            'temperature' => 'required|numeric',
            'reading_type' => 'required|in:automatic,manual,imported'
        ]);

        $reading = TemperatureReading::create([
            'machine_id' => $request->machine_id,
            'recorded_at' => $request->recorded_at,
            'temperature' => $request->temperature,
            'reading_type' => $request->reading_type,
            'metadata' => $request->metadata ? json_decode($request->metadata, true) : null
        ]);

        // Check for anomalies
        $this->anomalyService->checkSingleReading($reading);

        // Update monthly summary
        $this->updateMonthlySummary($reading);

        return redirect()->route('temperature.index')->with('success', 'Temperature reading added successfully.');
    }

    public function uploadPdf(Request $request)
    {
        $request->validate([
            'pdf_file' => 'required|file|mimes:pdf|max:10240',
            'machine_id' => 'required|exists:machines,id'
        ]);

        try {
            DB::beginTransaction();

            // Store the PDF file
            $path = $request->file('pdf_file')->store('pdf_uploads', 'public');

            // Process PDF and extract temperature data
            $temperatureData = $this->pdfService->extractTemperatureData(storage_path('app/public/' . $path));

            if (empty($temperatureData)) {
                throw new \Exception('No temperature data found in the PDF file.');
            }

            // Import the data
            $importedCount = $this->importService->importTemperatureData(
                $temperatureData,
                $request->machine_id,
                'imported',
                basename($path)
            );

            // Run anomaly detection on imported data
            $machine = Machine::findOrFail($request->machine_id);
            $this->anomalyService->checkMachineAnomalies($machine, Carbon::now()->subDays(7));

            // Update monthly summaries
            $this->updateMonthlySummariesForMachine($machine);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully imported {$importedCount} temperature readings.",
                'data' => [
                    'imported_count' => $importedCount,
                    'file_name' => basename($path),
                    'machine_id' => $request->machine_id
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            // Clean up uploaded file if exists
            if (isset($path)) {
                Storage::disk('public')->delete($path);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error processing PDF: ' . $e->getMessage()
            ], 400);
        }
    }

    public function uploadExcel(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'machine_id' => 'required|exists:machines,id'
        ]);

        try {
            DB::beginTransaction();

            // Store the Excel file
            $path = $request->file('excel_file')->store('excel_uploads', 'public');

            // Process Excel and extract temperature data
            $temperatureData = $this->importService->processExcelFile(storage_path('app/public/' . $path));

            if (empty($temperatureData)) {
                throw new \Exception('No temperature data found in the Excel file.');
            }

            // Import the data
            $importedCount = $this->importService->importTemperatureData(
                $temperatureData,
                $request->machine_id,
                'imported',
                basename($path)
            );

            // Run anomaly detection on imported data
            $machine = Machine::findOrFail($request->machine_id);
            $this->anomalyService->checkMachineAnomalies($machine, Carbon::now()->subDays(7));

            // Update monthly summaries
            $this->updateMonthlySummariesForMachine($machine);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully imported {$importedCount} temperature readings.",
                'data' => [
                    'imported_count' => $importedCount,
                    'file_name' => basename($path),
                    'machine_id' => $request->machine_id
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            // Clean up uploaded file if exists
            if (isset($path)) {
                Storage::disk('public')->delete($path);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error processing Excel file: ' . $e->getMessage()
            ], 400);
        }
    }

    public function exportPdf(Request $request)
    {
        $filters = $request->only(['machine_id', 'date_from', 'date_to']);

        $pdf = $this->pdfService->generateTemperatureReport($filters);

        $filename = 'temperature_report_' . now()->format('Y-m-d_H-i-s') . '.pdf';

        return $pdf->download($filename);
    }

    public function show($id)
    {
        $reading = TemperatureReading::with(['machine.branch', 'anomalies'])
            ->findOrFail($id);

        // Get nearby readings for context
        $nearbyReadings = TemperatureReading::where('machine_id', $reading->machine_id)
            ->where('recorded_at', '>=', $reading->recorded_at->subHours(2))
            ->where('recorded_at', '<=', $reading->recorded_at->addHours(2))
            ->where('id', '!=', $reading->id)
            ->orderBy('recorded_at')
            ->get();

        return view('temperature.show', compact('reading', 'nearbyReadings'));
    }

    public function edit($id)
    {
        $reading = TemperatureReading::with(['machine.branch'])->findOrFail($id);

        // Only allow editing of manual readings
        if ($reading->reading_type !== 'manual') {
            return redirect()->route('temperature.show', $reading)
                ->with('error', 'Only manual readings can be edited.');
        }

        $machines = Machine::with('branch')->where('is_active', true)->get();

        return view('temperature.edit', compact('reading', 'machines'));
    }

    public function update(Request $request, $id)
    {
        $reading = TemperatureReading::findOrFail($id);

        // Only allow editing of manual readings
        if ($reading->reading_type !== 'manual') {
            return redirect()->route('temperature.show', $reading)
                ->with('error', 'Only manual readings can be edited.');
        }

        $request->validate([
            'machine_id' => 'required|exists:machines,id',
            'recorded_at' => 'required|date',
            'temperature' => 'required|numeric',
            'reading_type' => 'required|in:automatic,manual,imported'
        ]);

        $reading->update([
            'machine_id' => $request->machine_id,
            'recorded_at' => $request->recorded_at,
            'temperature' => $request->temperature,
            'reading_type' => $request->reading_type,
            'metadata' => $request->metadata ? json_decode($request->metadata, true) : null
        ]);

        // Re-check for anomalies
        $this->anomalyService->checkSingleReading($reading);

        // Update monthly summary
        $this->updateMonthlySummary($reading);

        return redirect()->route('temperature.show', $reading)
            ->with('success', 'Temperature reading updated successfully.');
    }

    public function destroy($id)
    {
        $reading = TemperatureReading::findOrFail($id);
        $machineId = $reading->machine_id;

        $reading->delete();

        // Update monthly summary after deletion
        $this->updateMonthlySummariesForMachine(Machine::find($machineId));

        return redirect()->route('temperature.index')
            ->with('success', 'Temperature reading deleted successfully.');
    }

    private function updateMonthlySummary($reading)
    {
        $year = $reading->recorded_at->year;
        $month = $reading->recorded_at->month;

        $summary = MonthlySummary::firstOrCreate([
            'machine_id' => $reading->machine_id,
            'year' => $year,
            'month' => $month
        ]);

        // Recalculate summary data
        $monthlyReadings = TemperatureReading::where('machine_id', $reading->machine_id)
            ->whereYear('recorded_at', $year)
            ->whereMonth('recorded_at', $month)
            ->get();

        if ($monthlyReadings->isNotEmpty()) {
            $summary->update([
                'temp_avg' => $monthlyReadings->avg('temperature'),
                'temp_min' => $monthlyReadings->min('temperature'),
                'temp_max' => $monthlyReadings->max('temperature'),
                'total_readings' => $monthlyReadings->count(),
                'anomaly_count' => $monthlyReadings->where('is_anomaly', true)->count(),
            ]);
        }
    }

    private function updateMonthlySummariesForMachine($machine)
    {
        // Get all months that have readings for this machine
        $monthlyData = TemperatureReading::where('machine_id', $machine->id)
            ->selectRaw('YEAR(recorded_at) as year, MONTH(recorded_at) as month')
            ->groupBy('year', 'month')
            ->get();

        foreach ($monthlyData as $data) {
            $summary = MonthlySummary::firstOrCreate([
                'machine_id' => $machine->id,
                'year' => $data->year,
                'month' => $data->month
            ]);

            // Recalculate summary data
            $monthlyReadings = TemperatureReading::where('machine_id', $machine->id)
                ->whereYear('recorded_at', $data->year)
                ->whereMonth('recorded_at', $data->month)
                ->get();

            if ($monthlyReadings->isNotEmpty()) {
                $summary->update([
                    'temp_avg' => $monthlyReadings->avg('temperature'),
                    'temp_min' => $monthlyReadings->min('temperature'),
                    'temp_max' => $monthlyReadings->max('temperature'),
                    'total_readings' => $monthlyReadings->count(),
                    'anomaly_count' => $monthlyReadings->where('is_anomaly', true)->count(),
                ]);
            }
        }
    }
}
