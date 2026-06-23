<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Officer;
use App\Models\RefBahagian;
use App\Models\RefCategory;
use App\Models\RefSubCategory;
use App\Models\RefTicketStatus;
use App\Models\Report;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Yajra\DataTables\Facades\DataTables;

class ReportLogTicketController extends Controller
{
    /**
     * Apply common filters to ticket query
     *
     * @param  Builder  $query
     * @return Builder
     */
    private function applyFilters($query, Request $request)
    {
        if ($request->filled('status')) {
            $query->where('tick_status', (int) $request->status);
        }

        if ($request->filled('category')) {
            $query->where('cat_id', (int) $request->category);
        }

        if ($request->filled('subcategory')) {
            $query->where('subcat_id', (int) $request->subcategory);
        }

        if ($request->filled('officer')) {
            $officerId = (int) $request->officer;
            $query->where(function ($q) use ($officerId) {
                $q->whereHas('category.officers', function ($sq) use ($officerId) {
                    $sq->where('user_id', $officerId);
                })->orWhereHas('subCategory.officers', function ($sq) use ($officerId) {
                    $sq->where('user_id', $officerId);
                });
            });
        }

        if ($request->filled('bahagian')) {
            $bahagian = e($request->bahagian);
            $query->whereHas('user', function ($q) use ($bahagian) {
                $q->where('kod_bah', $bahagian);
            });
        }

        if ($request->filled('month')) {
            $month = (int) $request->month;
            if ($month >= 1 && $month <= 12) {
                $query->whereMonth('created_at', $month);
            }
        }

        if ($request->filled('year')) {
            $year = (int) $request->year;
            if ($year >= 2000 && $year <= 2100) {
                $query->whereYear('created_at', $year);
            }
        }

        return $query;
    }

    /**
     * Get assigned officer names for a ticket from its chronologies.
     *
     * @param  Ticket  $ticket
     */
    private function getAssignedOfficerNames($ticket): string
    {
        $names = $ticket->chronologies
            ->whereNotNull('escalate_to')
            ->where('tick_status', $ticket->tick_status)
            ->map(fn ($c) => $c->escalatedTo?->nama)
            ->filter()
            ->unique()
            ->values();

        return $names->isEmpty() ? '-' : $names->implode(', ');
    }

    /**
     * Format date in Malay
     *
     * @param  Carbon|null  $date
     */
    private function formatMalayDate($date): string
    {
        if (! $date) {
            return '-';
        }

        $bulan = [
            1 => 'JANUARI', 2 => 'FEBRUARI', 3 => 'MAC', 4 => 'APRIL',
            5 => 'MEI', 6 => 'JUN', 7 => 'JULAI', 8 => 'OGOS',
            9 => 'SEPTEMBER', 10 => 'OKTOBER', 11 => 'NOVEMBER', 12 => 'DISEMBER',
        ];

        return $date->format('d').' '.$bulan[$date->month].' '.$date->format('Y');
    }

    public function index()
    {
        // Calculate total tickets and resolved percentage
        $totalTickets = Ticket::count();
        // Status ID 6 = 'Aduan Selesai' (Resolved/Completed)
        $resolvedTickets = Ticket::where('tick_status', 6)->count();

        $resolvedPercentage = $totalTickets > 0 ? round(($resolvedTickets / $totalTickets) * 100, 2) : 0;

        return view('report.index', compact('totalTickets', 'resolvedTickets', 'resolvedPercentage'));
    }

    public function logTicket()
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
            'status',
            'chronologies.escalatedTo',
        ]);

        // Apply filters using shared method
        $this->applyFilters($query, $request);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('pengadu', function ($ticket) {
                return e($ticket->user?->nama ?? '-');
            })
            ->addColumn('bahagian', function ($ticket) {
                return e($ticket->user?->bahagian?->nama_bah ?? '-');
            })
            ->addColumn('tarikh_tiket', function ($ticket) {
                return $this->formatMalayDate($ticket->created_at);
            })
            ->addColumn('status_badge', function ($ticket) {
                $status = $ticket->status;
                if (! $status) {
                    return '<span class="badge bg-label-secondary">-</span>';
                }

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

                return '<span class="badge '.$badgeClass.'">'.e($status->st_name).'</span>';
            })
            ->addColumn('kategori', function ($ticket) {
                return e($ticket->category?->cat_name ?? '-');
            })
            ->addColumn('sub_kategori', function ($ticket) {
                return e($ticket->subCategory?->subcat_name ?? '-');
            })
            ->addColumn('pegawai_tugasan', function ($ticket) {
                return e($this->getAssignedOfficerNames($ticket));
            })
            ->addColumn('severity', function ($ticket) {
                // You can customize this based on your severity logic
                return $ticket->ticket_type === 'urgent' ?
                    '<span class="badge bg-label-danger">HIGH</span>' :
                    '<span class="badge bg-label-warning">MEDIUM</span>';
            })
            ->addColumn('sla_days', function ($ticket) {
                if (! $ticket->tick_duedate) {
                    return '-';
                }

                $now = now();
                $dueDate = $ticket->tick_duedate;
                $daysDiff = $now->diffInDays($dueDate, false);

                return round($daysDiff).' hari';
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
                'status',
                'chronologies.escalatedTo',
            ]);

            // Apply filters using shared method
            $this->applyFilters($query, $request);

            $tickets = $query->get();

            // Calculate statistics
            $totalTickets = $tickets->count();
            // Status ID 6 = 'Aduan Selesai' (Resolved/Completed)
            $resolvedTickets = $tickets->filter(fn ($ticket) => $ticket->tick_status == 6)->count();
            $resolvedPercentage = $totalTickets > 0 ? round(($resolvedTickets / $totalTickets) * 100, 2) : 0;

            // Create CSV content
            $filename = 'senarai-log-tiket-'.date('Y-m-d-His').'.csv';
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0',
            ];

            $callback = function () use ($tickets, $totalTickets, $resolvedTickets, $resolvedPercentage) {
                $file = fopen('php://output', 'w');
                // Add BOM for UTF-8
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

                // Add summary information
                fputcsv($file, ['SENARAI LOG TIKET']);
                fputcsv($file, ['Tarikh Dijana', date('d-m-Y H:i:s')]);
                fputcsv($file, ['Jumlah Tiket', $totalTickets]);
                fputcsv($file, ['Aduan Selesai', $resolvedTickets]);
                fputcsv($file, ['Aduan Belum Selesai', $totalTickets - $resolvedTickets]);
                fputcsv($file, ['Peratusan Aduan Selesai', $resolvedPercentage.'%']);
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
                    'Hari SLA',
                ]);

                // Add data rows
                foreach ($tickets as $ticket) {
                    $officerNames = $this->getAssignedOfficerNames($ticket);

                    // Calculate SLA days
                    $slaDays = '-';
                    if ($ticket->tick_duedate) {
                        $daysDiff = now()->diffInDays($ticket->tick_duedate, false);
                        $slaDays = round($daysDiff).' hari';
                    }

                    fputcsv($file, [
                        $ticket->ticket_nolog,
                        $ticket->user?->nama ?? '-',
                        $ticket->user?->bahagian?->nama_bah ?? '-',
                        $this->formatMalayDate($ticket->created_at),
                        $ticket->status?->st_name ?? '-',
                        $ticket->category?->cat_name ?? '-',
                        $ticket->subCategory?->subcat_name ?? '-',
                        $officerNames,
                        $slaDays,
                    ]);
                }

                fclose($file);
            };

            return Response::stream($callback, 200, $headers);

        } catch (\Exception $e) {
            Log::error('Excel export error: '.$e->getMessage());

            return redirect()->back()->with('error', 'Ralat semasa eksport data: '.$e->getMessage());
        }
    }

    public function exportPdf(Request $request)
    {
        try {
            $query = Ticket::with([
                'user.bahagian',
                'category',
                'subCategory',
                'status',
                'chronologies.escalatedTo',
            ]);

            // Apply filters using shared method
            $this->applyFilters($query, $request);

            $tickets = $query->get();
            $totalTickets = $tickets->count();
            // Status ID 6 = 'Aduan Selesai' (Resolved/Completed)
            $resolvedTickets = $tickets->filter(fn ($ticket) => $ticket->tick_status == 6)->count();
            $resolvedPercentage = $totalTickets > 0 ? round(($resolvedTickets / $totalTickets) * 100, 2) : 0;
            $generatedDate = now()->format('d/m/Y H:i:s');

            // Process officer names for each ticket
            foreach ($tickets as $ticket) {
                $ticket->officerNames = $this->getAssignedOfficerNames($ticket);
            }

            // Render Blade view for PDF
            $html = view('report.log-ticket-pdf', compact(
                'tickets',
                'totalTickets',
                'resolvedTickets',
                'resolvedPercentage',
                'generatedDate'
            ))->render();

            // Return HTML response that will be printed to PDF
            return response($html)
                ->header('Content-Type', 'text/html; charset=UTF-8');

        } catch (\Exception $e) {
            Log::error('PDF export error: '.$e->getMessage());

            return redirect()->back()->with('error', 'Ralat semasa eksport PDF: '.$e->getMessage());
        }
    }
}
