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

        // Subquery untuk ambil rekod chronology dengan status SELESAI (status 6)
        // Filter berdasarkan tarikh ticket dicipta (konsisten dengan LaporanStatusAduanTahunan)
        $chronologySelesai = DB::table('tickets_chronologies as tc')
            ->where('tick_status', 6)     // Hanya status SELESAI
            ->whereNull('deleted_at')
            ->select(
                'ticket_id',
                'tick_status',
                'created_at as tarikh_chronology',
                'updated_at as tarikh_updated',
                'escalate_to'
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


        // Apply filters - GUNA t.created_at (bila ticket dicipta)
        if ($from) {
            $base->whereDate('t.created_at', '>=', $from);
        }

        if ($to) {
            $base->whereDate('t.created_at', '<=', $to);
        }

        if ($catId) {
            $base->where('t.cat_id', $catId);
        }

        $rows = $base->get();

        // Count jumlah tickets yang tidak soft-deleted
        $jumlahTickets = DB::table('tickets')
            ->whereNull('deleted_at');

        // Apply date filter pada created_at tickets
        if ($from) {
            $jumlahTickets->whereDate('created_at', '>=', $from);
        }

        if ($to) {
            $jumlahTickets->whereDate('created_at', '<=', $to);
        }

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

        // Apply same date filters
        if ($from) {
            $jumlahPerCategory->whereDate('t.created_at', '>=', $from);
        }

        if ($to) {
            $jumlahPerCategory->whereDate('t.created_at', '<=', $to);
        }

        if ($catId) {
            $jumlahPerCategory->where('t.cat_id', $catId);
        }

        $jumlahPerCategory = $jumlahPerCategory->get()->keyBy('cat_name');

        // ================= KPI =================
        $kpi = [
            'jumlah'  => $jumlahTotal,
            'selesai' => $rows->unique('id')->count(), // Count DISTINCT ticket_id
            'purata'  => round($rows->avg('tempoh_minit'), 2),
            'purata_jam'  => round($rows->avg('tempoh_jam'), 2),    
        ];

        // ================= TABLE =================
        $table = $rows
            ->groupBy('cat_name')
            ->map(function ($items, $cat) use ($jumlahPerCategory) {
                return [
                    'kategori' => $cat,
                    'selesai'  => $items->unique('id')->count(), // Count DISTINCT ticket_id
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
        // Ambil semua status bukan rekod pertama overall
        $chronologySelesai = DB::table(DB::raw('(
            SELECT 
                tc.*,
                ROW_NUMBER() OVER (PARTITION BY tc.ticket_id ORDER BY tc.created_at ASC) as row_num
            FROM tickets_chronologies tc
            WHERE tc.deleted_at IS NULL
        ) as ranked_tc'))
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
            // Join dengan rekod chronology (kecuali yang pertama overall)
            ->joinSub($chronologySelesai, 'tc2', function ($join) {
                $join->on('t.id', '=', 'tc2.ticket_id');
            })

            ->join('ref_categories as c', 'c.id', '=', 't.cat_id')
            ->join('users as u', 'u.id', '=', 't.user_id')

            ->leftJoin('ref_bahagian as d', 'd.kod_bah', '=', 'u.kod_bah')

            ->leftJoin('users as tech', function ($j) {
                $j->on('tech.id', '=', 'tc2.escalate_to');
            })

            ->leftJoin('ref_ticket_statuses as s', 's.id', '=', 'tc2.tick_status')

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

                // Tarikh status terakhir (dari tc2)
                DB::raw('tc2.tarikh_chronology as tarikh_selesai'),

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


        // Apply filters - GUNA t.created_at (bila ticket dicipta)
        if ($request->from) {
            $query->whereDate('t.created_at', '>=', $request->from);
        }
        if ($request->to) {
            $query->whereDate('t.created_at', '<=', $request->to);
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
                // Tentukan warna berdasarkan status
                $statusClass = 'text-secondary'; // default
                switch($r->chronology_status) {
                    case 1: $statusClass = 'text-info'; break;      // Baru
                    case 2: $statusClass = 'text-warning'; break;   // Dalam Proses
                    case 3: $statusClass = 'text-secondary'; break; // Pending
                    case 4: $statusClass = 'text-purple'; break;    // Escalate
                    case 5: $statusClass = 'text-danger'; break;    // Reject
                    case 6: $statusClass = 'text-success'; break;   // Selesai
                }
                
                $statusLabel = $r->status ?? 'Status ' . $r->chronology_status;
                
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