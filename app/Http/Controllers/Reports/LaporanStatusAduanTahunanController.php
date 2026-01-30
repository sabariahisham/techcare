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
         * SUBQUERY:
         * Ambil STATUS TERKINI bagi setiap ticket
         */
        $latestStatus = DB::table('tickets_chronologies as tc1')
            ->select('tc1.ticket_id', 'tc1.tick_status')
            ->whereNull('tc1.deleted_at')
            ->whereRaw('tc1.created_at = (
                SELECT MAX(tc2.created_at)
                FROM tickets_chronologies tc2
                WHERE tc2.ticket_id = tc1.ticket_id
                  AND tc2.deleted_at IS NULL
            )');

        /**
         * QUERY UTAMA:
         * Kira jumlah aduan mengikut
         * - Kategori
         * - Status terkini
         * - Tahun & Bulan (ikut tarikh tiket dicipta)
         */
        $data = DB::table('ref_categories as c')
            ->whereNull('c.deleted_at')

            ->leftJoin('tickets as t', function ($join) use ($year, $months) {
                $join->on('t.cat_id', '=', 'c.id')
                     ->whereNull('t.deleted_at');

                if ($year) {
                    $join->whereYear('t.created_at', $year);
                }

                if (!empty($months)) {
                    $join->whereIn(DB::raw('MONTH(t.created_at)'), $months);
                }
            })

            ->leftJoinSub($latestStatus, 'ls', function ($join) {
                $join->on('ls.ticket_id', '=', 't.id');
            })

            ->leftJoin('ref_ticket_statuses as s', function ($join) {
                $join->on('s.id', '=', 'ls.tick_status')
                     ->whereNull('s.deleted_at');
            })

            ->select(
                'c.id as category_id',
                'c.cat_name',
                's.id as status_id',
                DB::raw('COUNT(DISTINCT t.id) as total')
            )

            ->groupBy('c.id', 'c.cat_name', 's.id')
            ->orderBy('c.cat_name')
            ->get();

        /**
         * SUSUN DATA JADI MATRIX (Kategori x Status)
         */
        $statuses = DB::table('ref_ticket_statuses')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get();

        $matrix = [];

        foreach ($data as $row) {
            $matrix[$row->category_id]['cat_name'] = $row->cat_name;
            $matrix[$row->category_id]['status'][$row->status_id] = $row->total;
        }

        /**
         * FORMAT AKHIR UNTUK DATATABLE
         */
        $final = [];

        foreach ($matrix as $row) {
            $temp = [
                'kategori' => $row['cat_name'],
                'jumlah'   => 0,
            ];

            foreach ($statuses as $status) {
                $count = $row['status'][$status->id] ?? 0;
                $temp['status_' . $status->id] = $count;
                $temp['jumlah'] += $count;
            }

            $final[] = $temp;
        }

        return DataTables::of(collect($final))->make(true);
    }
}
