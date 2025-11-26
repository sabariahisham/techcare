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
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class OfficerController extends Controller
{
    public function index()
    {
        $categories = RefCategory::orderBy('cat_name')->get();
        $users = User::orderBy('nama')->get();

        $totalCategories = RefCategory::count();
        $totalSubCategories = RefSubCategory::count();
        $totalSlas = Sla::count();
        $totalOfficers = Officer::count();

        // Calculate categories with officers assigned
        $categoriesWithOfficers = RefCategory::whereHas('officers')->count();
        $categoryPercentage = $totalCategories > 0 ? round(($categoriesWithOfficers / $totalCategories) * 100, 2) : 0;

        // Calculate sub-categories with officers assigned
        $subCategoriesWithOfficers = RefSubCategory::whereHas('officers')->count();
        $subCategoryPercentage = $totalSubCategories > 0 ? round(($subCategoriesWithOfficers / $totalSubCategories) * 100, 2) : 0;

        return view('officers.index', compact('categories', 'users', 'totalCategories', 'totalSubCategories', 'totalSlas', 'totalOfficers', 'categoriesWithOfficers', 'categoryPercentage', 'subCategoriesWithOfficers', 'subCategoryPercentage'));
    }

    public function data(Request $request)
    {
        // Group officers by category, subcategory, and level
        $groupedOfficers = Officer::with(['subcategory.category', 'user'])
            ->get()
            ->groupBy(function($officer) {
                return $officer->cat_id . '_' . $officer->subcat_id . '_' . $officer->level_tugasan;
            })
            ->map(function($group) {
                $firstOfficer = $group->first();
                return [
                    'id' => $firstOfficer->id, // Use first officer ID as representative
                    'cat_id' => $firstOfficer->cat_id,
                    'subcat_id' => $firstOfficer->subcat_id,
                    'level_tugasan' => $firstOfficer->level_tugasan,
                    'cat_name' => $firstOfficer->subcategory->category->cat_name ?? '-',
                    'subcat_name' => $firstOfficer->subcategory->subcat_name ?? '-',
                    'users' => $group->pluck('user.nama')->filter()->toArray(),
                    'user_ids' => $group->pluck('user_id')->toArray(),
                    'officer_ids' => $group->pluck('id')->toArray(),
                    'created_at' => $firstOfficer->created_at,
                ];
            })
            ->sortBy(function($item) {
                return $item['cat_name'] . '_' . $item['subcat_name'] . '_' . $item['level_tugasan'];
            })
            ->values();

        return DataTables::of($groupedOfficers)
            ->addIndexColumn()
            ->addColumn('cat_name', function ($data) {
                return $data['cat_name'];
            })
            ->addColumn('subcat_name', function ($data) {
                return $data['subcat_name'];
            })
            ->addColumn('level_tugasan', function ($data) {
                $level = $data['level_tugasan'];
                $badgeClass = '';
                $levelText = '';

                switch ($level) {
                    case 1:
                        $badgeClass = 'bg-label-success';
                        $levelText = 'Tahap 1';
                        break;
                    case 2:
                        $badgeClass = 'bg-label-warning';
                        $levelText = 'Tahap 2';
                        break;
                    case 3:
                        $badgeClass = 'bg-label-danger';
                        $levelText = 'Tahap 3';
                        break;
                    default:
                        $badgeClass = 'bg-label-secondary';
                        $levelText = 'Tahap ' . $level;
                        break;
                }

                return '<span class="badge ' . $badgeClass . '">' . $levelText . '</span>';
            })
            ->addColumn('user_name', function ($data) {
                $users = $data['users'];
                $userBadges = [];

                if (count($users) > 3) {
                    $displayUsers = array_slice($users, 0, 3);
                    $remaining = count($users) - 3;

                    foreach ($displayUsers as $user) {
                        $userBadges[] = '<span class="badge bg-label-primary me-1 mb-1">' . $user . '</span>';
                    }

                    $userBadges[] = '<span class="badge bg-label-info me-1 mb-1">+' . $remaining . ' lagi</span>';
                } else {
                    foreach ($users as $user) {
                        $userBadges[] = '<span class="badge bg-label-primary me-1 mb-1">' . $user . '</span>';
                    }
                }

                return implode(' ', $userBadges);
            })
            ->addColumn('action', function ($data) {
                return '<div class="d-inline-block text-nowrap">
                    <button class="btn btn-sm btn-icon edit-record" data-id="' . $data['id'] . '" data-bs-toggle="offcanvas" data-bs-target="#offcanvasEditOfficer">
                        <i class="bx bx-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-icon delete-record text-danger" data-id="' . $data['id'] . '">
                        <i class="bx bx-trash"></i>
                    </button>
                </div>';
            })
            ->rawColumns(['level_tugasan', 'user_name', 'action'])
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
                $existingAssignments[] = $user->nama ?? "User ID: $userId";
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

        // Get all officers with the same category, subcategory, and level
        // This allows editing multiple users as a group
        $relatedOfficers = Officer::with(['user'])
            ->where('cat_id', $officer->cat_id)
            ->where('subcat_id', $officer->subcat_id)
            ->where('level_tugasan', $officer->level_tugasan)
            ->get();

        // Collect all user IDs for this group
        $userIds = $relatedOfficers->pluck('user_id')->toArray();
        $userNames = $relatedOfficers->pluck('user.nama')->filter()->toArray();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $officer->id, // Primary officer ID for reference
                'cat_id' => $officer->cat_id,
                'subcat_id' => $officer->subcat_id,
                'user_id' => $userIds, // Array of user IDs
                'level_tugasan' => $officer->level_tugasan,
                'category_name' => $officer->subcategory->category->cat_name ?? '',
                'subcategory_name' => $officer->subcategory->subcat_name ?? '',
                'user_names' => $userNames, // Array of user names for reference
                'related_officer_ids' => $relatedOfficers->pluck('id')->toArray() // All officer IDs in this group
            ]
        ]);
    }

    public function update(Request $request, $id)
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

        $officer = Officer::findOrFail($id);

        // For update, we'll delete all officers with the same category/subcategory/level and create new ones
        // This approach handles group editing properly

        // First, collect the old assignment info for logging purposes
        $oldOfficer = [
            'id' => $officer->id,
            'user_id' => $officer->user_id,
            'cat_id' => $officer->cat_id,
            'subcat_id' => $officer->subcat_id,
            'level_tugasan' => $officer->level_tugasan
        ];

        // Delete all officers with the same category, subcategory, and current level
        // This handles both single and multiple officer scenarios
        Officer::where('cat_id', $officer->cat_id)
            ->where('subcat_id', $officer->subcat_id)
            ->where('level_tugasan', $officer->level_tugasan)
            ->delete();

        // Create new officer records for each selected user
        $createdOfficers = [];
        $existingAssignments = [];

        foreach ($userIds as $userId) {
            // Check if officer assignment already exists for this user, category, subcategory and level
            // But exclude the same combination we just deleted (in case of level change)
            $existingOfficer = Officer::where('cat_id', $request->cat_id)
                ->where('subcat_id', $request->subcat_id)
                ->where('user_id', $userId)
                ->where('level_tugasan', $request->level_tugasan)
                ->first();

            if ($existingOfficer) {
                $user = User::find($userId);
                $existingAssignments[] = $user->nama ?? "User ID: $userId";
                continue;
            }

            $newOfficer = Officer::create([
                'cat_id' => $request->cat_id,
                'subcat_id' => $request->subcat_id,
                'user_id' => $userId,
                'level_tugasan' => $request->level_tugasan,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            $createdOfficers[] = $newOfficer;
        }

        if (empty($createdOfficers)) {
            if (!empty($existingAssignments)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Semua pegawai yang dipilih sudah mempunyai tugasan untuk kategori, sub kategori dan level yang sama: ' . implode(', ', $existingAssignments)
                ], 422);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Tiada pegawai tugasan yang berjaya dikemaskini.'
                ], 422);
            }
        }

        $message = count($createdOfficers) . ' pegawai tugasan berjaya dikemaskini.';
        if (!empty($existingAssignments)) {
            $message .= ' Pegawai berikut sudah mempunyai tugasan: ' . implode(', ', $existingAssignments);
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $createdOfficers
        ]);
    }

    public function previewDelete($id)
    {
        try {
            $officer = Officer::findOrFail($id);

            // Get all officers that will be deleted (same category, subcategory, and level)
            $officersToDelete = Officer::where('cat_id', $officer->cat_id)
                ->where('subcat_id', $officer->subcat_id)
                ->where('level_tugasan', $officer->level_tugasan)
                ->with('user')
                ->get()
                ->map(function ($officer) {
                    return [
                        'id' => $officer->id,
                        'user_name' => optional($officer->user)->nama ?? 'Tiada Pegawai Ditugaskan',
                        'level_tugasan' => $officer->level_tugasan
                    ];
                });

            Log::info('Preview delete officers retrieved successfully', [
                'officer_id' => $id,
                'officers_count' => $officersToDelete->count(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'officers' => $officersToDelete
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('Officer not found for preview delete', [
                'officer_id' => $id,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Pegawai tugasan tidak dijumpai.'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Preview delete officer error: ' . $e->getMessage(), [
                'officer_id' => $id,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => app()->environment('local')
                    ? 'Ralat berlaku semasa mengambil maklumat pegawai: ' . $e->getMessage()
                    : 'Ralat berlaku semasa mengambil maklumat pegawai.'
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $officer = Officer::findOrFail($id);

            // Update deleted_by field for all officers that will be deleted, then delete them
            $officersToDelete = Officer::where('cat_id', $officer->cat_id)
                ->where('subcat_id', $officer->subcat_id)
                ->where('level_tugasan', $officer->level_tugasan)
                ->get();

            $deletedCount = 0;
            foreach ($officersToDelete as $officerToDelete) {
                $officerToDelete->update(['deleted_by' => Auth::id()]);
                $officerToDelete->delete();
                $deletedCount++;
            }

            $message = $deletedCount > 1
                ? "$deletedCount pegawai tugasan berjaya dipadam."
                : 'Pegawai tugasan berjaya dipadam.';

            return response()->json([
                'success' => true,
                'message' => $message
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ralat berlaku semasa memadam pegawai tugasan.'
            ], 500);
        }
    }
}
