<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class RekodLaporanController extends Controller
{
    public function index()
    {
        $categories = DB::table('ref_categories')
            ->whereNull('deleted_at')
            ->orderBy('cat_name')
            ->get();

        $statuses = DB::table('ref_ticket_statuses')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get();

        return view('report.rekod-laporan', compact('categories', 'statuses'));
    }

    /**
     * =========================
     * TAB 1 : RINGKASAN
     * =========================
     */
    public function ringkasan(Request $request)
    {
        $from   = $request->from;
        $to     = $request->to;
        $catId  = $request->category;

        // Subquery untuk rank SEMUA rekod chronology untuk setiap ticket
        // Kemudian filter yang status 6 DAN bukan rekod pertama overall
        // SAMA SEPERTI FUNCTION PERINCIAN
        $chronologySelesai = DB::table(DB::raw('(
            SELECT 
                tc.*,
                ROW_NUMBER() OVER (PARTITION BY tc.ticket_id ORDER BY tc.created_at ASC) as row_num
            FROM tickets_chronologies tc
            WHERE tc.deleted_at IS NULL
        ) as ranked_tc'))
        ->where('tick_status', 6)     // Hanya status SELESAI
        ->where('row_num', '>', 1)     // Bukan rekod pertama overall
        ->whereNotNull('escalate_to')
        ->select(
            'ticket_id',
            'tick_status',
            'created_at as tarikh_chronology',
            'updated_at as tarikh_updated'
        );

        $base = DB::table('tickets as t')
            ->joinSub($chronologySelesai, 'tc2', function ($join) {
                $join->on('t.id', '=', 'tc2.ticket_id');
            })
            ->join('ref_categories as c', 'c.id', '=', 't.cat_id')
            ->select(
                'c.cat_name',
                'tc2.tarikh_chronology',
                't.id',
                
                // Aduan diterima (rekod chronology paling awal)
                DB::raw('(
                    SELECT MIN(tc0.created_at)
                    FROM tickets_chronologies tc0
                    WHERE tc0.ticket_id = t.id
                    AND tc0.deleted_at IS NULL
                ) as aduan_diterima'),

                // Tempoh tindakan dalam minit
                DB::raw('TIMESTAMPDIFF(
                    MINUTE,
                    (SELECT MIN(tc_first.created_at) 
                     FROM tickets_chronologies tc_first 
                     WHERE tc_first.ticket_id = t.id 
                     AND tc_first.deleted_at IS NULL),
                    tc2.tarikh_chronology
                ) as tempoh_minit'),
                // Tempoh tindakan dalam jam
                DB::raw('ROUND(TIMESTAMPDIFF(
                    MINUTE,
                    (SELECT MIN(tc_first.created_at)        
                     FROM tickets_chronologies tc_first 
                     WHERE tc_first.ticket_id = t.id 
                     AND tc_first.deleted_at IS NULL),
                    tc2.tarikh_chronology 
                ) / 60, 2) as tempoh_jam'),              
            );


        // Apply filters (sama seperti perincian)
        if ($from) {
            $base->whereDate('tc2.tarikh_chronology', '>=', $from);
        }

        if ($to) {
            $base->whereDate('tc2.tarikh_chronology', '<=', $to);
        }

        if ($catId) {
            $base->where('t.cat_id', $catId);
        }

        $rows = $base->get();

        // Count jumlah tickets yang tidak soft-deleted
        $jumlahTickets = DB::table('tickets')
            ->whereNull('deleted_at');

        if ($catId) {
            $jumlahTickets->where('cat_id', $catId);
        }

        $jumlahTotal = $jumlahTickets->count();

        // Count jumlah per category untuk table
        $jumlahPerCategory = DB::table('tickets as t')
            ->join('ref_categories as c', 'c.id', '=', 't.cat_id')
            ->whereNull('t.deleted_at')
            ->select('c.cat_name', DB::raw('COUNT(*) as total'))
            ->groupBy('c.cat_name');

        if ($catId) {
            $jumlahPerCategory->where('t.cat_id', $catId);
        }

        $jumlahPerCategory = $jumlahPerCategory->get()->keyBy('cat_name');

        // ================= KPI =================
        $kpi = [
            'jumlah'  => $jumlahTotal,
            'selesai' => $rows->count(),
            'purata'  => round($rows->avg('tempoh_minit'), 2),
            'purata_jam'  => round($rows->avg('tempoh_jam'), 2),    
        ];

        // ================= TABLE =================
        $table = $rows
            ->groupBy('cat_name')
            ->map(function ($items, $cat) use ($jumlahPerCategory) {
                return [
                    'kategori' => $cat,
                    'selesai'  => $items->count(),
                    'jumlah'   => $jumlahPerCategory[$cat]->total ?? 0,
                ];
            })
            ->values();

        return response()->json([
            'kpi'   => $kpi,
            'table' => $table,
        ]);
    }

    /**
     * =========================
     * TAB 2 : PERINCIAN
     * =========================
     */
    public function perincian(Request $request)
    {
        // Subquery untuk rank SEMUA rekod chronology untuk setiap ticket
        // Kemudian filter yang status 6 DAN bukan rekod pertama overall
        $chronologySelesai = DB::table(DB::raw('(
            SELECT 
                tc.*,
                ROW_NUMBER() OVER (PARTITION BY tc.ticket_id ORDER BY tc.created_at ASC) as row_num
            FROM tickets_chronologies tc
            WHERE tc.deleted_at IS NULL
        ) as ranked_tc'))
        ->where('tick_status', 6)     // Hanya status SELESAI
        ->where('row_num', '>', 1)     // Bukan rekod pertama overall
        ->whereNotNull('escalate_to')  
        ->select(
            'ticket_id',
            'tick_status',
            'created_at as tarikh_chronology',
            'updated_at as tarikh_updated',
            'escalate_to'
        );

        $query = DB::table('tickets as t')
            // Join dengan rekod SELESAI (kecuali yang pertama overall)
            ->joinSub($chronologySelesai, 'tc2', function ($join) {
                $join->on('t.id', '=', 'tc2.ticket_id');
            })

            ->join('ref_categories as c', 'c.id', '=', 't.cat_id')
            ->join('users as u', 'u.id', '=', 't.user_id')

            ->leftJoin('ref_bahagian as d', 'd.kod_bah', '=', 'u.kod_bah')

            ->leftJoin('users as tech', function ($j) {
                $j->on('tech.id', '=', 'tc2.escalate_to');
            })

            ->leftJoin('ref_ticket_statuses as s', 's.id', '=', 't.tick_status')

            ->select(
                't.id',
                't.ticket_nolog',
                'u.nama as nama_pengadu',
                'tc2.tarikh_chronology as tarikh_mula',  // Alias untuk compatibility dengan JS
                'c.cat_name as cat_name',
                'd.singkat_bah as bahagian',
                's.st_name as status',
                'tech.nama as pegawai_tugasan',
                'tc2.tick_status as chronology_status',

                // Aduan diterima (rekod chronology paling awal)
                DB::raw('(
                    SELECT MIN(tc0.created_at)
                    FROM tickets_chronologies tc0
                    WHERE tc0.ticket_id = t.id
                    AND tc0.deleted_at IS NULL
                ) as aduan_diterima'),

                // Tindakan diambil (rekod kedua - which is tc2)
                DB::raw('tc2.tarikh_updated as tindakan_diambil'),

                // Tarikh selesai (rekod status 6)
                DB::raw('(
                    SELECT tc6.created_at
                    FROM tickets_chronologies tc6
                    WHERE tc6.ticket_id = t.id
                    AND tc6.tick_status = 6
                    AND tc6.deleted_at IS NULL
                    LIMIT 1
                ) as tarikh_selesai'),

                // Tempoh tindakan dalam minit
                DB::raw('TIMESTAMPDIFF(
                    MINUTE,
                    (SELECT MIN(tc_first.created_at) 
                     FROM tickets_chronologies tc_first 
                     WHERE tc_first.ticket_id = t.id 
                     AND tc_first.deleted_at IS NULL),
                    tc2.tarikh_chronology
                ) as tempoh_minit'),

                // Tempoh tindakan dalam jam
                DB::raw('ROUND(TIMESTAMPDIFF(
                    MINUTE,
                    (SELECT MIN(tc_first.created_at) 
                     FROM tickets_chronologies tc_first 
                     WHERE tc_first.ticket_id = t.id 
                     AND tc_first.deleted_at IS NULL),
                    tc2.tarikh_chronology
                ) / 60, 2) as tempoh_jam'),

                // Tempoh tindakan dalam hari
                DB::raw('ROUND(TIMESTAMPDIFF(
                    MINUTE,
                    (SELECT MIN(tc_first.created_at) 
                     FROM tickets_chronologies tc_first 
                     WHERE tc_first.ticket_id = t.id 
                     AND tc_first.deleted_at IS NULL),
                    tc2.tarikh_chronology
                ) / 1440, 2) as tempoh_hari')
            );


        // Apply filters (filter by rekod ke-2 chronology date)
        if ($request->from) {
            $query->whereDate('tc2.tarikh_chronology', '>=', $request->from);
        }
        if ($request->to) {
            $query->whereDate('tc2.tarikh_chronology', '<=', $request->to);
        }
        if ($request->category) {
            $query->where('t.cat_id', $request->category);
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('aduan_diterima', function ($r) {
                return $r->aduan_diterima ? date('d-m-Y h:i:s A', strtotime($r->aduan_diterima)) : '-';
            })
            ->editColumn('tindakan_diambil', function ($r) {
                return $r->tindakan_diambil ? date('d-m-Y h:i:s A', strtotime($r->tindakan_diambil)) : '-';
            })
            ->editColumn('tarikh_mula', function ($r) {
                return date('d-m-Y h:i:s A', strtotime($r->tarikh_mula));
            })
            ->editColumn('tarikh_selesai', function ($r) {
                return $r->tarikh_selesai ? date('d-m-Y h:i:s A', strtotime($r->tarikh_selesai)) : '-';
            })
            ->editColumn('tempoh_minit', function ($r) {
                return $r->tempoh_jam . ' jam';
            })
            ->addColumn('status_display', function ($r) {
                $statusLabel = $r->chronology_status == 6 ? 'Selesai' : 'Status ' . $r->chronology_status;
                $statusClass = $r->chronology_status == 6 ? 'text-success' : 'text-warning';
                
                return '<div class="' . $statusClass . ' fw-bold">' . $statusLabel . '</div>' .
                       '<div class="text-muted small">' . date('d-m-Y : h:i:s A', strtotime($r->tarikh_mula)) . '</div>' .
                       '<div class="text-primary small">Tempoh: ' . ($r->tempoh_minit ?? 0) . ' minit</div>' .
                       '<div class="text-info small">(' . ($r->tempoh_jam ?? 0) . ' jam / ' . ($r->tempoh_hari ?? 0) . ' hari)</div>';
            })
            ->addColumn('action', function ($r) {
                return '<button class="btn btn-sm btn-icon btn-text-secondary rounded-pill" title="Edit">' .
                       '<i class="ri-edit-line ri-20px"></i>' .
                       '</button>' .
                       '<button class="btn btn-sm btn-icon btn-text-secondary rounded-pill" title="Delete">' .
                       '<i class="ri-delete-bin-7-line ri-20px"></i>' .
                       '</button>';
            })
            ->rawColumns(['status_display', 'action'])
            ->make(true);
    }
}