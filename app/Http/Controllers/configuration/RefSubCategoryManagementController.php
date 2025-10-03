<?php

namespace App\Http\Controllers\Configuration;

use App\Http\Controllers\Controller;
use App\Models\RefSubCategory;
use App\Models\RefCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class RefSubCategoryManagementController extends Controller
{

    public function index()
    {
        $categories = RefCategory::all();
        return view('ref-sub-category-management.index', compact('categories'));
    }

    public function data(Request $request)
    {
        $subCategories = RefSubCategory::with('category')->select('ref_sub_categories.*');

        return DataTables::of($subCategories)
            ->addIndexColumn()
            ->addColumn('cat_name', function ($subCategory) {
                return $subCategory->category ? $subCategory->category->cat_name : '-';
            })
            ->addColumn('action', function ($subCategory) {
                $editBtn = '<button type="button" class="btn btn-icon btn-text-secondary rounded-pill btn-sm edit-record" data-id="'.e($subCategory->id).'" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
                    <i class="bx bx-edit"></i>
                </button>';

                $deleteBtn = '<button type="button" class="btn btn-icon btn-text-secondary rounded-pill btn-sm delete-record" data-id="'.e($subCategory->id).'" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
                    <i class="bx bx-trash"></i>
                </button>';

                return '<div class="d-inline-block text-nowrap">' . $editBtn . $deleteBtn . '</div>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function create()
    {
        //
    }


    public function store(Request $request)
    {
        $request->validate([
            'cat_id' => 'required|exists:ref_categories,id',
            'subcat_name' => 'required|string|max:255',
            'subcat_desc' => 'nullable|string|max:1000',
        ]);

        try {
            RefSubCategory::create([
                'cat_id' => $request->cat_id,
                'subcat_name' => $request->subcat_name,
                'subcat_desc' => $request->subcat_desc,
                'created_by' => Auth::id(),
            ]);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        try {
            $subCategory = RefSubCategory::with('category')->findOrFail($id);
            return response()->json($subCategory);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Sub category not found'], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'cat_id' => 'required|exists:ref_categories,id',
            'subcat_name' => 'required|string|max:255',
            'subcat_desc' => 'nullable|string|max:1000',
        ]);

        try {
            $subCategory = RefSubCategory::findOrFail($id);

            $subCategory->update([
                'cat_id' => $request->cat_id,
                'subcat_name' => $request->subcat_name,
                'subcat_desc' => $request->subcat_desc,
                'updated_by' => Auth::id(),
            ]);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $subCategory = RefSubCategory::findOrFail($id);
            $subCategory->delete();

            return response()->json(['success' => true, 'message' => 'Sub-kategori berjaya dipadam']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Ralat semasa memadam sub-kategori']);
        }
    }
}
