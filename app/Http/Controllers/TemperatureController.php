<?php

namespace App\Http\Controllers;

use App\Models\Machine;
use App\Models\Temperature;
use App\Models\TemperatureReading;
use App\Models\MonthlySummary;
use App\Services\PdfProcessingService;
use App\Services\DataImportService;
use App\Services\AnomalyDetectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;
use App\Events\TemperatureUpdated;
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

    /**
     * Tampilkan daftar pembacaan suhu
     */
    // public function index(Request $request)
    // {
    //     $query = Temperature::with(['machine.branch'])
    //         ->orderBy('reading_date', 'desc')
    //         ->orderBy('reading_time', 'desc');

    //     if ($request->filled('machine_id')) {
    //         $query->where('machine_id', $request->machine_id);
    //     }

    //     if ($request->filled('date_from')) {
    //         $query->where('reading_date', '>=', $request->date_from);
    //     }

    //     if ($request->filled('date_to')) {
    //         $query->where('reading_date', '<=', $request->date_to);
    //     }

    //     if ($request->filled('validation_status')) {
    //         $query->where('validation_status', $request->validation_status);
    //     }

    //     // Tampilan detail atau grouped
    //     if ($request->view === 'detailed') {
    //         $readings = $query->paginate(50);
    //         $machines = Machine::with('branch')->where('is_active', true)->get();
    //         return view('layouts.temperature.detailed', compact('readings', 'machines'));
    //     }

    //     $readings = $query->get();
    //     $groupedReadings = $readings->groupBy('reading_date');
    //     $machines = Machine::with('branch')->where('is_active', true)->get();
    //     $trendReadings = $readings;
    //     return view('layouts.temperature.index', compact('readings', 'groupedReadings', 'machines', 'trendReadings'));
    // }
    public function index(Request $request)
    {
        $query = Temperature::with(['machine.branch'])
            ->orderBy('reading_date', 'desc')
            ->orderBy('reading_time', 'desc');

        if ($request->filled('machine_id')) {
            $query->where('machine_id', $request->machine_id);
        }

        if ($request->filled('date_from')) {
            $query->where('reading_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('reading_date', '<=', $request->date_to);
        }

        if ($request->filled('validation_status')) {
            $query->where('validation_status', $request->validation_status);
        }

        // Tampilan detail atau grouped
        if ($request->view === 'detailed') {
            $readings = $query->paginate(50);
            $machines = Machine::with('branch')->where('is_active', true)->get();
            return view('layouts.temperature.detailed', compact('readings', 'machines'));
        }

        // Query khusus untuk chart (30 data terakhir sebagai default)
        $chartQuery = Temperature::with(['machine'])
            ->orderBy('reading_date', 'desc')
            ->orderBy('reading_time', 'desc');

        // Apply filter yang sama untuk chart
        if ($request->filled('machine_id')) {
            $chartQuery->where('machine_id', $request->machine_id);
        }

        if ($request->filled('date_from')) {
            $chartQuery->where('reading_date', '>=', $request->date_from);
        } else {
            // Jika tidak ada filter tanggal, ambil 30 hari terakhir
            $chartQuery->where('reading_date', '>=', now()->subDays(30));
        }

        if ($request->filled('date_to')) {
            $chartQuery->where('reading_date', '<=', $request->date_to);
        }

        if ($request->filled('validation_status')) {
            $chartQuery->where('validation_status', $request->validation_status);
        }

        // Ambil data untuk chart (maksimal 100 titik data agar chart tidak terlalu padat)
        $chartData = $chartQuery->limit(100)->get();

        // Data untuk tabel (dengan pagination)
        $readings = $query->get();
        // dd($chartData->count()); // Uncomment untuk debug

        // Data untuk card view (grouped)
        $groupedReadings = $readings->groupBy('reading_date');
        $machines = Machine::with('branch')->where('is_active', true)->get();

        return view('layouts.temperature.index', compact(
            'readings',
            'groupedReadings',
            'machines',
            'chartData'
        ));
    }

    /**
     * Tampilkan data berdasarkan tanggal
     */
    public function showDate($date)
    {
        $readings = Temperature::with(['machine.branch'])
            ->where('reading_date', $date)
            ->orderBy('reading_time')
            ->get();

        $groupedByMachine = $readings->groupBy('machine_id');

        $chartData = $readings->map(fn($r) => [
            'time' => $r->reading_time,
            'temperature' => $r->temperature_value,
            'machine' => optional($r->machine)->name ?? 'Unknown Machine',
        ]);

        return view('layouts.temperature.date-detail', compact('readings', 'groupedByMachine', 'chartData', 'date'));
    }

    public function create()
    {
        $machines = Machine::with('branch')->where('is_active', true)->get();
        return view('layouts.temperature.create', compact('machines'));
    }

    /**
     * Simpan data manual
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'machine_id' => 'required|exists:machines,id',
            'temperature_value' => 'required|numeric',
            'timestamp' => 'required|date',
        ]);

        $timestamp = Carbon::parse($data['timestamp']);

        $temperature = Temperature::create([
            'machine_id' => $data['machine_id'],
            'temperature_value' => $data['temperature_value'],
            'timestamp' => $timestamp,
            'reading_date' => $timestamp->format('Y-m-d'),
            'reading_time' => $timestamp->format('H:i:s'),
            'validation_status' => 'manual_entry',
        ]);

        // Run anomaly check on the new temperature reading
        $this->anomalyService->checkSingleReading($temperature);

        return response()->json(['message' => 'Data suhu berhasil disimpan']);
    }

    /**
     * Upload dan proses PDF via Python
     */
public function uploadPdfPy(Request $request)
{
    $request->validate([
        'file' => 'required|file|mimes:pdf|max:10240',
        'machine_id' => 'required|exists:machines,id'
    ]);

    try {
        $file = $request->file('file');

        // URL endpoint Python API
        $pythonApiUrl = 'https://466892740e9a.ngrok-free.app//upload';

        // Prepare file content based on environment
        $fileContent = app()->environment('testing')
            ? 'fake-content'
            : file_get_contents($file->getRealPath());

        // Send request to Python API
        $response = Http::timeout(120) // Tambah timeout untuk proses PDF yang besar
            ->withHeaders([
                'Accept' => 'application/json',
            ])
            ->attach(
                'file',
                $fileContent,
                $file->getClientOriginalName()
            )->post($pythonApiUrl, [
                'machine_id' => $request->machine_id
            ]);

        // Log untuk debugging
        Log::info('PDF Upload Request:', [
            'machine_id' => $request->machine_id,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'response_status' => $response->status(),
        ]);

        if ($response->failed()) {
            Log::error('Python API Failed Response:', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return redirect()->route('temperature.index')
                ->with('error', 'Gagal menghubungi Python API. Status: ' . $response->status() .
                       ' - Pastikan server Python berjalan dan dapat diakses.');
        }

        $data = $response->json();

        // Validasi respons dari Python API
        if (!isset($data['temperature_data']) || !is_array($data['temperature_data'])) {
            Log::error('Invalid Python API Response Format:', $data);
            return redirect()->route('temperature.index')
                ->with('error', 'Format respons dari Python API tidak valid. Data tidak ditemukan.');
        }

        $importedCount = 0;
        $failedCount = 0;
        $duplicateCount = 0;

        foreach ($data['temperature_data'] as $item) {
            try {
                // Validasi data yang diperlukan
                if (!isset($item['timestamp']) || !isset($item['temperature'])) {
                    $failedCount++;
                    Log::warning('Missing required fields in item:', $item);
                    continue;
                }

                $timestamp = Carbon::parse($item['timestamp']);
                $machineId = $item['machine_id'] ?? $request->machine_id;

                // Cek duplikat berdasarkan timestamp dan machine_id
                $existing = Temperature::where('machine_id', $machineId)
                    ->where('timestamp', $timestamp)
                    ->first();

                if ($existing) {
                    $duplicateCount++;
                    Log::info('Duplicate temperature skipped:', [
                        'machine_id' => $machineId,
                        'timestamp' => $timestamp
                    ]);
                    continue;
                }

                $temperature = Temperature::create([
                    'machine_id' => $machineId,
                    'temperature_value' => $item['temperature'],
                    'timestamp' => $timestamp,
                    'reading_date' => $timestamp->format('Y-m-d'),
                    'reading_time' => $timestamp->format('H:i:s'),
                    'validation_status' => 'pending',
                    'source' => 'pdf_import',
                    'file_name' => $file->getClientOriginalName(),
                ]);

                // Run anomaly check on imported temperature
                $this->anomalyService->checkSingleReading($temperature);
                $importedCount++;

            } catch (\Exception $e) {
                // Log error untuk setiap item yang gagal
                Log::error('Error processing temperature item:', [
                    'item' => $item,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $failedCount++;
                continue;
            }
        }

        // Update summaries hanya jika ada data yang berhasil diimport
        if ($importedCount > 0) {
            try {
                $machine = Machine::findOrFail($request->machine_id);
                $this->updateMonthlySummariesForMachine($machine);

                Log::info('Monthly summaries updated for machine:', [
                    'machine_id' => $request->machine_id,
                    'imported_count' => $importedCount
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to update monthly summaries:', [
                    'machine_id' => $request->machine_id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Prepare success message
        $message = "Upload PDF berhasil!";

        if ($importedCount > 0) {
            $message .= " Sebanyak {$importedCount} data suhu baru berhasil diimpor.";
        }

        if ($duplicateCount > 0) {
            $message .= " {$duplicateCount} data duplikat dilewati.";
        }

        if ($failedCount > 0) {
            $message .= " {$failedCount} data gagal diproses.";
        }

        if ($importedCount === 0 && $duplicateCount === 0 && $failedCount === 0) {
            $message = "Tidak ada data yang ditemukan dalam PDF.";
        }

        return redirect()->route('temperature.index')
            ->with('success', $message);

    } catch (\Illuminate\Http\Client\ConnectionException $e) {
        Log::error('Python API Connection Error:', [
            'error' => $e->getMessage(),
            'url' => $pythonApiUrl
        ]);

        return redirect()->route('temperature.index')
            ->with('error', 'Tidak dapat terhubung ke Python API. ' .
                   'Pastikan: 1) Server Python berjalan, 2) URL benar, 3) Tidak ada firewall block.');

    } catch (\Illuminate\Validation\ValidationException $e) {
        throw $e; // Biarkan Laravel handle validation error

    } catch (\Exception $e) {
        Log::error('PDF Upload Process Error:', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'file' => $request->file('file')->getClientOriginalName(),
            'machine_id' => $request->machine_id
        ]);

        return redirect()->route('temperature.index')
            ->with('error', 'Terjadi kesalahan saat memproses PDF: ' . $e->getMessage());
    }
}

    public function validateTemperature($date)
    {
        $dateObject = Carbon::parse($date);
        $temperatures = Temperature::where('reading_date', $dateObject);
        $temperatures->update([
            'validation_status' => 'validated',
            'is_validated' => 1,
        ]);

        return redirect('temperature/date/' . $date)->with('success', 'Data tanggal ' . $date . ' berhasil divalidasi!');
    }

    /**
     * Export PDF report
     */
    public function exportPdf(Request $request)
    {
        $filters = $request->only(['machine_id', 'date_from', 'date_to']);
        $pdf = $this->pdfService->generateTemperatureReport($filters);
        $filename = 'temperature_report_' . now()->format('Y-m-d_H-i-s') . '.pdf';
        return $pdf->download($filename);
    }

    public function show($id)
    {
        $temperature = Temperature::with(['machine.branch'])->findOrFail($id);

        $nearbyReadings = Temperature::where('machine_id', $temperature->machine_id)
            ->where('reading_date', $temperature->reading_date)
            ->where('id', '!=', $temperature->id)
            ->orderBy('reading_time')
            ->get();

        $chartData = Temperature::where('machine_id', $temperature->machine_id)
            ->where('reading_date', $temperature->reading_date)
            ->orderBy('reading_time')
            ->get()
            ->map(fn($r) => [
                'time' => $r->reading_time,
                'temperature' => $r->temperature_value,
                'is_current' => $r->id === $temperature->id
            ]);

        return view('layouts.temperature.show', compact('temperature', 'nearbyReadings', 'chartData'));
    }

    public function edit($id)
    {
        $temperature = Temperature::with(['machine.branch'])->findOrFail($id);
        $machines = Machine::with('branch')->where('is_active', true)->get();

        $validationStatusOptions = [
            'validated' => 'Validated',
            'rejected' => 'Rejected',
        ];

        return view('layouts.temperature.edit', compact(
            'temperature',
            'machines',
            'validationStatusOptions'
        ));
    }

    public function update(Request $request, $id)
    {
        // VALIDATION
        $request->validate([
            'machine_id' => 'required|exists:machines,id',
            'timestamp' => 'required|date',
            'temperature_value' => 'required|numeric',
            'validation_status' => 'required|in:pending,validated,rejected,needs_review,manual_entry,imported,edited',
            'validation_notes' => 'nullable|string|max:500'
        ]);

        $temperature = Temperature::findOrFail($id);
        $oldData = $temperature->toArray(); // Simpan data lama

        // SIMPLIFY VALIDATION LOGIC
        $isValidated = match ($request->validation_status) {
            'validated' => 1,
            'rejected' => 0,
            default => null
        };

        //pertanyaan ?
        $timestamp = Carbon::parse($request->timestamp);

        // UPDATE
        $temperature->update([
            'machine_id' => $request->machine_id,
            'temperature_value' => $request->temperature_value,
            'timestamp' => $timestamp,
            'reading_date' => $timestamp->format('Y-m-d'),
            'reading_time' => $timestamp->format('H:i:s'),
            'validation_status' => $request->validation_status,
            'validation_notes' => $request->validation_notes,
            'is_validated' => $isValidated
        ]);

        // ✅ SELALU trigger event, biarkan Listener yang handle logicnya
        event(new TemperatureUpdated($temperature, $oldData));

        // UPDATE SUMMARY
        $this->updateMonthlySummary($temperature);

        return redirect()->route('temperature.show', $temperature->id)
            ->with('success', 'Temperature reading updated successfully.');
    }
    /**
     * Determine if anomaly check should be performed
     */
    private function shouldCheckAnomaly($oldTemp, $newTemp, $oldStatus, $newStatus): bool
    {
        // Always check if status changed to 'validated'
        if ($oldStatus !== 'validated' && $newStatus === 'validated') {
            return true;
        }

        // Check if temperature changed significantly (more than 2°C)
        if (abs($oldTemp - $newTemp) >= 2.0) {
            return true;
        }

        // Check if status changed from rejected/needs_review to validated
        if (in_array($oldStatus, ['rejected', 'needs_review']) && $newStatus === 'validated') {
            return true;
        }

        return false;
    }

    /**
     * Handle anomaly check for Temperature model
     */
    private function checkAnomalyForTemperature(Temperature $temperature)
    {
        // Cari atau buat TemperatureReading terkait
        $reading = $this->findOrCreateTemperatureReading($temperature);

        if ($reading) {
            $anomalies = $this->anomalyService->checkSingleReading($reading);

            if (count($anomalies) > 0) {
                Log::info("Anomaly detected after temperature update ID: {$temperature->id}, anomalies: " . count($anomalies));

                // Bisa tambahkan flash message atau notification
                session()->flash(
                    'anomaly_warning',
                    count($anomalies) . ' anomaly detected after update!'
                );
            }

            return count($anomalies);
        }

        return 0;
    }

    /**
     * Find or create TemperatureReading from Temperature
     */
    private function findOrCreateTemperatureReading(Temperature $temperature)
    {
        // Coba cari existing reading
        $reading = TemperatureReading::where('temperature_id', $temperature->id)->first();

        if (!$reading) {
            // Buat baru jika tidak ada
            $reading = TemperatureReading::create([
                'machine_id' => $temperature->machine_id,
                'temperature_id' => $temperature->id, // Link ke temperature asli
                'temperature' => $temperature->temperature_value,
                'recorded_at' => $temperature->timestamp,
                'reading_type' => 'manual_entry',
                'is_anomaly' => false
            ]);
        } else {
            // Update existing reading
            $reading->update([
                'temperature' => $temperature->temperature_value,
                'recorded_at' => $temperature->timestamp
            ]);
        }

        return $reading;
    }
    public function destroy($id)
    {
        $temperature = Temperature::findOrFail($id);
        $date = $temperature->reading_date;
        $machine = Machine::find($temperature->machine_id);
        $temperature->delete();

        if ($machine) {
            $this->updateMonthlySummariesForMachine($machine);
        }

        return redirect()->route('temperature.show-date', $date)
            ->with('success', 'Temperature reading deleted successfully.');
    }

    /**
     * Generate data untuk chart AJAX
     */
    public function getChartData(Request $request)
    {
        $readings = Temperature::where('machine_id', $request->machine_id)
            ->where('reading_date', $request->date)
            ->orderBy('reading_time')
            ->get();

        return response()->json($readings->map(fn($r) => [
            'time' => $r->reading_time,
            'temperature' => $r->temperature_value,
            'timestamp' => $r->timestamp->format('Y-m-d H:i:s')
        ]));
    }

    /**
     * Get temperature readings for a specific machine (API endpoint)
     */
    public function getTemperatureReadingsForMachine($machineId)
    {
        $readings = Temperature::where('machine_id', $machineId)
            ->orderBy('timestamp', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($reading) {
                return [
                    'id' => $reading->id,
                    'temperature' => $reading->temperature_value,
                    'timestamp' => $reading->timestamp->format('Y-m-d H:i:s')
                ];
            });

        return response()->json($readings);
    }

    /* ==============================
     * Helper untuk rekap bulanan
     * ============================== */
    private function updateMonthlySummary($reading)
    {
        $year = $reading->timestamp->year;
        $month = $reading->timestamp->month;

        $summary = MonthlySummary::firstOrCreate([
            'machine_id' => $reading->machine_id,
            'year' => $year,
            'month' => $month
        ]);

        $monthlyReadings = Temperature::where('machine_id', $reading->machine_id)
            ->whereYear('timestamp', $year)
            ->whereMonth('timestamp', $month)
            ->get();

        if ($monthlyReadings->isNotEmpty()) {
            $summary->update([
                'temp_avg' => $monthlyReadings->avg('temperature_value'),
                'temp_min' => $monthlyReadings->min('temperature_value'),
                'temp_max' => $monthlyReadings->max('temperature_value'),
                'total_readings' => $monthlyReadings->count()
            ]);
        }
    }

    private function updateMonthlySummariesForMachine($machine)
    {
        $monthlyData = Temperature::where('machine_id', $machine->id)
            ->selectRaw('YEAR(timestamp) as year, MONTH(timestamp) as month')
            ->groupBy('year', 'month')
            ->get();

        foreach ($monthlyData as $data) {
            $summary = MonthlySummary::firstOrCreate([
                'machine_id' => $machine->id,
                'year' => $data->year,
                'month' => $data->month
            ]);

            $monthlyReadings = Temperature::where('machine_id', $machine->id)
                ->whereYear('timestamp', $data->year)
                ->whereMonth('timestamp', $data->month)
                ->get();

            if ($monthlyReadings->isNotEmpty()) {
                $summary->update([
                    'temp_avg' => $monthlyReadings->avg('temperature_value'),
                    'temp_min' => $monthlyReadings->min('temperature_value'),
                    'temp_max' => $monthlyReadings->max('temperature_value'),
                    'total_readings' => $monthlyReadings->count()
                ]);
            }
        }
    }
}
