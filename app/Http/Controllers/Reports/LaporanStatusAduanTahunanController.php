<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class LaporanStatusAduanTahunanController extends Controller
{
    public function index()
    {
        $statuses = DB::table('ref_ticket_statuses')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get();

        $categories = DB::table('ref_categories')
            ->whereNull('deleted_at')
            ->orderBy('cat_name')
            ->get();

        $years = DB::table('tickets')
            ->selectRaw('DISTINCT YEAR(created_at) as year')
            ->whereNull('deleted_at')
            ->orderBy('year', 'desc')
            ->pluck('year');

        if ($years->isEmpty()) {
            $years = collect([date('Y')]);
        }

        return view('report.laporan-status-aduan', compact(
            'statuses',
            'categories',
            'years'
        ));
    }

    public function data(Request $request)
    {
        $year   = $request->year;
        $months = $request->months ?? [];

        /**
         * SUBQUERY: Ambil STATUS TERKINI sahaja bagi setiap ticket
         * (berdasarkan MAX id dari tickets_chronologies)
         */
        $latestStatus = DB::table('tickets_chronologies as tc1')
            ->select('tc1.*')
            ->join(DB::raw('(
                SELECT ticket_id, MAX(id) AS latest_id
                FROM tickets_chronologies
                WHERE deleted_at IS NULL
                GROUP BY ticket_id
            ) latest'), function($join) {
                $join->on('latest.latest_id', '=', 'tc1.id');
            })
            ->whereNull('tc1.deleted_at');

        /**
         * QUERY UTAMA:
         * Kira jumlah aduan mengikut:
         * - Kategori
         * - Status TERKINI sahaja (dari chronology)
         * - Tahun & Bulan (berdasarkan updated_at dari chronology)
         * 
         * Nota: Setiap ticket dikira SEKALI sahaja mengikut status terkini
         */
        $query = DB::table('tickets as t')
            ->join('ref_categories as c', 'c.id', '=', 't.cat_id')
            ->joinSub($latestStatus, 'tc', function($join) {
                $join->on('tc.ticket_id', '=', 't.id');
            })
            ->whereNull('t.deleted_at')
            ->whereNull('c.deleted_at');

        // Filter Tahun
        if ($year) {
            $query->whereYear('tc.updated_at', $year);
        }

        // Filter Bulan
        if (!empty($months)) {
            $query->whereIn(DB::raw('MONTH(tc.updated_at)'), $months);
        }

        // Aggregate data
        $data = $query->select(
                'c.id as category_id',
                'c.cat_name',
                DB::raw('SUM(CASE WHEN tc.tick_status = 1 THEN 1 ELSE 0 END) AS status_1'),
                DB::raw('SUM(CASE WHEN tc.tick_status = 2 THEN 1 ELSE 0 END) AS status_2'),
                DB::raw('SUM(CASE WHEN tc.tick_status = 3 THEN 1 ELSE 0 END) AS status_3'),
                DB::raw('SUM(CASE WHEN tc.tick_status = 4 THEN 1 ELSE 0 END) AS status_4'),
                DB::raw('SUM(CASE WHEN tc.tick_status = 5 THEN 1 ELSE 0 END) AS status_5'),
                DB::raw('SUM(CASE WHEN tc.tick_status = 6 THEN 1 ELSE 0 END) AS status_6'),
                DB::raw('COUNT(*) AS jumlah')
            )
            ->groupBy('c.id', 'c.cat_name')
            ->orderBy('c.cat_name')
            ->get();

        /**
         * FORMAT AKHIR UNTUK DATATABLE
         */
        $final = [];

        foreach ($data as $row) {
            $final[] = [
                'kategori'  => $row->cat_name,
                'status_1'  => $row->status_1,
                'status_2'  => $row->status_2,
                'status_3'  => $row->status_3,
                'status_4'  => $row->status_4,
                'status_5'  => $row->status_5,
                'status_6'  => $row->status_6,
                'jumlah'    => $row->jumlah,
            ];
        }

        return DataTables::of(collect($final))->make(true);
    }
}
