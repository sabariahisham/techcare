<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\RefBahagian;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;

class PrestasiPegtugasController extends Controller
{
    public function index(Request $request)
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

        // Calculate all statistics with filters applied
        $statisticsQuery = DB::table('prestasi_pegtugas')
            ->selectRaw('COUNT(DISTINCT assignee_id) as total_officers')
            ->selectRaw('SUM(tiket_diterima) as total_tickets')
            ->selectRaw('SUM(tiket_selesai) as total_tiket_selesai')
            ->selectRaw('SUM(tiket_dalam_tindakan) as total_tiket_dalam_tindakan')
            ->selectRaw('SUM(tiket_lewat_sla) as total_tiket_lewat_sla')
            ->selectRaw('SUM(tiket_selesai_dalam_sla) as total_tiket_selesai_dalam_sla')
            ->selectRaw('SUM(high) as total_high')
            ->selectRaw('SUM(medium) as total_medium')
            ->selectRaw('SUM(low) as total_low');

        // Apply filters if provided
        if ($request->filled('bulan')) {
            $statisticsQuery->where('bulan', $request->bulan);
        }
        if ($request->filled('tahun')) {
            $statisticsQuery->where('tahun', $request->tahun);
        }

        $statistics = $statisticsQuery->first();

        return view('report.prestasi-pegtugas', compact(
            'officers',
            'bahagians'
        ))->with([
            'totalOfficers' => $statistics->total_officers ?? 0,
            'totalTickets' => $statistics->total_tickets ?? 0,
            'totalTicketSelesai' => $statistics->total_tiket_selesai ?? 0,
            'totalTicketDalamTindakan' => $statistics->total_tiket_dalam_tindakan ?? 0,
            'totalTicketLewatSla' => $statistics->total_tiket_lewat_sla ?? 0,
            'totalTicketSelesaiDalamSla' => $statistics->total_tiket_selesai_dalam_sla ?? 0,
            'totalHigh' => $statistics->total_high ?? 0,
            'totalMedium' => $statistics->total_medium ?? 0,
            'totalLow' => $statistics->total_low ?? 0,
            'filterBulan' => $request->input('bulan'),
            'filterTahun' => $request->input('tahun'),
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
            'bulan' => 'nullable|integer|min:1|max:12',
            'tahun' => 'nullable|integer|min:2020|max:2099',
        ]);

        $query = DB::table('prestasi_pegtugas as pp')
            ->join('users as u', 'u.id', '=', 'pp.assignee_id')
            ->select(
                'pp.assignee_id',
                'u.nama as pegawai_tugasan',
                'pp.bulan',
                'pp.tahun',
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

        if ($request->filled('bulan')) {
            $query->where('pp.bulan', $request->bulan);
        }

        if ($request->filled('tahun')) {
            $query->where('pp.tahun', $request->tahun);
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
            ->editColumn('bulan', function ($row) {
                $bulanNames = [
                    1 => 'Januari', 2 => 'Februari', 3 => 'Mac', 4 => 'April',
                    5 => 'Mei', 6 => 'Jun', 7 => 'Julai', 8 => 'Ogos',
                    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Disember'
                ];
                return $bulanNames[$row->bulan] ?? '-';
            })
            ->editColumn('tahun', function ($row) {
                return $row->tahun ?? '-';
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
                'pp.assignee_id',
                'u.nama as pegawai_tugasan',
                'pp.bulan',
                'pp.tahun',
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

        // Get filter values
        $filterBulan = $request->filled('bulan') ? $request->bulan : null;
        $filterTahun = $request->filled('tahun') ? $request->tahun : null;

        // Calculate summary statistics - count unique officers
        $totalOfficers = $data->unique('assignee_id')->count();
        $totalTickets = $data->sum('tiket_diterima');
        $totalTicketSelesai = $data->sum('tiket_selesai');
        $totalTicketDalamTindakan = $data->sum('tiket_dalam_tindakan');
        $totalTicketLewatSla = $data->sum('tiket_lewat_sla');
        $totalTicketSelesaiDalamSla = $data->sum('tiket_selesai_dalam_sla');
        $totalHigh = $data->sum('high');
        $totalMedium = $data->sum('medium');
        $totalLow = $data->sum('low');

        // Create Excel export
        $filename = 'prestasi_pegawai_tugasan_' . date('YmdHis') . '.csv';

        return response()->streamDownload(function () use ($data, $totalOfficers, $totalTickets, $totalTicketSelesai, $totalTicketDalamTindakan, $totalTicketLewatSla, $totalTicketSelesaiDalamSla, $totalHigh, $totalMedium, $totalLow, $filterBulan, $filterTahun) {
            $handle = fopen('php://output', 'w');

            // Add BOM for UTF-8
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Add summary statistics header
            fputcsv($handle, ['LAPORAN PRESTASI PEGAWAI TUGASAN']);
            fputcsv($handle, ['Tarikh: ' . date('d-m-Y')]);

            // Add filter information
            $bulanNames = [
                1 => 'Januari', 2 => 'Februari', 3 => 'Mac', 4 => 'April',
                5 => 'Mei', 6 => 'Jun', 7 => 'Julai', 8 => 'Ogos',
                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Disember'
            ];
            if ($filterBulan) {
                fputcsv($handle, ['Bulan: ' . ($bulanNames[$filterBulan] ?? $filterBulan)]);
            }
            if ($filterTahun) {
                fputcsv($handle, ['Tahun: ' . $filterTahun]);
            }
            fputcsv($handle, []);
            fputcsv($handle, ['RINGKASAN']);
            fputcsv($handle, ['Jumlah Pegawai', $totalOfficers]);
            fputcsv($handle, ['Jumlah Tiket Diterima', $totalTickets]);
            fputcsv($handle, ['Jumlah Tiket Selesai', $totalTicketSelesai]);
            fputcsv($handle, ['Jumlah Tiket Dalam Tindakan', $totalTicketDalamTindakan]);
            fputcsv($handle, ['Jumlah Tiket Lewat SLA', $totalTicketLewatSla]);
            fputcsv($handle, ['Jumlah Tiket Selesai Dalam SLA', $totalTicketSelesaiDalamSla]);
            fputcsv($handle, ['Jumlah High', $totalHigh]);
            fputcsv($handle, ['Jumlah Medium', $totalMedium]);
            fputcsv($handle, ['Jumlah Low', $totalLow]);
            fputcsv($handle, []);

            // Add headers
            fputcsv($handle, [
                'PEGAWAI TUGASAN',
                'BULAN',
                'TAHUN',
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
            $bulanNames = [
                1 => 'Januari', 2 => 'Februari', 3 => 'Mac', 4 => 'April',
                5 => 'Mei', 6 => 'Jun', 7 => 'Julai', 8 => 'Ogos',
                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Disember'
            ];
            foreach ($data as $row) {
                fputcsv($handle, [
                    $row->pegawai_tugasan,
                    $bulanNames[$row->bulan] ?? $row->bulan,
                    $row->tahun,
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
                    'pp.assignee_id',
                    'u.nama as pegawai_tugasan',
                    'pp.bulan',
                    'pp.tahun',
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

            // Get filter values
            $filterBulan = $request->filled('bulan') ? $request->bulan : null;
            $filterTahun = $request->filled('tahun') ? $request->tahun : null;

            // Month names
            $bulanNames = [
                1 => 'Januari', 2 => 'Februari', 3 => 'Mac', 4 => 'April',
                5 => 'Mei', 6 => 'Jun', 7 => 'Julai', 8 => 'Ogos',
                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Disember'
            ];
            $filterBulanName = $filterBulan ? ($bulanNames[$filterBulan] ?? $filterBulan) : null;

            // Calculate summary statistics - count unique officers
            $totalOfficers = $data->unique('assignee_id')->count();
            $totalTickets = $data->sum('tiket_diterima');
            $totalTicketSelesai = $data->sum('tiket_selesai');
            $totalTicketDalamTindakan = $data->sum('tiket_dalam_tindakan');
            $totalTicketLewatSla = $data->sum('tiket_lewat_sla');
            $totalTicketSelesaiDalamSla = $data->sum('tiket_selesai_dalam_sla');
            $totalHigh = $data->sum('high');
            $totalMedium = $data->sum('medium');
            $totalLow = $data->sum('low');
            $generatedDate = now()->format('d/m/Y H:i:s');

            // Render HTML for PDF using Blade view
            $html = view('report.prestasi-pegtugas-pdf', compact(
                'data',
                'totalOfficers',
                'totalTickets',
                'totalTicketSelesai',
                'totalTicketDalamTindakan',
                'totalTicketLewatSla',
                'totalTicketSelesaiDalamSla',
                'totalHigh',
                'totalMedium',
                'totalLow',
                'generatedDate',
                'filterBulan',
                'filterTahun',
                'filterBulanName'
            ))->render();

            // Return HTML response that will be printed to PDF
            return response($html)
                ->header('Content-Type', 'text/html; charset=UTF-8');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Ralat semasa eksport PDF: ' . $e->getMessage());
        }
    }
}
