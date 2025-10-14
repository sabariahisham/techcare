<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Yajra\DataTables\Facades\DataTables;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index()
    {
        // Get roles excluding Administrator for assignment
        $roles = Role::whereNotIn('name', ['Administrator', 'Super Admin'])
                    ->orderBy('name')
                    ->get();

        // Get statistics
        $stats = $this->getUserStatistics();

        return view('admin.users.index', compact('roles', 'stats'));
    }

    /**
     * Get users data for DataTable.
     */
    public function getData(Request $request)
    {
        $users = User::with(['roles', 'jawatan'])
                    ->select(['users.*']);

        return DataTables::of($users)
            ->addIndexColumn()
            ->addColumn('role', function ($user) {
                $roles = $user->roles->pluck('name')->toArray();
                if (empty($roles)) {
                    return '<span class="badge bg-label-secondary">No Role</span>';
                }

                $badges = [];
                foreach ($roles as $role) {
                    $badges[] = '<span class="badge bg-label-primary me-1">' . e($role) . '</span>';
                }
                return implode('', $badges);
            })
            ->addColumn('status', function ($user) {
                $statusClass = $user->is_active ? 'bg-label-success' : 'bg-label-secondary';
                $statusText = $user->is_active ? 'Active' : 'Inactive';
                return '<span class="badge ' . $statusClass . '">' . $statusText . '</span>';
            })
            ->addColumn('email_verified', function ($user) {
                if ($user->email_verified_at) {
                    return '<i class="bx bx-check-circle text-success" title="Verified"></i>';
                }
                return '<i class="bx bx-x-circle text-danger" title="Not Verified"></i>';
            })
            ->addColumn('last_login', function ($user) {
                return $user->last_login_at ? $user->last_login_at->format('d M Y, H:i') : 'Never';
            })
            ->addColumn('action', function ($user) {
                $editBtn = '<button type="button" class="btn btn-icon btn-text-secondary rounded-pill btn-sm edit-record"
                           data-id="' . e($user->id) . '"
                           data-bs-toggle="tooltip"
                           data-bs-placement="top"
                           title="Edit User">
                    <i class="bx bx-edit"></i>
                </button>';

                $viewBtn = '<button type="button" class="btn btn-icon btn-text-info rounded-pill btn-sm view-record"
                           data-id="' . e($user->id) . '"
                           data-bs-toggle="tooltip"
                           data-bs-placement="top"
                           title="View Details">
                    <i class="bx bx-show"></i>
                </button>';

                $statusBtn = $user->is_active
                    ? '<button type="button" class="btn btn-icon btn-text-warning rounded-pill btn-sm status-toggle"
                       data-id="' . e($user->id) . '"
                       data-action="deactivate"
                       data-bs-toggle="tooltip"
                       data-bs-placement="top"
                       title="Deactivate">
                    <i class="bx bx-user-x"></i>
                </button>'
                    : '<button type="button" class="btn btn-icon btn-text-success rounded-pill btn-sm status-toggle"
                       data-id="' . e($user->id) . '"
                       data-action="activate"
                       data-bs-toggle="tooltip"
                       data-bs-placement="top"
                       title="Activate">
                    <i class="bx bx-user-check"></i>
                </button>';

                $deleteBtn = '<button type="button" class="btn btn-icon btn-text-danger rounded-pill btn-sm delete-record"
                            data-id="' . e($user->id) . '"
                            data-bs-toggle="tooltip"
                            data-bs-placement="top"
                            title="Delete">
                    <i class="bx bx-trash"></i>
                </button>';

                return '<div class="d-inline-block text-nowrap">' .
                       $viewBtn . $editBtn . $statusBtn . $deleteBtn .
                       '</div>';
            })
            ->filterColumn('role', function($query, $keyword) {
                $query->whereHas('roles', function($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%");
                });
            })
            ->rawColumns(['role', 'status', 'email_verified', 'action'])
            ->make(true);
    }

    /**
     * Store a newly created user.
     */
    public function store(StoreUserRequest $request)
    {
        try {
            DB::beginTransaction();

            // Create user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password ?? '12345678'), // Default password
                'ic_number' => $request->ic_number,
                'phone_number' => $request->phone_number,
                'office_phone_number' => $request->office_phone_number,
                'agency' => $request->agency,
                'department' => $request->department,
                'section' => $request->section,
                'unit' => $request->unit,
                'position' => $request->position,
                'position_title' => $request->position_title,
                'scheme_and_grade' => $request->scheme_and_grade,
                'is_active' => $request->is_active ?? true,
                'first_time_login' => true,
            ]);

            // Assign role if provided
            if ($request->role) {
                $role = Role::where('name', $request->role)->first();
                if ($role) {
                    $user->assignRole($role);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'user' => $user->load('roles')
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('User creation failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error creating user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        $user->load(['roles.permissions']);

        return response()->json([
            'success' => true,
            'user' => $user,
            'contact_info' => $user->getContactInfo(),
            'work_info' => $user->getWorkInfo(),
            'stats' => $user->getStats(),
        ]);
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        try {
            $user->load('roles');
            return response()->json([
                'success' => true,
                'user' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified user.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        try {
            DB::beginTransaction();

            // Update user data
            $userData = [
                'name' => $request->name,
                'email' => $request->email,
                'ic_number' => $request->ic_number,
                'phone_number' => $request->phone_number,
                'office_phone_number' => $request->office_phone_number,
                'agency' => $request->agency,
                'department' => $request->department,
                'section' => $request->section,
                'unit' => $request->unit,
                'position' => $request->position,
                'position_title' => $request->position_title,
                'scheme_and_grade' => $request->scheme_and_grade,
            ];

            // Update password if provided
            if ($request->password) {
                $userData['password'] = Hash::make($request->password);
                $userData['password_change_at'] = now();
            }

            // Update status if provided
            if ($request->has('is_active')) {
                $userData['is_active'] = $request->is_active;
            }

            $user->update($userData);

            // Update role if provided
            if ($request->role) {
                $role = Role::where('name', $request->role)->first();
                if ($role) {
                    $user->syncRoles([$role]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'user' => $user->load('roles')
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('User update failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error updating user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle user status (activate/deactivate).
     */
    public function toggleStatus(Request $request, User $user)
    {
        try {
            $action = $request->action;

            if ($action === 'activate') {
                $user->activate();
                $message = 'User activated successfully';
            } elseif ($action === 'deactivate') {
                $user->deactivate();
                $message = 'User deactivated successfully';
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid action'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'status' => $user->is_active
            ]);

        } catch (\Exception $e) {
            Log::error('User status toggle failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error updating user status'
            ], 500);
        }
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user)
    {
        try {
            // Check if user has critical roles
            if ($user->hasRole(['Super Admin', 'Administrator'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete users with administrative roles'
                ], 403);
            }

            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('User deletion failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error deleting user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset user password.
     */
    public function resetPassword(Request $request, User $user)
    {
        $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        try {
            $user->update([
                'password' => Hash::make($request->password),
                'password_change_at' => now(),
                'first_time_login' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password reset successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Password reset failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error resetting password'
            ], 500);
        }
    }

    /**
     * Bulk operations on users.
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:activate,deactivate,delete',
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id'
        ]);

        try {
            $users = User::whereIn('id', $request->user_ids)->get();
            $successCount = 0;

            foreach ($users as $user) {
                // Skip critical admin users for deletion
                if ($request->action === 'delete' && $user->hasRole(['Super Admin', 'Administrator'])) {
                    continue;
                }

                switch ($request->action) {
                    case 'activate':
                        $user->activate();
                        $successCount++;
                        break;
                    case 'deactivate':
                        $user->deactivate();
                        $successCount++;
                        break;
                    case 'delete':
                        $user->delete();
                        $successCount++;
                        break;
                }
            }

            $actionText = match($request->action) {
                'activate' => 'activated',
                'deactivate' => 'deactivated',
                'delete' => 'deleted',
            };

            return response()->json([
                'success' => true,
                'message' => "{$successCount} users {$actionText} successfully"
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk action failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error performing bulk action'
            ], 500);
        }
    }

    /**
     * Export users data.
     */
    public function export(Request $request)
    {
        // Implementation for exporting users (CSV, Excel, PDF)
        // This would integrate with Laravel Excel or similar package
        return response()->json([
            'success' => false,
            'message' => 'Export functionality not implemented yet'
        ]);
    }

    /**
     * Get user statistics.
     */
    private function getUserStatistics(): array
    {
        return [
            'total_users' => User::count(),
            'active_users' => User::active()->count(),
            'inactive_users' => User::inactive()->count(),
            'verified_users' => User::verified()->count(),
            'unverified_users' => User::unverified()->count(),
            'users_with_roles' => User::has('roles')->count(),
            'users_without_roles' => User::doesntHave('roles')->count(),
        ];
    }

    /**
     * Get dashboard statistics for users.
     */
    public function getStatistics()
    {
        try {
            $stats = $this->getUserStatistics();

            // Calculate percentages
            $total = $stats['total_users'];
            $stats['active_percentage'] = $total > 0 ? round(($stats['active_users'] / $total) * 100) : 0;
            $stats['inactive_percentage'] = $total > 0 ? round(($stats['inactive_users'] / $total) * 100) : 0;
            $stats['verified_percentage'] = $total > 0 ? round(($stats['verified_users'] / $total) * 100) : 0;

            return response()->json([
                'success' => true,
                'statistics' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get user statistics: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error retrieving statistics'
            ], 500);
        }
    }

    /**
     * Search users for role assignment
     */
    public function search(Request $request)
    {
        try {
            $query = $request->get('q', '');
            $excludeRole = $request->get('exclude_role');
            $format = $request->get('format', 'default'); // Support different formats

            // For Select2, we can return all users if no query, or filter by query
            if ($format === 'select2' && empty($query)) {
                $usersQuery = User::where('kodaktif', true);
            } else {
                if (strlen($query) < 2 && $format !== 'select2') {
                    return response()->json([
                        'success' => true,
                        'users' => []
                    ]);
                }

                $usersQuery = User::where('kodaktif', true);

                if (!empty($query)) {
                    $usersQuery->where(function($q) use ($query) {
                        $q->where('nama', 'LIKE', "%{$query}%")
                          ->orWhere('email', 'LIKE', "%{$query}%");
                    });
                }
            }

            // Exclude users who already have a specific role
            if ($excludeRole) {
                $usersQuery->whereDoesntHave('roles', function($roleQuery) use ($excludeRole) {
                    $roleQuery->where('roles.id', $excludeRole);
                });
            }

            $limit = $format === 'select2' ? 50 : 10;
            $users = $usersQuery->select('id', 'nama', 'email')
                               ->orderBy('nama')
                               ->limit($limit)
                               ->get();

            // Format for Select2 if requested
            if ($format === 'select2') {
                $results = $users->map(function($user) {
                    return [
                        'id' => $user->id,
                        'text' => $user->nama . ' (' . $user->email . ')',
                        'email' => $user->email
                    ];
                });

                return response()->json([
                    'results' => $results,
                    'pagination' => ['more' => false]
                ]);
            }

            // Default format
            return response()->json([
                'success' => true,
                'users' => $users
            ]);

        } catch (\Exception $e) {
            Log::error('User search failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error searching users'
            ], 500);
        }
    }
}
