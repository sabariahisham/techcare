<?php

namespace App\Http\Controllers\Configuration;

use App\Http\Controllers\Controller;
use App\Models\RefCategory;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class RefCategoryManagementController extends Controller
{
    public function index()
    {
        return view('ref-category-management.index');
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

                $deleteBtn = '<button type="button" class="btn btn-icon btn-text-secondary rounded-pill btn-sm delete-record" data-id="'.e($category->id).'" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
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
                'success' => false,
                'message' => 'Error loading category: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $category = RefCategory::findOrFail($id);
            $category->delete();

            return response()->json([
                'success' => true,
                'message' => 'Category deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting category: ' . $e->getMessage()
            ], 500);
        }
    }
}
