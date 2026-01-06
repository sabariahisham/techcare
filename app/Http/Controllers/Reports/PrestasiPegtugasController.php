<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\RefBahagian;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;

class PrestasiPegtugasController extends Controller
{
    public function index()
    {
        // Get unique officers from prestasi_pegtugas view with user names
        $officers = DB::table('prestasi_pegtugas as pp')
            ->join('users as u', 'u.id', '=', 'pp.assignee_id')
            ->select('pp.assignee_id', 'u.nama')
            ->distinct()
            ->whereNotNull('pp.assignee_id')
            ->orderBy('u.nama')
            ->pluck('u.nama', 'pp.assignee_id');

        // Get bahagians
        $bahagians = RefBahagian::where('status', 'active')
            ->orderBy('nama_bah', 'asc')
            ->get();

        // Calculate all statistics in a single optimized query
        $statistics = DB::table('prestasi_pegtugas')
            ->selectRaw('COUNT(DISTINCT assignee_id) as total_officers')
            ->selectRaw('SUM(tiket_diterima) as total_tickets')
            ->selectRaw('SUM(high) as total_high')
            ->selectRaw('SUM(medium) as total_medium')
            ->selectRaw('SUM(low) as total_low')
            ->first();

        return view('report.prestasi-pegtugas', compact(
            'officers',
            'bahagians'
        ))->with([
            'totalOfficers' => $statistics->total_officers ?? 0,
            'totalTickets' => $statistics->total_tickets ?? 0,
            'totalHigh' => $statistics->total_high ?? 0,
            'totalMedium' => $statistics->total_medium ?? 0,
            'totalLow' => $statistics->total_low ?? 0,
        ]);
    }

    /**
     * Build base query for prestasi pegtugas data
     *
     * @param Request $request
     * @return \Illuminate\Database\Query\Builder
     */
    private function buildBaseQuery(Request $request)
    {
        // Validate request parameters
        $request->validate([
            'officer' => 'nullable|integer|exists:users,id',
        ]);

        $query = DB::table('prestasi_pegtugas as pp')
            ->join('users as u', 'u.id', '=', 'pp.assignee_id')
            ->select(
                'pp.assignee_id',
                'u.nama as pegawai_tugasan',
                'pp.tiket_diterima',
                'pp.tiket_selesai',
                'pp.tiket_dalam_tindakan',
                'pp.tiket_lewat_sla',
                'pp.tiket_selesai_dalam_sla',
                'pp.high',
                'pp.medium',
                'pp.low'
            );

        // Apply filters
        if ($request->filled('officer')) {
            $query->where('pp.assignee_id', $request->officer);
        }

        return $query;
    }

    public function data(Request $request)
    {
        $query = $this->buildBaseQuery($request);

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('pegawai_tugasan', function ($row) {
                return $row->pegawai_tugasan ?? '-';
            })
            ->editColumn('tiket_diterima', function ($row) {
                return $row->tiket_diterima ?? 0;
            })
            ->editColumn('tiket_selesai', function ($row) {
                return $row->tiket_selesai ?? 0;
            })
            ->editColumn('tiket_dalam_tindakan', function ($row) {
                return $row->tiket_dalam_tindakan ?? 0;
            })
            ->editColumn('tiket_lewat_sla', function ($row) {
                return $row->tiket_lewat_sla ?? 0;
            })
            ->editColumn('tiket_selesai_dalam_sla', function ($row) {
                return $row->tiket_selesai_dalam_sla ?? 0;
            })
            ->editColumn('high', function ($row) {
                return $row->high ?? 0;
            })
            ->editColumn('medium', function ($row) {
                return $row->medium ?? 0;
            })
            ->editColumn('low', function ($row) {
                return $row->low ?? 0;
            })
            ->make(true);
    }

    public function exportExcel(Request $request)
    {
        $query = $this->buildBaseQuery($request)
            ->select(
                'u.nama as pegawai_tugasan',
                'pp.tiket_diterima',
                'pp.tiket_selesai',
                'pp.tiket_dalam_tindakan',
                'pp.tiket_lewat_sla',
                'pp.tiket_selesai_dalam_sla',
                'pp.high',
                'pp.medium',
                'pp.low'
            )
            ->orderBy('pp.tiket_diterima', 'desc');

        $data = $query->get();

        // Calculate summary statistics
        $totalOfficers = $data->count();
        $totalTickets = $data->sum('tiket_diterima');
        $totalHigh = $data->sum('high');
        $totalMedium = $data->sum('medium');
        $totalLow = $data->sum('low');

        // Create Excel export
        $filename = 'prestasi_pegawai_tugasan_' . date('YmdHis') . '.csv';

        return response()->streamDownload(function () use ($data, $totalOfficers, $totalTickets, $totalHigh, $totalMedium, $totalLow) {
            $handle = fopen('php://output', 'w');

            // Add BOM for UTF-8
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Add summary statistics header
            fputcsv($handle, ['LAPORAN PRESTASI PEGAWAI TUGASAN']);
            fputcsv($handle, ['Tarikh: ' . date('d-m-Y')]);
            fputcsv($handle, []);
            fputcsv($handle, ['RINGKASAN']);
            fputcsv($handle, ['Jumlah Pegawai', $totalOfficers]);
            fputcsv($handle, ['Jumlah Tiket', $totalTickets]);
            fputcsv($handle, ['Jumlah High', $totalHigh]);
            fputcsv($handle, ['Jumlah Medium', $totalMedium]);
            fputcsv($handle, ['Jumlah Low', $totalLow]);
            fputcsv($handle, []);

            // Add headers
            fputcsv($handle, [
                'PEGAWAI TUGASAN',
                'TIKET DITERIMA',
                'TIKET DISELESAI',
                'TIKET DALAM TINDAKAN',
                'TIKET LEWAT SLA',
                'TIKET SELESAI DALAM TEMPOH SLA',
                'HIGH',
                'MEDIUM',
                'LOW'
            ]);

            // Add data rows
            foreach ($data as $row) {
                fputcsv($handle, [
                    $row->pegawai_tugasan,
                    $row->tiket_diterima,
                    $row->tiket_selesai,
                    $row->tiket_dalam_tindakan,
                    $row->tiket_lewat_sla,
                    $row->tiket_selesai_dalam_sla,
                    $row->high,
                    $row->medium,
                    $row->low
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function exportPdf(Request $request)
    {
        try {
            $query = $this->buildBaseQuery($request)
                ->select(
                    'u.nama as pegawai_tugasan',
                    'pp.tiket_diterima',
                    'pp.tiket_selesai',
                    'pp.tiket_dalam_tindakan',
                    'pp.tiket_lewat_sla',
                    'pp.tiket_selesai_dalam_sla',
                    'pp.high',
                    'pp.medium',
                    'pp.low'
                )
                ->orderBy('pp.tiket_diterima', 'desc');

            $data = $query->get();

            // Calculate summary statistics
            $totalOfficers = $data->count();
            $totalTickets = $data->sum('tiket_diterima');
            $totalHigh = $data->sum('high');
            $totalMedium = $data->sum('medium');
            $totalLow = $data->sum('low');
            $generatedDate = now()->format('d/m/Y H:i:s');

            // Render HTML for PDF using Blade view
            $html = view('report.prestasi-pegtugas-pdf', compact(
                'data',
                'totalOfficers',
                'totalTickets',
                'totalHigh',
                'totalMedium',
                'totalLow',
                'generatedDate'
            ))->render();

            // Return HTML response that will be printed to PDF
            return response($html)
                ->header('Content-Type', 'text/html; charset=UTF-8');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Ralat semasa eksport PDF: ' . $e->getMessage());
        }
    }
}
