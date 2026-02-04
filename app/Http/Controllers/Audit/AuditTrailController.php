<?php

namespace App\Http\Controllers\Audit;

use App\Http\Controllers\Controller;
use OwenIt\Auditing\Models\Audit;
use Illuminate\Http\Request;

class AuditTrailController extends Controller
{
    public function index(Request $request)
    {
        // Get users for filter dropdown
        $users = \App\Models\User::orderBy('nama')->get();
        
        $query = Audit::with('user');

        // Filter Tarikh
        if ($request->date_range) {
            [$start, $end] = explode(' - ', $request->date_range);

            $query->whereBetween('created_at', [
                date('d-m-Y 00:00:00', strtotime($start)),
                date('d-m-Y 23:59:59', strtotime($end)),
            ]);
        }

        // Filter Model
        if ($request->model) {
            $query->where('auditable_type', $request->model);
        }

        // Filter User
        if ($request->username) {
            $query->whereHas('user', function($q) use ($request){
                $q->where('nama', 'LIKE', "%{$request->username}%");
            });
        }

        $audits = $query->orderBy('created_at','desc')->get();

        return view('audit.index', compact('audits', 'users'));
    }
    public function getAuditData(Request $request)
    {
        $query = Audit::with('user')
            ->whereIn('auditable_type', [
                'App\\Models\\Ticket',
                'App\\Models\\TicketChronology', 
                'App\\Models\\KnowledgeBase',
                'App\\Models\\RefCategory',
                'App\\Models\\RefSubCategory',
            ])
            ->orderBy('created_at', 'desc');

        // Filter Tarikh
        if ($request->date_from && $request->date_to) {
            $query->whereBetween('created_at', [
                date('Y-m-d 00:00:00', strtotime($request->date_from)),
                date('Y-m-d 23:59:59', strtotime($request->date_to)),
            ]);
        }

        // Filter Model
        if ($request->model) {
            $modelMap = [
                'Ticket' => 'App\\Models\\Ticket',
                'TicketChronology' => 'App\\Models\\TicketChronology',
                'KnowledgeBase' => 'App\\Models\\KnowledgeBase',
                'RefCategory' => 'App\\Models\\RefCategory',
                'RefSubCategory' => 'App\\Models\\RefSubCategory',
            ];
            
            if (isset($modelMap[$request->model])) {
                $query->where('auditable_type', $modelMap[$request->model]);
            }
        }

        // Filter User
        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        return datatables()->of($query)
            ->addIndexColumn()
            ->addColumn('user_name', function($audit) {
                return $audit->user ? $audit->user->nama : 'System';
            })
            ->addColumn('model_display', function ($audit) {
                $modelName = class_basename($audit->auditable_type);
                $badgeClass = '';
                switch($modelName) {
                    case 'Ticket':
                        $badgeClass = 'model-ticket';
                        break;
                    case 'TicketChronology':
                        $badgeClass = 'model-chronology';
                        break;
                    case 'KnowledgeBase':
                        $badgeClass = 'model-knowledgebase';
                        break;
                    case 'RefCategory':
                        $badgeClass = 'model-refcategory';
                        break;
                    case 'RefSubCategory':
                        $badgeClass = 'model-refsubcategory';
                        break;
                }
                return '<span class="badge ' . $badgeClass . '">' . $modelName . '</span>';
            })
            ->editColumn('created_at', function ($audit) {
                return $audit->created_at->format('d/m/Y H:i:s');
            })
            ->editColumn('event', function ($audit) {
                $badgeClass = 'activity-' . $audit->event;
                return '<span class="badge activity-badge ' . $badgeClass . '">' . ucfirst($audit->event) . '</span>';
            })
            ->editColumn('url', function ($audit) {
                return $audit->url ? (strlen($audit->url) > 50 ? substr($audit->url, 0, 50) . '...' : $audit->url) : '-';
            })
            ->addColumn('old_values_preview', function ($audit) {
                if (!$audit->old_values || empty($audit->old_values)) {
                    return '<span class="text-muted">-</span>';
                }
                $preview = collect($audit->old_values)->take(2)->map(function($value, $key) {
                    return $key . ': ' . (is_string($value) ? substr($value, 0, 20) : $value);
                })->join('<br>');
                
                if (count($audit->old_values) > 2) {
                    $preview .= '<br><small class="text-muted">...dan ' . (count($audit->old_values) - 2) . ' lagi</small>';
                }
                
                return '<div class="json-display">' . $preview . 
                       '<br><button class="btn btn-sm btn-outline-info mt-1" onclick="showAuditDetail(' . $audit->id . ')">Lihat Detail</button></div>';
            })
            ->addColumn('new_values_preview', function ($audit) {
                if (!$audit->new_values || empty($audit->new_values)) {
                    return '<span class="text-muted">-</span>';
                }
                $preview = collect($audit->new_values)->take(2)->map(function($value, $key) {
                    return $key . ': ' . (is_string($value) ? substr($value, 0, 20) : $value);
                })->join('<br>');
                
                if (count($audit->new_values) > 2) {
                    $preview .= '<br><small class="text-muted">...dan ' . (count($audit->new_values) - 2) . ' lagi</small>';
                }
                
                return '<div class="json-display">' . $preview . 
                       '<br><button class="btn btn-sm btn-outline-info mt-1" onclick="showAuditDetail(' . $audit->id . ')">Lihat Detail</button></div>';
            })
            ->rawColumns(['model_display', 'event', 'old_values_preview', 'new_values_preview'])
            ->make(true);
    }

    /**
     * Get audit detail for modal
     */
    public function getAuditDetail($id)
    {
        $audit = Audit::with('user')->findOrFail($id);
        
        return response()->json([
            'id' => $audit->id,
            'event' => $audit->event,
            'user' => $audit->user,
            'auditable_type' => $audit->auditable_type,
            'auditable_id' => $audit->auditable_id,
            'old_values' => $audit->old_values,
            'new_values' => $audit->new_values,
            'url' => $audit->url,
            'ip_address' => $audit->ip_address,
            'user_agent' => $audit->user_agent,
            'created_at' => $audit->created_at,
        ]);
    }
}