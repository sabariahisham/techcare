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

        return view('sla-and-severity.index', compact('categories', 'subCategories', 'totalCategories', 'totalSubCategories', 'totalSlas'));
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
            ->editColumn('sla_severity', function ($sla) {
                return ucfirst($sla->sla_severity);
            })
            ->addColumn('action', function ($sla) {
                $editBtn = '<button type="button" class="btn btn-icon btn-text-secondary rounded-pill btn-sm edit-record" data-id="'.e($sla->id).'" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
                    <i class="bx bx-edit"></i>
                </button>';

                $deleteBtn = '<button type="button" class="btn btn-icon btn-text-secondary rounded-pill btn-sm delete-record" data-id="'.e($sla->id).'" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
                    <i class="bx bx-trash"></i>
                </button>';

                return '<div class="d-inline-block text-nowrap">' . $editBtn . $deleteBtn . '</div>';
            })
            ->rawColumns(['action'])
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
                        $fail('SLA untuk kombinasi kategori, sub-kategori, dan level petugas ini sudah wujud.');
                    }
                },
            ],
            'sla_severity' => 'required|string|max:255',
            'sla_days' => 'required|integer|min:1',
        ]);

        try {
            Sla::create([
                'cat_id' => $request->cat_id,
                'subcat_id' => $request->subcat_id,
                'sla_level' => $request->sla_level,
                'sla_severity' => $request->sla_severity,
                'sla_days' => $request->sla_days,
                'created_by' => Auth::id(),
            ]);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false]);
        }
    }

    public function edit(string $id)
    {
        try {
            $sla = Sla::with(['category', 'subCategory'])->findOrFail($id);
            return response()->json($sla);
        } catch (\Exception $e) {
            return response()->json(['error' => true], 404);
        }
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'cat_id' => 'required|exists:ref_categories,id',
            'subcat_id' => 'required|exists:ref_sub_categories,id',
            'sla_level' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($request, $id) {
                    $exists = Sla::where('cat_id', $request->cat_id)
                                 ->where('subcat_id', $request->subcat_id)
                                 ->where('sla_level', $value)
                                 ->where('id', '!=', $id)
                                 ->exists();
                    if ($exists) {
                        $fail('SLA untuk kombinasi kategori, sub-kategori, dan level petugas ini sudah wujud.');
                    }
                },
            ],
            'sla_severity' => 'required|string|max:255',
            'sla_days' => 'required|integer|min:1',
        ]);

        try {
            $sla = Sla::findOrFail($id);

            $sla->update([
                'cat_id' => $request->cat_id,
                'subcat_id' => $request->subcat_id,
                'sla_level' => $request->sla_level,
                'sla_severity' => $request->sla_severity,
                'sla_days' => $request->sla_days,
                'updated_by' => Auth::id(),
            ]);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false]);
        }
    }

    public function destroy(string $id)
    {
        try {
            $sla = Sla::findOrFail($id);

            // Ensure user is authenticated
            if (!Auth::check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
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

            return response()->json(['success' => true, 'message' => 'SLA berjaya dipadam']);
        } catch (\Exception $e) {
            Log::error('Error deleting SLA', [
                'sla_id' => $id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ralat semasa memadam SLA: ' . $e->getMessage()
            ], 500);
        }
    }

    public function checkDuplicate(Request $request)
    {
        $request->validate([
            'cat_id' => 'required|integer',
            'subcat_id' => 'required|integer',
            'sla_level' => 'required|string',
        ]);

        $exists = Sla::where('cat_id', $request->cat_id)
                     ->where('subcat_id', $request->subcat_id)
                     ->where('sla_level', $request->sla_level)
                     ->when($request->id, function ($query) use ($request) {
                         return $query->where('id', '!=', $request->id);
                     })
                     ->exists();

        return response()->json(['exists' => $exists]);
    }
}
