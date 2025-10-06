<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Yajra\DataTables\Facades\DataTables;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;

class RoleController extends Controller
{
    /**
     * Display a listing of roles.
     */
    public function index()
    {
        // Get permissions grouped by module for role assignment
        $permissions = Permission::select('name', 'module')
                                ->orderBy('module')
                                ->orderBy('name')
                                ->get()
                                ->groupBy('module');

        // Get statistics
        $stats = $this->getRoleStatistics();

        return view('roles.index', compact('permissions', 'stats'));
    }

    /**
     * Get roles data for DataTable.
     */
    public function getData(Request $request)
    {
        $roles = Role::with(['users', 'permissions'])
                    ->select(['roles.*']);

        return DataTables::of($roles)
            ->addIndexColumn()
            ->addColumn('user_names', function ($role) {
                return $role->users->pluck('name')->toArray();
            })
            ->addColumn('users', function ($role) {
                $users = $role->users->take(3);
                $totalUsers = $role->users->count();

                if ($totalUsers === 0) {
                    return '<span class="text-muted">No users assigned</span>';
                }

                $avatars = '';
                foreach ($users as $user) {
                    $initials = strtoupper(substr($user->name, 0, 2));
                    $avatars .= '<div class="avatar avatar-xs pull-up me-1" title="' . e($user->name) . '">
                                    <span class="avatar-initial rounded-circle bg-label-primary">' . $initials . '</span>
                                </div>';
                }

                if ($totalUsers > 3) {
                    $remaining = $totalUsers - 3;
                    $avatars .= '<div class="avatar avatar-xs">
                                    <span class="avatar-initial rounded-circle bg-label-secondary">+' . $remaining . '</span>
                                </div>';
                }

                return '<div class="d-flex align-items-center">' . $avatars . '</div>';
            })
            ->addColumn('users_count', function ($role) {
                $count = $role->users->count();
                $badgeClass = $count > 0 ? 'bg-label-success' : 'bg-label-secondary';
                return '<span class="badge ' . $badgeClass . '">' . $count . ' users</span>';
            })
            ->addColumn('permissions_count', function ($role) {
                $count = $role->permissions->count();
                $badgeClass = $count > 0 ? 'bg-label-info' : 'bg-label-secondary';
                return '<span class="badge ' . $badgeClass . '">' . $count . ' permissions</span>';
            })
            ->addColumn('status', function ($role) {
                $isActive = $role->is_active ?? true;
                $statusClass = $isActive ? 'bg-label-success' : 'bg-label-secondary';
                $statusText = $isActive ? 'Active' : 'Inactive';
                return '<span class="badge ' . $statusClass . '">' . $statusText . '</span>';
            })
            ->addColumn('created_date', function ($role) {
                return $role->created_at->format('d M Y');
            })
            ->addColumn('action', function ($role) {
                $viewBtn = '<button type="button" class="btn btn-icon btn-text-info rounded-pill btn-sm view-role"
                           data-id="' . e($role->id) . '"
                           data-bs-toggle="tooltip"
                           data-bs-placement="top"
                           title="View Details">
                    <i class="bx bx-show"></i>
                </button>';

                $editBtn = '<button type="button" class="btn btn-icon btn-text-secondary rounded-pill btn-sm edit-role"
                           data-id="' . e($role->id) . '"
                           data-bs-toggle="tooltip"
                           data-bs-placement="top"
                           title="Edit Role">
                    <i class="bx bx-edit"></i>
                </button>';

                $permissionsBtn = '<button type="button" class="btn btn-icon btn-text-warning rounded-pill btn-sm manage-permissions"
                                  data-id="' . e($role->id) . '"
                                  data-bs-toggle="tooltip"
                                  data-bs-placement="top"
                                  title="Manage Permissions">
                    <i class="bx bx-lock"></i>
                </button>';

                $deleteBtn = '';
                if (!$role->isSystemRole()) {
                    $deleteBtn = '<button type="button" class="btn btn-icon btn-text-danger rounded-pill btn-sm delete-role"
                                 data-id="' . e($role->id) . '"
                                 data-bs-toggle="tooltip"
                                 data-bs-placement="top"
                                 title="Delete">
                        <i class="bx bx-trash"></i>
                    </button>';
                }

                return '<div class="d-inline-block text-nowrap">' .
                       $viewBtn . $editBtn . $permissionsBtn . $deleteBtn .
                       '</div>';
            })
            ->rawColumns(['users', 'users_count', 'permissions_count', 'status', 'action'])
            ->make(true);
    }

    /**
     * Store a newly created role.
     */
    public function store(StoreRoleRequest $request)
    {
        try {
            DB::beginTransaction();

            // Create role
            $role = Role::create([
                'name' => $request->name,
                'description' => $request->description,
                'color' => $request->color,
                'icon' => $request->icon,
                'is_active' => $request->is_active ?? true,
                'guard_name' => 'web',
            ]);

            // Assign permissions if provided
            if ($request->permissions) {
                $role->syncPermissions($request->permissions);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Role created successfully',
                'role' => $role->load('permissions')
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Role creation failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error creating role: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified role.
     */
    public function show(Role $role)
    {
        $role->load(['users', 'permissions']);

        return response()->json([
            'success' => true,
            'role' => $role,
            'stats' => $role->getStats(),
            'grouped_permissions' => $role->getGroupedPermissions(),
            'users' => $role->getUsersWithDetails(),
        ]);
    }

    /**
     * Show the form for editing the specified role.
     */
    public function edit(Role $role)
    {
        try {
            $role->load('permissions');
            return response()->json([
                'success' => true,
                'role' => $role,
                'permissions' => $role->permissions->pluck('name')->toArray()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading role: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get permissions assigned to a specific role.
     */
    public function getRolePermissions(Role $role)
    {
        try {
            $permissions = $role->permissions->pluck('name');
            $groupedPermissions = $role->getGroupedPermissions();

            return response()->json([
                'success' => true,
                'role' => $role,
                'permissions' => $permissions,
                'grouped_permissions' => $groupedPermissions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading role permissions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified role.
     */
    public function update(UpdateRoleRequest $request, Role $role)
    {
        try {
            // Check if role is system role
            if ($role->isSystemRole() && $request->name !== $role->name) {
                return response()->json([
                    'success' => false,
                    'message' => 'System roles cannot be renamed'
                ], 403);
            }

            DB::beginTransaction();

            // Update role data
            $role->update([
                'name' => $request->name,
                'description' => $request->description,
                'color' => $request->color,
                'icon' => $request->icon,
                'is_active' => $request->is_active ?? true,
            ]);

            // Update permissions if provided
            if ($request->has('permissions')) {
                $permissionDetails = $role->syncPermissionsWithDetails($request->permissions ?? []);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Role updated successfully',
                'role' => $role->load('permissions'),
                'permission_changes' => $permissionDetails ?? null
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Role update failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error updating role: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update role permissions only.
     */
    public function updatePermissions(Request $request, Role $role)
    {
        $request->validate([
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        try {
            $permissionDetails = $role->syncPermissionsWithDetails($request->permissions ?? []);

            return response()->json([
                'success' => true,
                'message' => 'Permissions updated successfully',
                'permission_changes' => $permissionDetails
            ]);
        } catch (\Exception $e) {
            Log::error('Permission update failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error updating permissions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle role status (activate/deactivate).
     */
    public function toggleStatus(Request $request, Role $role)
    {
        try {
            $action = $request->action;

            if ($action === 'activate') {
                $role->activate();
                $message = 'Role activated successfully';
            } elseif ($action === 'deactivate') {
                $role->deactivate();
                $message = 'Role deactivated successfully';
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid action'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'status' => $role->is_active
            ]);

        } catch (\Exception $e) {
            Log::error('Role status toggle failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error updating role status'
            ], 500);
        }
    }

    /**
     * Remove the specified role.
     */
    public function destroy(Role $role)
    {
        try {
            // Check if role can be deleted
            $canDelete = $role->canBeDeleted();

            if (!$canDelete['can_delete']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete role',
                    'errors' => $canDelete['errors']
                ], 403);
            }

            $role->delete();

            return response()->json([
                'success' => true,
                'message' => 'Role deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Role deletion failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error deleting role: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Duplicate a role.
     */
    public function duplicate(Role $role)
    {
        try {
            DB::beginTransaction();

            // Create new role with duplicated data
            $newRole = Role::create([
                'name' => $role->name . ' (Copy)',
                'description' => $role->description,
                'color' => $role->color,
                'icon' => $role->icon,
                'is_active' => true,
                'guard_name' => 'web',
            ]);

            // Copy permissions
            $permissions = $role->permissions->pluck('name')->toArray();
            $newRole->syncPermissions($permissions);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Role duplicated successfully',
                'role' => $newRole->load('permissions')
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Role duplication failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error duplicating role: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get role statistics.
     */
    private function getRoleStatistics(): array
    {
        return [
            'total_roles' => Role::count(),
            'active_roles' => Role::where('is_active', true)->count(),
            'inactive_roles' => Role::where('is_active', false)->count(),
            'system_roles' => Role::whereIn('name', ['Super Admin', 'Administrator'])->count(),
            'roles_with_users' => Role::has('users')->count(),
            'roles_without_users' => Role::doesntHave('users')->count(),
        ];
    }

    /**
     * Get dashboard statistics for roles.
     */
    public function getStatistics()
    {
        try {
            $stats = $this->getRoleStatistics();

            // Calculate percentages
            $total = $stats['total_roles'];
            $stats['active_percentage'] = $total > 0 ? round(($stats['active_roles'] / $total) * 100) : 0;
            $stats['with_users_percentage'] = $total > 0 ? round(($stats['roles_with_users'] / $total) * 100) : 0;

            return response()->json([
                'success' => true,
                'statistics' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get role statistics: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error retrieving statistics'
            ], 500);
        }
    }
}
