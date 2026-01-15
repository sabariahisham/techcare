<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\RefCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class AduanTahunanController extends Controller
{
    /**
     * Display the annual complaint report page
     */
    public function index()
    {
        $categories = RefCategory::orderBy('cat_name', 'asc')->get();

        // Get available years from tickets_chronologies
        $years = DB::table('tickets_chronologies')
            ->selectRaw('DISTINCT YEAR(created_at) as year')
            ->whereNotNull('created_at')
            ->orderBy('year', 'desc')
            ->pluck('year');

        // Default to current year if no data
        if ($years->isEmpty()) {
            $years = collect([date('Y')]);
        }

        return view('report.aduan-tahunan', compact('categories', 'years'));
    }

    /**
     * Get annual complaint data for DataTable
     */
   
    public function data(Request $request)
    {
        $request->validate([
            'year'     => 'required|integer|min:2000|max:2100',
            'category' => 'nullable|string',
            'months'   => 'nullable|string'
        ]);

        $year = $request->input('year', date('Y'));
        $categoryFilter = $request->filled('category')
            ? array_map('intval', explode(',', $request->category))
            : [];
        $monthsFilter = $request->filled('months')
            ? array_map('intval', explode(',', $request->months))
            : [];

        // Build the query with monthly breakdown
        $query = DB::table('ref_categories as rc')
            ->leftJoin('tickets as t', 'rc.id', '=', 't.cat_id')
            ->leftJoin('tickets_chronologies as tc', 't.id', '=', 'tc.ticket_id')
            ->select('rc.id as category_id', 'rc.cat_name')
            ->selectRaw("
                COUNT(DISTINCT CASE WHEN MONTH(tc.created_at) = 1 AND YEAR(tc.created_at) = ? THEN tc.ticket_id END) as jan_aduan,
                COUNT(DISTINCT CASE WHEN MONTH(tc.created_at) = 1 AND YEAR(tc.created_at) = ? AND tc.tick_status = 6 THEN tc.ticket_id END) as jan_selesai,

                COUNT(DISTINCT CASE WHEN MONTH(tc.created_at) = 2 AND YEAR(tc.created_at) = ? THEN tc.ticket_id END) as feb_aduan,
                COUNT(DISTINCT CASE WHEN MONTH(tc.created_at) = 2 AND YEAR(tc.created_at) = ? AND tc.tick_status = 6 THEN tc.ticket_id END) as feb_selesai,

                COUNT(DISTINCT CASE WHEN MONTH(tc.created_at) = 3 AND YEAR(tc.created_at) = ? THEN tc.ticket_id END) as mac_aduan,
                COUNT(DISTINCT CASE WHEN MONTH(tc.created_at) = 3 AND YEAR(tc.created_at) = ? AND tc.tick_status = 6 THEN tc.ticket_id END) as mac_selesai,

                COUNT(DISTINCT CASE WHEN MONTH(tc.created_at) = 4 AND YEAR(tc.created_at) = ? THEN tc.ticket_id END) as apr_aduan,
                COUNT(DISTINCT CASE WHEN MONTH(tc.created_at) = 4 AND YEAR(tc.created_at) = ? AND tc.tick_status = 6 THEN tc.ticket_id END) as apr_selesai,

                COUNT(DISTINCT CASE WHEN MONTH(tc.created_at) = 5 AND YEAR(tc.created_at) = ? THEN tc.ticket_id END) as mei_aduan,
                COUNT(DISTINCT CASE WHEN MONTH(tc.created_at) = 5 AND YEAR(tc.created_at) = ? AND tc.tick_status = 6 THEN tc.ticket_id END) as mei_selesai,

                COUNT(DISTINCT CASE WHEN MONTH(tc.created_at) = 6 AND YEAR(tc.created_at) = ? THEN tc.ticket_id END) as jun_aduan,
                COUNT(DISTINCT CASE WHEN MONTH(tc.created_at) = 6 AND YEAR(tc.created_at) = ? AND tc.tick_status = 6 THEN tc.ticket_id END) as jun_selesai,

                COUNT(DISTINCT CASE WHEN MONTH(tc.created_at) = 7 AND YEAR(tc.created_at) = ? THEN tc.ticket_id END) as jul_aduan,
                COUNT(DISTINCT CASE WHEN MONTH(tc.created_at) = 7 AND YEAR(tc.created_at) = ? AND tc.tick_status = 6 THEN tc.ticket_id END) as jul_selesai,

                COUNT(DISTINCT CASE WHEN MONTH(tc.created_at) = 8 AND YEAR(tc.created_at) = ? THEN tc.ticket_id END) as ogos_aduan,
                COUNT(DISTINCT CASE WHEN MONTH(tc.created_at) = 8 AND YEAR(tc.created_at) = ? AND tc.tick_status = 6 THEN tc.ticket_id END) as ogos_selesai,

                COUNT(DISTINCT CASE WHEN MONTH(tc.created_at) = 9 AND YEAR(tc.created_at) = ? THEN tc.ticket_id END) as sep_aduan,
                COUNT(DISTINCT CASE WHEN MONTH(tc.created_at) = 9 AND YEAR(tc.created_at) = ? AND tc.tick_status = 6 THEN tc.ticket_id END) as sep_selesai,

                COUNT(DISTINCT CASE WHEN MONTH(tc.created_at) = 10 AND YEAR(tc.created_at) = ? THEN tc.ticket_id END) as okt_aduan,
                COUNT(DISTINCT CASE WHEN MONTH(tc.created_at) = 10 AND YEAR(tc.created_at) = ? AND tc.tick_status = 6 THEN tc.ticket_id END) as okt_selesai,

                COUNT(DISTINCT CASE WHEN MONTH(tc.created_at) = 11 AND YEAR(tc.created_at) = ? THEN tc.ticket_id END) as nov_aduan,
                COUNT(DISTINCT CASE WHEN MONTH(tc.created_at) = 11 AND YEAR(tc.created_at) = ? AND tc.tick_status = 6 THEN tc.ticket_id END) as nov_selesai,

                COUNT(DISTINCT CASE WHEN MONTH(tc.created_at) = 12 AND YEAR(tc.created_at) = ? THEN tc.ticket_id END) as dis_aduan,
                COUNT(DISTINCT CASE WHEN MONTH(tc.created_at) = 12 AND YEAR(tc.created_at) = ? AND tc.tick_status = 6 THEN tc.ticket_id END) as dis_selesai,

                COUNT(DISTINCT CASE WHEN YEAR(tc.created_at) = ? THEN tc.ticket_id END) as jumlah_aduan,
                COUNT(DISTINCT CASE WHEN YEAR(tc.created_at) = ? AND tc.tick_status = 6 THEN tc.ticket_id END) as jumlah_selesai
            ", array_fill(0, 26, $year))
            ->whereNull('rc.deleted_at')
            ->groupBy('rc.id', 'rc.cat_name')
            ->orderBy('rc.cat_name');

        // Apply category filter
        // if ($categoryFilter) {
        //     $query->where('rc.id', $categoryFilter);
        // }

        if (!empty($categoryFilter)) {
            $query->whereIn('rc.id', (array) $categoryFilter);
        }

        if (!empty($monthsFilter)) {
            $query->whereIn(DB::raw('MONTH(tc.created_at)'), $monthsFilter);
        }

        // Get all data for calculating totals SEBELUM DataTables process
        $allData = (clone $query)->get();
        
        // Calculate totals
        $totals = $this->calculateTotals($allData);

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('cat_name', function ($row) {
                return $row->cat_name ?? '-';
            })
            ->addColumn('jan_peratus', fn($row) => $row->jan_aduan == 0 ? '100%' : round(($row->jan_selesai / $row->jan_aduan) * 100, 0) . '%')
            ->addColumn('feb_peratus', fn($row) => $row->feb_aduan == 0 ? '100%' : round(($row->feb_selesai / $row->feb_aduan) * 100, 0) . '%')
            ->addColumn('mac_peratus', fn($row) => $row->mac_aduan == 0 ? '100%' : round(($row->mac_selesai / $row->mac_aduan) * 100, 0) . '%')
            ->addColumn('apr_peratus', fn($row) => $row->apr_aduan == 0 ? '100%' : round(($row->apr_selesai / $row->apr_aduan) * 100, 0) . '%')
            ->addColumn('mei_peratus', fn($row) => $row->mei_aduan == 0 ? '100%' : round(($row->mei_selesai / $row->mei_aduan) * 100, 0) . '%')
            ->addColumn('jun_peratus', fn($row) => $row->jun_aduan == 0 ? '100%' : round(($row->jun_selesai / $row->jun_aduan) * 100, 0) . '%')
            ->addColumn('jul_peratus', fn($row) => $row->jul_aduan == 0 ? '100%' : round(($row->jul_selesai / $row->jul_aduan) * 100, 0) . '%')
            ->addColumn('ogos_peratus', fn($row) => $row->ogos_aduan == 0 ? '100%' : round(($row->ogos_selesai / $row->ogos_aduan) * 100, 0) . '%')
            ->addColumn('sep_peratus', fn($row) => $row->sep_aduan == 0 ? '100%' : round(($row->sep_selesai / $row->sep_aduan) * 100, 0) . '%')
            ->addColumn('okt_peratus', fn($row) => $row->okt_aduan == 0 ? '100%' : round(($row->okt_selesai / $row->okt_aduan) * 100, 0) . '%')
            ->addColumn('nov_peratus', fn($row) => $row->nov_aduan == 0 ? '100%' : round(($row->nov_selesai / $row->nov_aduan) * 100, 0) . '%')
            ->addColumn('dis_peratus', fn($row) => $row->dis_aduan == 0 ? '100%' : round(($row->dis_selesai / $row->dis_aduan) * 100, 0) . '%')
            // PENTING: Tambah jumlah_selesai_total
            ->addColumn('jumlah_selesai_total', fn($row) => $row->jumlah_selesai ?? 0)
            ->addColumn('jumlah_peratus', fn($row) => $row->jumlah_aduan == 0 ? '100%' : round(($row->jumlah_selesai / $row->jumlah_aduan) * 100, 2) . '%')
            ->with('totals', $totals)
            ->make(true);
    }

    /**
     * Export to Excel
     */
    public function exportExcel(Request $request)
    {
        $request->validate([
            'year'     => 'required|integer|min:2000|max:2100',
            'category' => 'nullable|string',
            'months'   => 'nullable|string'
        ]);

        $year = $request->input('year', date('Y'));
        $categoryFilter = $request->filled('category')
            ? array_map('intval', explode(',', $request->category))
            : [];
        $monthsFilter = $request->filled('months')
            ? array_map('intval', explode(',', $request->months))
            : [];

        $data = $this->getReportData($year, $categoryFilter, $monthsFilter);
        $totals = $this->calculateTotals($data);

        $filename = 'laporan_aduan_tahunan_' . $year . '_' . date('YmdHis') . '.csv';

        return response()->streamDownload(function () use ($data, $totals, $year) {
            $handle = fopen('php://output', 'w');

            // Add BOM for UTF-8
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Add title header
            fputcsv($handle, ['LAPORAN ADUAN TAHUNAN ' . $year]);
            fputcsv($handle, ['Tarikh: ' . date('d-m-Y')]);
            fputcsv($handle, []);

            // Add column headers
            $headers = [
                'Kategori Aduan',
                'Jan Bil', 'Jan Selesai', 'Jan %',
                'Feb Bil', 'Feb Selesai', 'Feb %',
                'Mac Bil', 'Mac Selesai', 'Mac %',
                'Apr Bil', 'Apr Selesai', 'Apr %',
                'Mei Bil', 'Mei Selesai', 'Mei %',
                'Jun Bil', 'Jun Selesai', 'Jun %',
                'Jul Bil', 'Jul Selesai', 'Jul %',
                'Ogos Bil', 'Ogos Selesai', 'Ogos %',
                'Sep Bil', 'Sep Selesai', 'Sep %',
                'Okt Bil', 'Okt Selesai', 'Okt %',
                'Nov Bil', 'Nov Selesai', 'Nov %',
                'Dis Bil', 'Dis Selesai', 'Dis %',
                'Jumlah Bil', 'Jumlah Selesai', 'Jumlah %'
            ];
            fputcsv($handle, $headers);

            // Add data rows
            foreach ($data as $row) {
                fputcsv($handle, [
                    $row->cat_name,
                    $row->jan_aduan, $row->jan_selesai, $this->calculatePersen($row->jan_selesai, $row->jan_aduan),
                    $row->feb_aduan, $row->feb_selesai, $this->calculatePersen($row->feb_selesai, $row->feb_aduan),
                    $row->mac_aduan, $row->mac_selesai, $this->calculatePersen($row->mac_selesai, $row->mac_aduan),
                    $row->apr_aduan, $row->apr_selesai, $this->calculatePersen($row->apr_selesai, $row->apr_aduan),
                    $row->mei_aduan, $row->mei_selesai, $this->calculatePersen($row->mei_selesai, $row->mei_aduan),
                    $row->jun_aduan, $row->jun_selesai, $this->calculatePersen($row->jun_selesai, $row->jun_aduan),
                    $row->jul_aduan, $row->jul_selesai, $this->calculatePersen($row->jul_selesai, $row->jul_aduan),
                    $row->ogos_aduan, $row->ogos_selesai, $this->calculatePersen($row->ogos_selesai, $row->ogos_aduan),
                    $row->sep_aduan, $row->sep_selesai, $this->calculatePersen($row->sep_selesai, $row->sep_aduan),
                    $row->okt_aduan, $row->okt_selesai, $this->calculatePersen($row->okt_selesai, $row->okt_aduan),
                    $row->nov_aduan, $row->nov_selesai, $this->calculatePersen($row->nov_selesai, $row->nov_aduan),
                    $row->dis_aduan, $row->dis_selesai, $this->calculatePersen($row->dis_selesai, $row->dis_aduan),
                    $row->jumlah_aduan, $row->jumlah_selesai, $this->calculatePersen($row->jumlah_selesai, $row->jumlah_aduan),
                ]);
            }

            // Add totals row
            fputcsv($handle, [
                'JUMLAH KESELURUHAN',
                $totals['jan_aduan'], $totals['jan_selesai'], $this->calculatePersen($totals['jan_selesai'], $totals['jan_aduan']),
                $totals['feb_aduan'], $totals['feb_selesai'], $this->calculatePersen($totals['feb_selesai'], $totals['feb_aduan']),
                $totals['mac_aduan'], $totals['mac_selesai'], $this->calculatePersen($totals['mac_selesai'], $totals['mac_aduan']),
                $totals['apr_aduan'], $totals['apr_selesai'], $this->calculatePersen($totals['apr_selesai'], $totals['apr_aduan']),
                $totals['mei_aduan'], $totals['mei_selesai'], $this->calculatePersen($totals['mei_selesai'], $totals['mei_aduan']),
                $totals['jun_aduan'], $totals['jun_selesai'], $this->calculatePersen($totals['jun_selesai'], $totals['jun_aduan']),
                $totals['jul_aduan'], $totals['jul_selesai'], $this->calculatePersen($totals['jul_selesai'], $totals['jul_aduan']),
                $totals['ogos_aduan'], $totals['ogos_selesai'], $this->calculatePersen($totals['ogos_selesai'], $totals['ogos_aduan']),
                $totals['sep_aduan'], $totals['sep_selesai'], $this->calculatePersen($totals['sep_selesai'], $totals['sep_aduan']),
                $totals['okt_aduan'], $totals['okt_selesai'], $this->calculatePersen($totals['okt_selesai'], $totals['okt_aduan']),
                $totals['nov_aduan'], $totals['nov_selesai'], $this->calculatePersen($totals['nov_selesai'], $totals['nov_aduan']),
                $totals['dis_aduan'], $totals['dis_selesai'], $this->calculatePersen($totals['dis_selesai'], $totals['dis_aduan']),
                $totals['jumlah_aduan'], $totals['jumlah_selesai'], $this->calculatePersen($totals['jumlah_selesai'], $totals['jumlah_aduan']),
            ]);

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Export to PDF (HTML for print)
     */
    public function exportPdf(Request $request)
    {
        $request->validate([
            'year'     => 'required|integer|min:2000|max:2100',
            'category' => 'nullable|string',
            'months'   => 'nullable|string'
        ]);

        $year = $request->input('year', date('Y'));

        $categoryFilter = $request->filled('category')
            ? array_map('intval', explode(',', $request->category))
            : [];

        $monthsFilter = $request->filled('months')
            ? array_map('intval', explode(',', $request->months))
            : [];

        $data = $this->getReportData($year, $categoryFilter, $monthsFilter);
        $totals = $this->calculateTotals($data);
        $generatedDate = now()->format('d/m/Y H:i:s');

        $html = view('report.aduan-tahunan-pdf', compact(
            'data',
            'totals',
            'year',
            'generatedDate'
        ))->render();

        return response($html)
            ->header('Content-Type', 'text/html; charset=UTF-8');
    }

    /**
     * Get report data
     */
    private function getReportData(int $year, ?array $categoryFilter = null, array $monthsFilter = [])

    {
        $query = DB::table('ref_categories as rc')
            ->leftJoin('tickets as t', 'rc.id', '=', 't.cat_id')
            ->leftJoin('tickets_chronologies as tc', 't.id', '=', 'tc.ticket_id')
            ->select('rc.id as category_id', 'rc.cat_name')
            ->selectRaw("
                COUNT(DISTINCT CASE WHEN MONTH(tc.created_at) = 1 AND YEAR(tc.created_at) = ? THEN tc.ticket_id END) as jan_aduan,
                COUNT(DISTINCT CASE WHEN MONTH(tc.created_at) = 1 AND YEAR(tc.created_at) = ? AND tc.tick_status = 6 THEN tc.ticket_id END) as jan_selesai,

                COUNT(DISTINCT CASE WHEN MONTH(tc.created_at) = 2 AND YEAR(tc.created_at) = ? THEN tc.ticket_id END) as feb_aduan,
                COUNT(DISTINCT CASE WHEN MONTH(tc.created_at) = 2 AND YEAR(tc.created_at) = ? AND tc.tick_status = 6 THEN tc.ticket_id END) as feb_selesai,

                COUNT(DISTINCT CASE WHEN MONTH(tc.created_at) = 3 AND YEAR(tc.created_at) = ? THEN tc.ticket_id END) as mac_aduan,
                COUNT(DISTINCT CASE WHEN MONTH(tc.created_at) = 3 AND YEAR(tc.created_at) = ? AND tc.tick_status = 6 THEN tc.ticket_id END) as mac_selesai,

                COUNT(DISTINCT CASE WHEN MONTH(tc.created_at) = 4 AND YEAR(tc.created_at) = ? THEN tc.ticket_id END) as apr_aduan,
                COUNT(DISTINCT CASE WHEN MONTH(tc.created_at) = 4 AND YEAR(tc.created_at) = ? AND tc.tick_status = 6 THEN tc.ticket_id END) as apr_selesai,

                COUNT(DISTINCT CASE WHEN MONTH(tc.created_at) = 5 AND YEAR(tc.created_at) = ? THEN tc.ticket_id END) as mei_aduan,
                COUNT(DISTINCT CASE WHEN MONTH(tc.created_at) = 5 AND YEAR(tc.created_at) = ? AND tc.tick_status = 6 THEN tc.ticket_id END) as mei_selesai,

                COUNT(DISTINCT CASE WHEN MONTH(tc.created_at) = 6 AND YEAR(tc.created_at) = ? THEN tc.ticket_id END) as jun_aduan,
                COUNT(DISTINCT CASE WHEN MONTH(tc.created_at) = 6 AND YEAR(tc.created_at) = ? AND tc.tick_status = 6 THEN tc.ticket_id END) as jun_selesai,

                COUNT(DISTINCT CASE WHEN MONTH(tc.created_at) = 7 AND YEAR(tc.created_at) = ? THEN tc.ticket_id END) as jul_aduan,
                COUNT(DISTINCT CASE WHEN MONTH(tc.created_at) = 7 AND YEAR(tc.created_at) = ? AND tc.tick_status = 6 THEN tc.ticket_id END) as jul_selesai,

                COUNT(DISTINCT CASE WHEN MONTH(tc.created_at) = 8 AND YEAR(tc.created_at) = ? THEN tc.ticket_id END) as ogos_aduan,
                COUNT(DISTINCT CASE WHEN MONTH(tc.created_at) = 8 AND YEAR(tc.created_at) = ? AND tc.tick_status = 6 THEN tc.ticket_id END) as ogos_selesai,

                COUNT(DISTINCT CASE WHEN MONTH(tc.created_at) = 9 AND YEAR(tc.created_at) = ? THEN tc.ticket_id END) as sep_aduan,
                COUNT(DISTINCT CASE WHEN MONTH(tc.created_at) = 9 AND YEAR(tc.created_at) = ? AND tc.tick_status = 6 THEN tc.ticket_id END) as sep_selesai,

                COUNT(DISTINCT CASE WHEN MONTH(tc.created_at) = 10 AND YEAR(tc.created_at) = ? THEN tc.ticket_id END) as okt_aduan,
                COUNT(DISTINCT CASE WHEN MONTH(tc.created_at) = 10 AND YEAR(tc.created_at) = ? AND tc.tick_status = 6 THEN tc.ticket_id END) as okt_selesai,

                COUNT(DISTINCT CASE WHEN MONTH(tc.created_at) = 11 AND YEAR(tc.created_at) = ? THEN tc.ticket_id END) as nov_aduan,
                COUNT(DISTINCT CASE WHEN MONTH(tc.created_at) = 11 AND YEAR(tc.created_at) = ? AND tc.tick_status = 6 THEN tc.ticket_id END) as nov_selesai,

                COUNT(DISTINCT CASE WHEN MONTH(tc.created_at) = 12 AND YEAR(tc.created_at) = ? THEN tc.ticket_id END) as dis_aduan,
                COUNT(DISTINCT CASE WHEN MONTH(tc.created_at) = 12 AND YEAR(tc.created_at) = ? AND tc.tick_status = 6 THEN tc.ticket_id END) as dis_selesai,

                COUNT(DISTINCT CASE WHEN YEAR(tc.created_at) = ? THEN tc.ticket_id END) as jumlah_aduan,
                COUNT(DISTINCT CASE WHEN YEAR(tc.created_at) = ? AND tc.tick_status = 6 THEN tc.ticket_id END) as jumlah_selesai
            ", array_fill(0, 26, $year))
            ->whereNull('rc.deleted_at')
            ->groupBy('rc.id', 'rc.cat_name')
            ->orderBy('rc.cat_name');

        if (!empty($categoryFilter)) {
            $query->whereIn('rc.id', (array) $categoryFilter);
        }

        if (!empty($monthsFilter)) {
            $query->whereIn(DB::raw('MONTH(tc.created_at)'), $monthsFilter);
        }

        return $query->get();
    }

    /**
     * Calculate totals for all categories
     */
    private function calculateTotals($data): array
    {
        $months = ['jan', 'feb', 'mac', 'apr', 'mei', 'jun', 'jul', 'ogos', 'sep', 'okt', 'nov', 'dis', 'jumlah'];
        $totals = [];

        foreach ($months as $month) {
            $totals[$month . '_aduan'] = $data->sum($month . '_aduan');
            $totals[$month . '_selesai'] = $data->sum($month . '_selesai');
        }

        return $totals;
    }

    /**
     * Calculate percentage
     */
    private function calculatePersen(int $selesai, int $aduan): string
    {
        if ($aduan <= 0) {
            return '-';
        }
        return round(($selesai / $aduan) * 100, 2) . '%';
    }
}