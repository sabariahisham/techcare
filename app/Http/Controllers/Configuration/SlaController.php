<?php

namespace App\Http\Controllers\Configuration;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sla;
use App\Models\RefCategory;
use App\Models\RefSubCategory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class SlaController extends Controller
{
    public function index()
    {
        $categories = RefCategory::all();
        $subCategories = RefSubCategory::all();
        $totalCategories = RefCategory::count();
        $totalSubCategories = RefSubCategory::count();
        $totalSlas = Sla::count();

        // Calculate categories with SLA declared
        $categoriesWithSla = RefCategory::has('slas')->count();
        $categoryPercentage = $totalCategories > 0 ? round(($categoriesWithSla / $totalCategories) * 100, 2) : 0;

        // Calculate sub-categories with SLA declared
        $subCategoriesWithSla = RefSubCategory::has('slas')->count();
        $subCategoryPercentage = $totalSubCategories > 0 ? round(($subCategoriesWithSla / $totalSubCategories) * 100, 2) : 0;

        return view('sla-and-severity.index', compact('categories', 'subCategories', 'totalCategories', 'totalSubCategories', 'totalSlas', 'categoriesWithSla', 'categoryPercentage', 'subCategoriesWithSla', 'subCategoryPercentage'));
    }

    public function data(Request $request)
    {
        $slas = Sla::with(['category', 'subCategory'])->get();

        return DataTables::of($slas)
            ->addIndexColumn()
            ->addColumn('cat_name', function ($sla) {
                return $sla->category ? $sla->category->cat_name : '-';
            })
            ->addColumn('subcat_name', function ($sla) {
                return $sla->subCategory ? $sla->subCategory->subcat_name : '-';
            })
            ->editColumn('sla_level', function ($sla) {
                $level = $sla->sla_level;
                $badgeClass = '';
                $badgeText = 'Level ' . $level;

                switch($level) {
                    case '1':
                        $badgeClass = 'bg-primary';
                        break;
                    case '2':
                        $badgeClass = 'bg-info';
                        break;
                    default:
                        $badgeClass = 'bg-secondary';
                        break;
                }

                return '<span class="badge ' . $badgeClass . '">' . $badgeText . '</span>';
            })
            ->editColumn('sla_severity', function ($sla) {
                $severity = strtolower($sla->sla_severity);
                $badgeClass = '';
                $badgeText = ucfirst($sla->sla_severity);

                switch($severity) {
                    case 'high':
                        $badgeClass = 'bg-danger';
                        $badgeText = 'HIGH';
                        break;
                    case 'medium':
                        $badgeClass = 'bg-warning';
                        $badgeText = 'MEDIUM';
                        break;
                    case 'low':
                        $badgeClass = 'bg-success';
                        $badgeText = 'LOW';
                        break;
                    default:
                        $badgeClass = 'bg-secondary';
                        break;
                }

                return '<span class="badge ' . $badgeClass . '">' . $badgeText . '</span>';
            })
            ->editColumn('sla_days', function ($sla) {
                return $sla->sla_days . ' hari';
            })
            ->addColumn('action', function ($sla) {
                $editBtn = '<button type="button" class="btn btn-icon btn-text-secondary rounded-pill btn-sm edit-record" data-id="'.e($sla->id).'" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
                    <i class="bx bx-edit"></i>
                </button>';

                $deleteBtn = '<button type="button" class="btn btn-icon text-danger rounded-pill btn-sm delete-record" data-id="'.e($sla->id).'" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
                    <i class="bx bx-trash"></i>
                </button>';

                return '<div class="d-inline-block text-nowrap">' . $editBtn . $deleteBtn . '</div>';
            })
            ->rawColumns(['action', 'sla_level', 'sla_severity'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'cat_id' => 'required|exists:ref_categories,id',
            'subcat_id' => 'required|exists:ref_sub_categories,id',
            'sla_level' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($request) {
                    $exists = Sla::where('cat_id', $request->cat_id)
                                 ->where('subcat_id', $request->subcat_id)
                                 ->where('sla_level', $value)
                                 ->exists();
                    if ($exists) {
                        $fail('SLA untuk kombinasi ini sudah wujud.');
                    }
                },
            ],
            'sla_days_high' => 'required|integer|min:1',
            'sla_days_medium' => 'required|integer|min:1',
            'sla_days_low' => 'required|integer|min:1',
        ]);

        try {
            // Create three SLA records for High, Medium, and Low severity
            $severities = [
                'High' => $request->sla_days_high,
                'Medium' => $request->sla_days_medium,
                'Low' => $request->sla_days_low,
            ];

            foreach ($severities as $severity => $days) {
                Sla::create([
                    'cat_id' => $request->cat_id,
                    'subcat_id' => $request->subcat_id,
                    'sla_level' => $request->sla_level,
                    'sla_severity' => $severity,
                    'sla_days' => $days,
                    'created_by' => Auth::id(),
                ]);
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Error creating SLA', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);
            return response()->json(['success' => false], 500);
        }
    }

    public function edit(string $id)
    {
        try {
            $sla = Sla::with(['category', 'subCategory'])->findOrFail($id);

            // Get all three severity records for this cat_id, subcat_id, and sla_level
            $slas = Sla::where('cat_id', $sla->cat_id)
                       ->where('subcat_id', $sla->subcat_id)
                       ->where('sla_level', $sla->sla_level)
                       ->get()
                       ->keyBy('sla_severity');

            $response = [
                'id' => $sla->id,
                'cat_id' => $sla->cat_id,
                'subcat_id' => $sla->subcat_id,
                'sla_level' => $sla->sla_level,
                'sla_days_high' => $slas->get('High')->sla_days ?? '',
                'sla_days_medium' => $slas->get('Medium')->sla_days ?? '',
                'sla_days_low' => $slas->get('Low')->sla_days ?? '',
            ];

            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json(['error' => true], 404);
        }
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'cat_id' => 'required|exists:ref_categories,id',
            'subcat_id' => 'required|exists:ref_sub_categories,id',
            'sla_level' => 'required|string|max:255',
            'sla_days_high' => 'required|integer|min:1',
            'sla_days_medium' => 'required|integer|min:1',
            'sla_days_low' => 'required|integer|min:1',
        ]);

        try {
            $sla = Sla::findOrFail($id);

            // Update all three severity records for this combination
            $severities = [
                'High' => $request->sla_days_high,
                'Medium' => $request->sla_days_medium,
                'Low' => $request->sla_days_low,
            ];

            foreach ($severities as $severity => $days) {
                Sla::updateOrCreate(
                    [
                        'cat_id' => $request->cat_id,
                        'subcat_id' => $request->subcat_id,
                        'sla_level' => $request->sla_level,
                        'sla_severity' => $severity,
                    ],
                    [
                        'sla_days' => $days,
                        'updated_by' => Auth::id(),
                    ]
                );
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Error updating SLA', [
                'sla_id' => $id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);
            return response()->json(['success' => false], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $sla = Sla::findOrFail($id);

            // Ensure user is authenticated
            if (!Auth::check()) {
                return response()->json(['success' => false], 401);
            }

            // Set deleted_by before soft delete
            $sla->deleted_by = Auth::id();
            $sla->save();

            // Perform soft delete
            $sla->delete();

            Log::info('SLA deleted successfully', [
                'sla_id' => $id,
                'deleted_by' => Auth::id(),
                'user_id' => Auth::user()->id ?? null,
                'user_name' => Auth::user()->name ?? 'Unknown'
            ]);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Error deleting SLA', [
                'sla_id' => $id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['success' => false], 500);
        }
    }

    public function checkDuplicate(Request $request)
    {
        $request->validate([
            'cat_id' => 'required|integer',
            'subcat_id' => 'required|integer',
            'sla_level' => 'required|string',
        ]);

        // Check for existing SLA with same combination (excluding soft-deleted records)
        $query = Sla::where('cat_id', $request->cat_id)
                     ->where('subcat_id', $request->subcat_id)
                     ->where('sla_level', $request->sla_level);

        // Exclude current record if updating
        if ($request->has('id') && $request->id) {
            // When updating, check if any record exists for this combination
            // but exclude all records from the same group (same cat, subcat, level)
            $sla = Sla::find($request->id);
            if ($sla) {
                $query->where(function($q) use ($sla) {
                    $q->where('cat_id', '!=', $sla->cat_id)
                      ->orWhere('subcat_id', '!=', $sla->subcat_id)
                      ->orWhere('sla_level', '!=', $sla->sla_level);
                });
            }
        }

        $exists = $query->exists();

        return response()->json(['exists' => $exists]);
    }
}
