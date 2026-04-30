<?php

namespace App\Http\Controllers\Configuration;

use App\Http\Controllers\Controller;
use App\Models\RefCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use LDAP\Result;
use Yajra\DataTables\Facades\DataTables;

class RefCategoryManagementController extends Controller
{
    public function index()
    {
      // Get total categories count
        $totalCategories = RefCategory::count();

        return view('ref-category-management.index', compact('totalCategories'));
    }

    public function getAllCategories(Request $request)
    {
        $categories = RefCategory::query();

        return DataTables::of($categories)
            ->addIndexColumn()
            ->addColumn('action', function ($category) {
                $editBtn = '<button type="button" class="btn btn-icon btn-text-secondary rounded-pill btn-sm edit-record" data-id="'.e($category->id).'" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
                    <i class="bx bx-edit"></i>
                </button>';

                $deleteBtn = '<button type="button" class="btn btn-icon text-danger rounded-pill btn-sm delete-record" data-id="'.e($category->id).'" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
                    <i class="bx bx-trash"></i>
                </button>';

                return '<div class="d-inline-block text-nowrap">' . $editBtn . $deleteBtn . '</div>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function edit($id)
    {
        try {
            $category = RefCategory::findOrFail($id);
            return response()->json($category);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:ref_categories,cat_name,NULL,id,deleted_at,NULL',
                'description' => 'required|string'
            ]);

            $category = new RefCategory();
            $category->cat_name = $validated['name'];
            $category->cat_desc = $validated['description'];
            $category->created_by = $request->user()->id;
            $category->save();

            return response()->json([
                'success' => true,
                'data' => $category
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false
            ], 500);
        }
    }

    public function checkDuplicate(Request $request)
    {
        $name = $request->input('name');
        $id = $request->input('id'); // For edit mode

        $query = RefCategory::where('cat_name', $name)->whereNull('deleted_at');

        // Exclude current record when editing
        if ($id) {
            $query->where('id', '!=', $id);
        }

        $exists = $query->exists();

        return response()->json([
            'valid' => !$exists
        ]);
    }

    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:ref_categories,cat_name,' . $id . ',id,deleted_at,NULL',
                'description' => 'required|string'
            ]);

            $category = RefCategory::findOrFail($id);
            $category->cat_name = $validated['name'];
            $category->cat_desc = $validated['description'];
            $category->updated_by = $request->user()->id;
            $category->save();

            return response()->json([
                'success' => true,
                'data' => $category
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false
            ], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $category = RefCategory::findOrFail($id);

            // Check if category has sub-categories
            if ($category->subCategories->count() > 0) {
                $subcategoryNames = $category->subCategories->pluck('subcat_name')->toArray();

                return response()->json([
                    'success' => false,
                    'has_subcategories' => true,
                    'subcategories' => $subcategoryNames
                ]);
            }

            // Set deleted_by before soft deleting the category
            $category->deleted_by = Auth::id();
            $category->save();
            $category->delete();

            return response()->json([
                'success' => true
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error occurred while deleting category'
            ], 500);
        }
    }

    public function getSubcategories($id)
    {
        try {
            $category = RefCategory::findOrFail($id);
            $subcategories = $category->subCategories()->select('id', 'subcat_name')->orderBy('subcat_name', 'asc')->get();

            return response()->json([
                'success' => true,
                'data' => $subcategories
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false
            ], 500);
        }
    }

    public function forceDestroy(Request $request, $id)
    {
        try {
            $category = RefCategory::findOrFail($id);

            // Set deleted_by for subcategories before deleting them
            $category->subCategories()->update(['deleted_by' => Auth::id()]);
            $category->subCategories()->delete();

            // Set deleted_by before deleting the category
            $category->deleted_by = Auth::id();
            $category->save();
            $category->delete();

            return response()->json([
                'success' => true
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error occurred while force deleting category'
            ], 500);
        }
    }
}
