<?php

namespace App\Http\Controllers\Audit;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;


class AuditLogController extends Controller
{
    // -----------------------------------------------------------
    // 1. Senarai fail log harian
    // -----------------------------------------------------------
    public function index()
    {
        $files = File::files(storage_path('logs'));

        $logFiles = collect($files)
            ->filter(fn($file) => str_contains($file->getFilename(), 'laravel-'))
            ->map(function ($file) {
                $filename = $file->getFilename();
                $date = str_replace(['laravel-', '.log'], '', $filename);

                return [
                    'filename' => $filename,
                     'date' => $date,
                    //'date' => Carbon::parse($date)->format('d-m-Y'),
                    'size' => $file->getSize(),
                ];
            })
            ->sortBy('date')
            ->values();

        return view('audit.logs.index', compact('logFiles'));
    }

    // -----------------------------------------------------------
    // 2. Return raw log content untuk modal
    // -----------------------------------------------------------
    public function show($date)
    {
        // Validasi tarikh
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            abort(400, "Format tarikh tidak sah. (YYYY-MM-DD)");
        }

        $path = storage_path("logs/laravel-$date.log");

        if (!File::exists($path)) {
            abort(404, "Log untuk tarikh $date tidak dijumpai.");
        }

        $content = File::get($path);
        
        // Return raw content for modal
        return response($content, 200)
            ->header('Content-Type', 'text/plain; charset=utf-8');
    }

    // -----------------------------------------------------------
    // 3. Download log
    // -----------------------------------------------------------
    public function download($date)
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            abort(400, "Format tarikh tidak sah. (YYYY-MM-DD)");
        }

        $path = storage_path("logs/laravel-$date.log");

        if (!File::exists($path)) {
            abort(404, "Log tidak dijumpai.");
        }

        return response()->download($path, "laravel-$date.log");
    }
}
