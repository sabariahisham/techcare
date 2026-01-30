<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\RefCategory;
use App\Models\RefTicketStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf as PDF;

class StatistikLogAduanController extends Controller
{
    /**
     * Display the statistics and log complaint report page
     */
    public function index()
    {
        // Get available years from tickets
        $years = DB::table('tickets')
            ->selectRaw('DISTINCT YEAR(created_at) as year')
            ->whereNotNull('created_at')
            ->whereNull('deleted_at')
            ->orderBy('year', 'desc')
            ->pluck('year');

        // Default to current year if no data
        if ($years->isEmpty()) {
            $years = collect([date('Y')]);
        }

        return view('report.statistik-log', compact('years'));
    }

    /**
     * Get chart data for statistics
     */
    public function getChart(Request $request)
    {
        $request->validate([
            'year' => 'required|integer|min:2000|max:2100',
        ]);

        $year = $request->input('year', date('Y'));

        // Get total complaint counts by month only (not by category)
        $data = DB::table('tickets as t')
            ->select(
                DB::raw('MONTH(t.created_at) as bulan'),
                DB::raw('COUNT(t.id) as jumlah')
            )
            ->whereYear('t.created_at', $year)
            ->whereNull('t.deleted_at')
            ->groupBy(DB::raw('MONTH(t.created_at)'))
            ->orderBy('bulan')
            ->get();

        // Initialize data for all months (1-12)
        $monthlyData = array_fill(1, 12, 0);

        // Fill in the actual data
        foreach ($data as $row) {
            $monthlyData[$row->bulan] = (int) $row->jumlah;
        }

        // Professional color palette for each month
        $monthColors = [
            '#0d6efd', // Jan - Blue
            '#6610f2', // Feb - Indigo
            '#6f42c1', // Mac - Purple
            '#d63384', // Apr - Pink
            '#dc3545', // Mei - Red
            '#fd7e14', // Jun - Orange
            '#ffc107', // Jul - Yellow
            '#198754', // Ogos - Green
            '#20c997', // Sep - Teal
            '#0dcaf0', // Okt - Cyan
            '#6c757d', // Nov - Gray
            '#495057', // Dis - Dark Gray
        ];

        // Format for Chart.js (Vertical Bar Chart)
        $response = [
            'labels' => ['Jan', 'Feb', 'Mac', 'Apr', 'Mei', 'Jun', 'Jul', 'Ogos', 'Sep', 'Okt', 'Nov', 'Dis'],
            'datasets' => [
                [
                    'label' => 'Jumlah Aduan',
                    'data' => array_values($monthlyData),
                    'backgroundColor' => $monthColors,
                    'borderColor' => $monthColors,
                    'borderWidth' => 1,
                    'borderRadius' => 5,
                    'hoverBackgroundColor' => array_map(function($color) {
                        return $color . 'cc'; // Add opacity on hover
                    }, $monthColors)
                ]
            ]
        ];

        return response()->json($response);
    }

    /**
     * Get data for DataTable
     */
    public function getData(Request $request)
    {
        $request->validate([
            'year' => 'required|integer|min:2000|max:2100',
        ]);

        $year = $request->input('year', date('Y'));

        // Get all categories
        $categories = RefCategory::whereNull('deleted_at')
            ->orderBy('cat_name')
            ->get();

        // Get data grouped by month and category
        $rawData = DB::table('tickets as t')
            ->join('ref_categories as rc', 't.cat_id', '=', 'rc.id')
            ->select(
                DB::raw('MONTH(t.created_at) as bulan'),
                'rc.id as cat_id',
                'rc.cat_name',
                DB::raw('COUNT(t.id) as jumlah')
            )
            ->whereYear('t.created_at', $year)
            ->whereNull('rc.deleted_at')
            ->whereNull('t.deleted_at')
            ->groupBy(DB::raw('MONTH(t.created_at)'), 'rc.id', 'rc.cat_name')
            ->get();

        // Transpose: Create rows for each month
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Mac', 4 => 'April',
            5 => 'Mei', 6 => 'Jun', 7 => 'Julai', 8 => 'Ogos',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Disember'
        ];

        $transposed = [];
        foreach ($months as $monthNum => $monthName) {
            $row = ['bulan' => $monthName];
            $totalRow = 0;

            foreach ($categories as $category) {
                $count = $rawData->where('bulan', $monthNum)
                    ->where('cat_id', $category->id)
                    ->first();
                
                $value = $count ? $count->jumlah : 0;
                $row['cat_' . $category->id] = $value;
                $totalRow += $value;
            }

            $row['jumlah'] = $totalRow;
            $transposed[] = $row;
        }

        return response()->json([
            'data' => $transposed,
            'categories' => $categories
        ]);
    }

    /**
     * Export to Excel
     */
    public function exportExcel(Request $request)
    {
        $year = $request->input('year', date('Y'));

        // Get all categories
        $categories = RefCategory::whereNull('deleted_at')
            ->orderBy('cat_name')
            ->get();

        // Get data grouped by month and category
        $rawData = DB::table('tickets as t')
            ->join('ref_categories as rc', 't.cat_id', '=', 'rc.id')
            ->select(
                DB::raw('MONTH(t.created_at) as bulan'),
                'rc.id as cat_id',
                'rc.cat_name',
                DB::raw('COUNT(t.id) as jumlah')
            )
            ->whereYear('t.created_at', $year)
            ->whereNull('rc.deleted_at')
            ->whereNull('t.deleted_at')
            ->groupBy(DB::raw('MONTH(t.created_at)'), 'rc.id', 'rc.cat_name')
            ->get();

        // Transpose: Create rows for each month
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Mac', 4 => 'April',
            5 => 'Mei', 6 => 'Jun', 7 => 'Julai', 8 => 'Ogos',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Disember'
        ];

        $transposed = [];
        foreach ($months as $monthNum => $monthName) {
            $row = ['bulan' => $monthName];
            $totalRow = 0;

            foreach ($categories as $category) {
                $count = $rawData->where('bulan', $monthNum)
                    ->where('cat_id', $category->id)
                    ->first();
                
                $value = $count ? $count->jumlah : 0;
                $row['cat_' . $category->id] = $value;
                $totalRow += $value;
            }

            $row['jumlah'] = $totalRow;
            $transposed[] = $row;
        }

        return Excel::download(
            new \App\Exports\StatistikLogAduanExport($transposed, $categories, $year), 
            "Statistik_Log_Aduan_{$year}.xlsx"
        );
    }

    /**
     * Export to PDF
     */
    public function exportPdf(Request $request)
    {
        $year = $request->input('year', date('Y'));

        // Get all categories
        $categories = RefCategory::whereNull('deleted_at')
            ->orderBy('cat_name')
            ->get();

        // Get data grouped by month and category
        $rawData = DB::table('tickets as t')
            ->join('ref_categories as rc', 't.cat_id', '=', 'rc.id')
            ->select(
                DB::raw('MONTH(t.created_at) as bulan'),
                'rc.id as cat_id',
                'rc.cat_name',
                DB::raw('COUNT(t.id) as jumlah')
            )
            ->whereYear('t.created_at', $year)
            ->whereNull('rc.deleted_at')
            ->whereNull('t.deleted_at')
            ->groupBy(DB::raw('MONTH(t.created_at)'), 'rc.id', 'rc.cat_name')
            ->get();

        // Transpose: Create rows for each month
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Mac', 4 => 'April',
            5 => 'Mei', 6 => 'Jun', 7 => 'Julai', 8 => 'Ogos',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Disember'
        ];

        $transposed = [];
        foreach ($months as $monthNum => $monthName) {
            $row = ['bulan' => $monthName];
            $totalRow = 0;

            foreach ($categories as $category) {
                $count = $rawData->where('bulan', $monthNum)
                    ->where('cat_id', $category->id)
                    ->first();
                
                $value = $count ? $count->jumlah : 0;
                $row['cat_' . $category->id] = $value;
                $totalRow += $value;
            }

            $row['jumlah'] = $totalRow;
            $transposed[] = $row;
        }

        $pdf = PDF::loadView('report.statistik-log-pdf', [
            'data' => $transposed,
            'categories' => $categories,
            'year' => $year
        ]);
        $pdf->setPaper('a4', 'landscape');
        
        return $pdf->download("Statistik_Log_Aduan_{$year}.pdf");
    }
}
