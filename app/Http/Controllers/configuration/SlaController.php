<?php

namespace App\Http\Controllers\Configuration;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sla;
use App\Models\RefCategory;
use App\Models\RefSubCategory;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class SlaController extends Controller
{
    public function index()
    {
        $categories = RefCategory::all();
        $subCategories = RefSubCategory::all();
        return view('sla-and-severiry.index', compact('categories', 'subCategories'));
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
            ->editColumn('sla_priority', function ($sla) {
                return ucfirst($sla->sla_priority);
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
            'sla_level' => 'required|string|max:255',
            'sla_priority' => 'required|string|max:255',
            'sla_days' => 'required|integer|min:1',
        ]);

        try {
            Sla::create([
                'cat_id' => $request->cat_id,
                'subcat_id' => $request->subcat_id,
                'sla_level' => $request->sla_level,
                'sla_priority' => $request->sla_priority,
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
            'sla_level' => 'required|string|max:255',
            'sla_priority' => 'required|string|max:255',
            'sla_days' => 'required|integer|min:1',
        ]);

        try {
            $sla = Sla::findOrFail($id);

            $sla->update([
                'cat_id' => $request->cat_id,
                'subcat_id' => $request->subcat_id,
                'sla_level' => $request->sla_level,
                'sla_priority' => $request->sla_priority,
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
            $sla->delete();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false]);
        }
    }
}
