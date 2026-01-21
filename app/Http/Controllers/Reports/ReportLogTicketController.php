<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Officer;
use App\Models\Report;
use App\Models\RefBahagian;
use App\Models\RefCategory;
use App\Models\RefSubCategory;
use App\Models\RefTicketStatus;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Response;

class ReportLogTicketController extends Controller
{
    public function index()
    {
        // Calculate total tickets and resolved percentage
        $totalTickets = Ticket::count();
        // Status ID 6 = 'Aduan Selesai' (Resolved/Completed)
        $resolvedTickets = Ticket::where('tick_status', 6)->count();

        $resolvedPercentage = $totalTickets > 0 ? round(($resolvedTickets / $totalTickets) * 100, 2) : 0;

        return view('report.index', compact('totalTickets', 'resolvedTickets', 'resolvedPercentage'));
    }    public function logTicket()
    {
        $categories = RefCategory::orderBy('cat_name', 'asc')->get();
        $subCategories = RefSubCategory::orderBy('subcat_name', 'asc')->get();
        $statuses = RefTicketStatus::orderBy('st_name', 'asc')->get();
        $officers = Officer::with('user')
            ->whereHas('user')
            ->get()
            ->unique('user_id')
            ->sortBy('user.nama')
            ->pluck('user.nama', 'user_id');
        $bahagians = RefBahagian::where('status', 'active')
            ->orderBy('nama_bah', 'asc')
            ->get();

        $totalTickets = Ticket::count();

        // Get resolved status ID
        $resolvedStatus = RefTicketStatus::where('st_name', 'Aduan Selesai')->first();
        $resolvedTickets = $resolvedStatus ? Ticket::where('tick_status', $resolvedStatus->id)->count() : 0;

        // Calculate percentage
        $resolvedPercentage = $totalTickets > 0 ? round(($resolvedTickets / $totalTickets) * 100, 2) : 0;

        return view('report.log-ticket', compact('categories', 'subCategories', 'officers', 'bahagians', 'statuses', 'totalTickets', 'resolvedTickets', 'resolvedPercentage'));
    }

    public function data(Request $request)
    {
        $reports = Report::query();

        return DataTables::of($reports)
            ->addColumn('action', function ($report) {
                return '<button class="btn btn-sm btn-primary">View</button>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function logTicketData(Request $request)
    {
        $query = Ticket::with([
            'user.bahagian',
            'category',
            'subCategory',
            'status'
        ]);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('tick_status', $request->status);
        }

        if ($request->filled('category')) {
            $query->where('cat_id', $request->category);
        }

        if ($request->filled('subcategory')) {
            $query->where('subcat_id', $request->subcategory);
        }

        if ($request->filled('officer')) {
            $query->where(function($q) use ($request) {
                $q->whereHas('category.officers', function($sq) use ($request) {
                    $sq->where('user_id', $request->officer);
                })->orWhereHas('subCategory.officers', function($sq) use ($request) {
                    $sq->where('user_id', $request->officer);
                });
            });
        }

        if ($request->filled('bahagian')) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('kod_bah', $request->bahagian);
            });
        }

        if ($request->filled('month')) {
            $query->whereMonth('created_at', $request->month);
        }

        if ($request->filled('year')) {
            $query->whereYear('created_at', $request->year);
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('pengadu', function ($ticket) {
                return $ticket->user ? $ticket->user->nama : '-';
            })
            ->addColumn('bahagian', function ($ticket) {
                return $ticket->user && $ticket->user->bahagian ? $ticket->user->bahagian->nama_bah : '-';
            })
            ->addColumn('tarikh_tiket', function ($ticket) {
                if (!$ticket->created_at) return '-';
                $bulan = [
                    1 => 'JANUARI', 2 => 'FEBRUARI', 3 => 'MAC', 4 => 'APRIL',
                    5 => 'MEI', 6 => 'JUN', 7 => 'JULAI', 8 => 'OGOS',
                    9 => 'SEPTEMBER', 10 => 'OKTOBER', 11 => 'NOVEMBER', 12 => 'DISEMBER'
                ];
                return $ticket->created_at->format('d') . ' ' . $bulan[$ticket->created_at->month] . ' ' . $ticket->created_at->format('Y');
            })
            ->addColumn('status_badge', function ($ticket) {
                $status = $ticket->status;
                if (!$status) return '<span class="badge bg-label-secondary">-</span>';

                $badgeClass = 'bg-label-secondary';
                // Map status IDs to badge classes
                switch ($ticket->tick_status) {
                    case 1: // Aduan Baru
                        $badgeClass = 'bg-label-info';
                        break;
                    case 2: // Aduan Diproses
                        $badgeClass = 'bg-label-warning';
                        break;
                    case 6: // Aduan Selesai
                        $badgeClass = 'bg-label-success';
                        break;
                    case 5: // Aduan DiBatal
                        $badgeClass = 'bg-label-dark';
                        break;
                }

                return '<span class="badge ' . $badgeClass . '">' . $status->st_name . '</span>';
            })
            ->addColumn('kategori', function ($ticket) {
                return $ticket->category ? $ticket->category->cat_name : '-';
            })
            ->addColumn('sub_kategori', function ($ticket) {
                return $ticket->subCategory ? $ticket->subCategory->subcat_name : '-';
            })
            ->addColumn('pegawai_tugasan', function ($ticket) {
                // Get officers from subcategory first, then category
                $officers = collect();

                if ($ticket->subCategory) {
                    $officers = Officer::with('user')
                        ->where('subcat_id', $ticket->subcat_id)
                        ->whereHas('user')
                        ->get();
                }

                if ($officers->isEmpty() && $ticket->category) {
                    $officers = Officer::with('user')
                        ->where('cat_id', $ticket->cat_id)
                        ->whereNull('subcat_id')
                        ->whereHas('user')
                        ->get();
                }

                if ($officers->isEmpty()) return '-';

                return $officers->map(function($officer) {
                    return $officer->user->nama;
                })->implode(', ');
            })
            ->addColumn('severity', function ($ticket) {
                // You can customize this based on your severity logic
                return $ticket->ticket_type === 'urgent' ?
                    '<span class="badge bg-label-danger">HIGH</span>' :
                    '<span class="badge bg-label-warning">MEDIUM</span>';
            })
            ->addColumn('sla_days', function ($ticket) {
                if (!$ticket->tick_duedate) return '-';

                $now = now();
                $dueDate = $ticket->tick_duedate;
                $daysDiff = $now->diffInDays($dueDate, false);

                return round($daysDiff) . ' hari';
            })
            ->rawColumns(['status_badge', 'severity'])
            ->make(true);
    }

    public function exportExcel(Request $request)
    {
        try {
            $query = Ticket::with([
                'user.bahagian',
                'category',
                'subCategory',
                'status'
            ]);

            // Apply same filters as logTicketData
            if ($request->filled('status')) {
                $query->where('tick_status', $request->status);
            }

            if ($request->filled('category')) {
                $query->where('cat_id', $request->category);
            }

            if ($request->filled('subcategory')) {
                $query->where('subcat_id', $request->subcategory);
            }

            if ($request->filled('officer')) {
                $query->where(function($q) use ($request) {
                    $q->whereHas('category.officers', function($sq) use ($request) {
                        $sq->where('user_id', $request->officer);
                    })->orWhereHas('subCategory.officers', function($sq) use ($request) {
                        $sq->where('user_id', $request->officer);
                    });
                });
            }

            if ($request->filled('bahagian')) {
                $query->whereHas('user', function($q) use ($request) {
                    $q->where('kod_bah', $request->bahagian);
                });
            }

            if ($request->filled('month')) {
                $query->whereMonth('created_at', $request->month);
            }

            if ($request->filled('year')) {
                $query->whereYear('created_at', $request->year);
            }

            $tickets = $query->get();

            // Calculate statistics
            $totalTickets = $tickets->count();
            // Status ID 6 = 'Aduan Selesai' (Resolved/Completed)
            $resolvedTickets = $tickets->filter(function($ticket) {
                return $ticket->tick_status == 6;
            })->count();
            $resolvedPercentage = $totalTickets > 0 ? round(($resolvedTickets / $totalTickets) * 100, 2) : 0;

            // Create CSV content
            $filename = 'senarai-log-tiket-' . date('Y-m-d-His') . '.csv';
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0'
            ];

            $callback = function() use ($tickets, $totalTickets, $resolvedTickets, $resolvedPercentage) {
                $file = fopen('php://output', 'w');
                // Add BOM for UTF-8
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

                // Add summary information
                fputcsv($file, ['SENARAI LOG TIKET']);
                fputcsv($file, ['Tarikh Dijana', date('d-m-Y H:i:s')]);
                fputcsv($file, ['Jumlah Tiket', $totalTickets]);
                fputcsv($file, ['Aduan Selesai', $resolvedTickets]);
                fputcsv($file, ['Aduan Belum Selesai', $totalTickets - $resolvedTickets]);
                fputcsv($file, ['Peratusan Aduan Selesai', $resolvedPercentage . '%']);
                fputcsv($file, []); // Empty row

                // Add headers
                fputcsv($file, [
                    'No. Log Tiket',
                    'Pengadu',
                    'Bahagian',
                    'Tarikh Tiket',
                    'Status',
                    'Kategori',
                    'Sub Kategori',
                    'Pegawai Tugasan',
                    'Hari SLA'
                ]);

                // Add data rows
                foreach ($tickets as $ticket) {
                    // Get officers
                    $officers = collect();
                    if ($ticket->subCategory) {
                        $officers = Officer::with('user')
                            ->where('subcat_id', $ticket->subcat_id)
                            ->whereHas('user')
                            ->get();
                    }
                    if ($officers->isEmpty() && $ticket->category) {
                        $officers = Officer::with('user')
                            ->where('cat_id', $ticket->cat_id)
                            ->whereNull('subcat_id')
                            ->whereHas('user')
                            ->get();
                    }
                    $officerNames = $officers->isEmpty() ? '-' : $officers->map(fn($o) => $o->user->nama)->implode(', ');

                    // Calculate SLA days
                    $slaDays = '-';
                    if ($ticket->tick_duedate) {
                        $daysDiff = now()->diffInDays($ticket->tick_duedate, false);
                        $slaDays = round($daysDiff) . ' hari';
                    }

                    // Format Tarikh Tiket in Malay
                    $tarikhTiket = '-';
                    if ($ticket->created_at) {
                        $bulan = [
                            1 => 'JANUARI', 2 => 'FEBRUARI', 3 => 'MAC', 4 => 'APRIL',
                            5 => 'MEI', 6 => 'JUN', 7 => 'JULAI', 8 => 'OGOS',
                            9 => 'SEPTEMBER', 10 => 'OKTOBER', 11 => 'NOVEMBER', 12 => 'DISEMBER'
                        ];
                        $tarikhTiket = $ticket->created_at->format('d') . ' ' . $bulan[$ticket->created_at->month] . ' ' . $ticket->created_at->format('Y');
                    }

                    fputcsv($file, [
                        $ticket->ticket_nolog,
                        $ticket->user ? $ticket->user->nama : '-',
                        $ticket->user && $ticket->user->bahagian ? $ticket->user->bahagian->nama_bah : '-',
                        $tarikhTiket,
                        $ticket->status ? $ticket->status->st_name : '-',
                        $ticket->category ? $ticket->category->cat_name : '-',
                        $ticket->subCategory ? $ticket->subCategory->subcat_name : '-',
                        $officerNames,
                        $slaDays
                    ]);
                }

                fclose($file);
            };

            return Response::stream($callback, 200, $headers);

        } catch (\Exception $e) {
            Log::error('Excel export error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Ralat semasa eksport data: ' . $e->getMessage());
        }
    }

    public function exportPdf(Request $request)
    {
        try {
            $query = Ticket::with([
                'user.bahagian',
                'category',
                'subCategory',
                'status'
            ]);

            // Apply same filters as logTicketData
            if ($request->filled('status')) {
                $query->where('tick_status', $request->status);
            }

            if ($request->filled('category')) {
                $query->where('cat_id', $request->category);
            }

            if ($request->filled('subcategory')) {
                $query->where('subcat_id', $request->subcategory);
            }

            if ($request->filled('officer')) {
                $query->where(function($q) use ($request) {
                    $q->whereHas('category.officers', function($sq) use ($request) {
                        $sq->where('user_id', $request->officer);
                    })->orWhereHas('subCategory.officers', function($sq) use ($request) {
                        $sq->where('user_id', $request->officer);
                    });
                });
            }

            if ($request->filled('bahagian')) {
                $query->whereHas('user', function($q) use ($request) {
                    $q->where('kod_bah', $request->bahagian);
                });
            }

            if ($request->filled('month')) {
                $query->whereMonth('created_at', $request->month);
            }

            if ($request->filled('year')) {
                $query->whereYear('created_at', $request->year);
            }

            $tickets = $query->get();
            $totalTickets = $tickets->count();
            // Status ID 6 = 'Aduan Selesai' (Resolved/Completed)
            $resolvedTickets = $tickets->filter(function($ticket) {
                return $ticket->tick_status == 6;
            })->count();
            $resolvedPercentage = $totalTickets > 0 ? round(($resolvedTickets / $totalTickets) * 100, 2) : 0;
            $generatedDate = now()->format('d/m/Y H:i:s');

            // Generate HTML for PDF
            $html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Senarai Log Tiket</title>
    <style>
@page{ size: landscape; margin: 20px; }
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 15px;
            background-color: #f8f9fa;
        }
        .header {
            background-color: #4CAF50;
            color: white;
            padding: 15px;
            text-align: center;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        h1 {
            font-size: 16px;
            margin: 0 0 8px 0;
            font-weight: bold;
        }
        .info {
            background-color: white;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
            border: 1px solid #dee2e6;
            text-align: center;
            font-size: 9px;
        }
        .stats {
            display: flex;
            justify-content: space-around;
            margin-bottom: 15px;
            gap: 10px;
        }
        .stat-card {
            background: white;
            border-radius: 6px;
            padding: 10px;
            flex: 1;
            text-align: center;
            border: 2px solid #e9ecef;
        }
        .stat-label {
            font-size: 8px;
            color: #6c757d;
            text-transform: uppercase;
            font-weight: bold;
        }
        .stat-value {
            font-size: 18px;
            font-weight: bold;
            color: #495057;
            margin-top: 3px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            background: white;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
        }
        th {
            background-color: #4CAF50;
            color: white;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .footer {
            margin-top: 20px;
            padding-top: 12px;
            border-top: 1px solid #dee2e6;
            text-align: center;
            font-size: 8px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>SENARAI LOG TIKET</h1>
        <p style="margin: 0; font-size: 9px;">Tarikh Dijana: ' . $generatedDate . '</p>
    </div>

    <div class="stats">
        <div class="stat-card">
            <div class="stat-label">Jumlah Tiket</div>
            <div class="stat-value">' . $totalTickets . '</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Aduan Selesai</div>
            <div class="stat-value">' . $resolvedTickets . '</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Peratusan</div>
            <div class="stat-value">' . $resolvedPercentage . '%</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Belum Selesai</div>
            <div class="stat-value">' . ($totalTickets - $resolvedTickets) . '</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">Bil</th>
                <th style="width: 10%;">No. Log Tiket</th>
                <th style="width: 12%;">Pengadu</th>
                <th style="width: 10%;">Bahagian</th>
                <th style="width: 12%;">Tarikh Tiket</th>
                <th style="width: 8%;">Status</th>
                <th style="width: 12%;">Kategori</th>
                <th style="width: 12%;">Sub Kategori</th>
                <th style="width: 12%;">Pegawai Tugasan</th>
            </tr>
        </thead>
        <tbody>';

            $bil = 1;
            foreach ($tickets as $ticket) {
                // Get officers
                $officers = collect();
                if ($ticket->subCategory) {
                    $officers = Officer::with('user')
                        ->where('subcat_id', $ticket->subcat_id)
                        ->whereHas('user')
                        ->get();
                }
                if ($officers->isEmpty() && $ticket->category) {
                    $officers = Officer::with('user')
                        ->where('cat_id', $ticket->cat_id)
                        ->whereNull('subcat_id')
                        ->whereHas('user')
                        ->get();
                }
                $officerNames = $officers->isEmpty() ? '-' : $officers->map(fn($o) => $o->user->nama)->implode(', ');

                // Format Tarikh Tiket in Malay
                $tarikhTiket = '-';
                if ($ticket->created_at) {
                    $bulan = [
                        1 => 'JANUARI', 2 => 'FEBRUARI', 3 => 'MAC', 4 => 'APRIL',
                        5 => 'MEI', 6 => 'JUN', 7 => 'JULAI', 8 => 'OGOS',
                        9 => 'SEPTEMBER', 10 => 'OKTOBER', 11 => 'NOVEMBER', 12 => 'DISEMBER'
                    ];
                    $tarikhTiket = $ticket->created_at->format('d') . ' ' . $bulan[$ticket->created_at->month] . ' ' . $ticket->created_at->format('Y');
                }

                $html .= '
            <tr>
                <td>' . $bil++ . '</td>
                <td>' . htmlspecialchars($ticket->ticket_nolog) . '</td>
                <td>' . htmlspecialchars($ticket->user ? $ticket->user->nama : '-') . '</td>
                <td>' . htmlspecialchars($ticket->user && $ticket->user->bahagian ? $ticket->user->bahagian->nama_bah : '-') . '</td>
                <td>' . htmlspecialchars($tarikhTiket) . '</td>
                <td>' . htmlspecialchars($ticket->status ? $ticket->status->st_name : '-') . '</td>
                <td>' . htmlspecialchars($ticket->category ? $ticket->category->cat_name : '-') . '</td>
                <td>' . htmlspecialchars($ticket->subCategory ? $ticket->subCategory->subcat_name : '-') . '</td>
                <td>' . htmlspecialchars($officerNames) . '</td>
            </tr>';
            }

            $html .= '
        </tbody>
    </table>';

            $html .= '
    <div class="footer">
        <p><strong>Sistem eHelp</strong> - Dokumen ini dijana secara automatik</p>
    </div>
</body>
</html>';

            // Return HTML response that will be printed to PDF
            return response($html)
                ->header('Content-Type', 'text/html; charset=UTF-8');

        } catch (\Exception $e) {
            Log::error('PDF export error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Ralat semasa eksport PDF: ' . $e->getMessage());
        }
    }
}
