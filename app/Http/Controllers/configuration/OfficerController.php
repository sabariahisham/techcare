<?php

namespace App\Http\Controllers\Configuration;

use App\Http\Controllers\Controller;
use App\Models\Officer;
use App\Models\RefCategory;
use App\Models\RefSubCategory;
use App\Models\User;
use App\Models\Sla;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class OfficerController extends Controller
{
    public function index()
    {
        $categories = RefCategory::orderBy('cat_name')->get();
        $users = User::orderBy('name')->get();

        $totalCategories = RefCategory::count();
        $totalSubCategories = RefSubCategory::count();
        $totalSlas = Sla::count();
        $totalOfficers = Officer::count();

        return view('officers.index', compact('categories', 'users', 'totalCategories', 'totalSubCategories', 'totalSlas', 'totalOfficers'));
    }

    public function data(Request $request)
    {
        $officers = Officer::with(['subcategory.category', 'user'])
            ->join('ref_sub_categories', 'officers.subcat_id', '=', 'ref_sub_categories.id')
            ->join('ref_categories', 'ref_sub_categories.cat_id', '=', 'ref_categories.id')
            ->orderBy('ref_categories.cat_name')
            ->orderBy('ref_sub_categories.subcat_name')
            ->orderBy('officers.level_tugasan')
            ->select('officers.*');

        return DataTables::of($officers)
            ->addIndexColumn()
            ->addColumn('cat_name', function ($officer) {
                return $officer->subcategory->category->cat_name ?? '-';
            })
            ->addColumn('subcat_name', function ($officer) {
                return $officer->subcategory->subcat_name ?? '-';
            })
            ->addColumn('user_name', function ($officer) {
                return $officer->user->name ?? '-';
            })
            ->addColumn('action', function ($officer) {
                return '<div class="d-inline-block text-nowrap">
                    <button class="btn btn-sm btn-icon edit-record" data-id="' . $officer->id . '" data-bs-toggle="offcanvas" data-bs-target="#offcanvasEditOfficer">
                        <i class="bx bx-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-icon delete-record text-danger" data-id="' . $officer->id . '">
                        <i class="bx bx-trash"></i>
                    </button>
                </div>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'cat_id' => 'required|exists:ref_categories,id',
            'subcat_id' => 'required|exists:ref_sub_categories,id',
            'user_id' => 'required',
            'level_tugasan' => 'required|integer|between:1,10',
        ]);

        // Convert single user_id to array if needed
        $userIds = is_array($request->user_id) ? $request->user_id : [$request->user_id];

        // Validate each user_id exists
        foreach ($userIds as $userId) {
            if (!User::find($userId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pegawai yang dipilih tidak wujud dalam sistem.'
                ], 422);
            }
        }

        $createdOfficers = [];
        $existingAssignments = [];

        foreach ($userIds as $userId) {
            // Check if officer assignment already exists for this user, category and subcategory
            $existingOfficer = Officer::where('cat_id', $request->cat_id)
                ->where('subcat_id', $request->subcat_id)
                ->where('user_id', $userId)
                ->first();

            if ($existingOfficer) {
                $user = User::find($userId);
                $existingAssignments[] = $user->name ?? "User ID: $userId";
                continue;
            }

            $officer = Officer::create([
                'cat_id' => $request->cat_id,
                'subcat_id' => $request->subcat_id,
                'user_id' => $userId,
                'level_tugasan' => $request->level_tugasan,
                'created_by' => Auth::id(),
            ]);

            $createdOfficers[] = $officer;
        }

        if (empty($createdOfficers) && !empty($existingAssignments)) {
            return response()->json([
                'success' => false,
                'message' => 'Semua pegawai yang dipilih sudah mempunyai tugasan untuk kategori dan sub kategori ini: ' . implode(', ', $existingAssignments)
            ], 422);
        }

        $message = count($createdOfficers) . ' pegawai tugasan berjaya ditambah.';
        if (!empty($existingAssignments)) {
            $message .= ' Pegawai berikut sudah mempunyai tugasan: ' . implode(', ', $existingAssignments);
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $createdOfficers
        ]);
    }

    public function edit($id)
    {
        $officer = Officer::with(['subcategory.category', 'user'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $officer->id,
                'cat_id' => $officer->cat_id,
                'subcat_id' => $officer->subcat_id,
                'user_id' => $officer->user_id,
                'level_tugasan' => $officer->level_tugasan,
                'category_name' => $officer->subcategory->category->cat_name ?? '',
                'subcategory_name' => $officer->subcategory->subcat_name ?? '',
                'user_name' => $officer->user->name ?? ''
            ]
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'cat_id' => 'required|exists:ref_categories,id',
            'subcat_id' => 'required|exists:ref_sub_categories,id',
            'user_id' => 'required|exists:users,id',
            'level_tugasan' => 'required|integer|between:1,10',
        ]);

        $officer = Officer::findOrFail($id);

        // Check if officer assignment already exists for another officer with same user, category and subcategory
        $existingOfficer = Officer::where('cat_id', $request->cat_id)
            ->where('subcat_id', $request->subcat_id)
            ->where('user_id', $request->user_id)
            ->where('id', '!=', $id)
            ->first();

        if ($existingOfficer) {
            return response()->json([
                'success' => false,
                'message' => 'Pegawai tugasan untuk kategori dan sub kategori ini sudah wujud dalam sistem.'
            ], 422);
        }

        $officer->update([
            'cat_id' => $request->cat_id,
            'subcat_id' => $request->subcat_id,
            'user_id' => $request->user_id,
            'level_tugasan' => $request->level_tugasan,
            'updated_by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pegawai tugasan berjaya dikemaskini.',
            'data' => $officer
        ]);
    }

    public function destroy($id)
    {
        try {
            $officer = Officer::findOrFail($id);
            $officer->delete();

            return response()->json([
                'success' => true,
                'message' => 'Pegawai tugasan berjaya dipadam.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ralat berlaku semasa memadam pegawai tugasan.'
            ], 500);
        }
    }
}
