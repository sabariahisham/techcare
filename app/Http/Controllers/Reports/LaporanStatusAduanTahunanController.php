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

        $years = DB::table('tickets_chronologies')
            ->selectRaw('DISTINCT YEAR(created_at) as year')
            ->whereNotNull('created_at')
            ->orderBy('year', 'desc')
            ->pluck('year');

        // Default to current year if no data
        if ($years->isEmpty()) {
            $years = collect([date('Y')]);
        }

        return view('report.laporan-status-aduan', compact('statuses', 'categories', 'years'));
    }

    public function data(Request $request)
    {
        $years   = $request->year;
        $months = $request->months ?? [];

        // Subquery: status TERKINI setiap tiket
        $latestStatus = DB::table('tickets_chronologies as tc1')
            ->select('tc1.ticket_id', 'tc1.tick_status', 'tc1.created_at')
            ->whereRaw('tc1.created_at = (
                SELECT MAX(tc2.created_at)
                FROM tickets_chronologies tc2
                WHERE tc2.ticket_id = tc1.ticket_id
            )');

        if ($years) {
            $latestStatus->whereYear('tc1.created_at', $years);
        }

        if (!empty($months)) {
            $latestStatus->whereIn(DB::raw('MONTH(tc1.created_at)'), $months);
        }

        $data = DB::table('ref_categories as c')
            ->whereNull('c.deleted_at')
            ->leftJoin('tickets as t', 't.cat_id', '=', 'c.id')
            ->leftJoinSub($latestStatus, 'ls', function ($join) {
                $join->on('ls.ticket_id', '=', 't.id');
            })
            ->leftJoin('ref_ticket_statuses as s', function($join) {
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

        // Susun jadi matrix
        $statuses = DB::table('ref_ticket_statuses')->whereNull('deleted_at')->orderBy('id')->get();
        $result   = [];

        foreach ($data as $row) {
            $result[$row->category_id]['cat_name'] = $row->cat_name;
            $result[$row->category_id]['status'][$row->status_id] = $row->total;
        }

        $final = [];
        foreach ($result as $row) {
            $temp = [
                'kategori' => $row['cat_name'],
                'jumlah'   => 0
            ];

            foreach ($statuses as $status) {
                $count = $row['status'][$status->id] ?? 0;
                $temp['status_'.$status->id] = $count;
                $temp['jumlah'] += $count;
            }

            $final[] = $temp;
        }

        return DataTables::of(collect($final))->make(true);
    }
}
