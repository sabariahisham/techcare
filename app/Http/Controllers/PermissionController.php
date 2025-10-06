<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;
use App\Http\Requests\Permission\StorePermissionRequest;
use App\Http\Requests\Permission\UpdatePermissionRequest;

class PermissionController extends Controller
{
    /**
     * Display a listing of permissions.
     */
    public function index()
    {
        // Get available modules and categories
        $modules = Permission::excludeModules()
                            ->select('module')
                            ->distinct()
                            ->whereNotNull('module')
                            ->orderBy('module')
                            ->pluck('module');

        $categories = Permission::getCategories();
        $commonActions = Permission::getCommonActions();

        // Get statistics
        $stats = $this->getPermissionStatistics();

        return view('permission.index', compact('modules', 'categories', 'commonActions', 'stats'));
    }

    /**
     * Get permissions data for DataTable.
     */
    public function getData(Request $request)
    {
        $permissions = Permission::with(['roles'])
                                ->excludeModules() // Exclude module-list
                                ->select(['permissions.*']);

        return DataTables::of($permissions)
            ->addIndexColumn()
            ->addColumn('module', function ($permission) {
                return $permission->formatted_module;
            })
            ->addColumn('assigned_to', function ($permission) {
                $roles = $permission->roles->take(3);
                $totalRoles = $permission->roles->count();

                if ($totalRoles === 0) {
                    return '<span class="text-muted">No roles assigned</span>';
                }

                $badges = '';
                foreach ($roles as $role) {
                    $badges .= '<span class="badge bg-label-primary me-1">' . e($role->name) . '</span>';
                }

                if ($totalRoles > 3) {
                    $remaining = $totalRoles - 3;
                    $badges .= '<span class="badge bg-label-secondary">+' . $remaining . ' more</span>';
                }

                return $badges;
            })
            ->addColumn('users_count', function ($permission) {
                $count = $permission->users_count;
                $badgeClass = $count > 0 ? 'bg-label-info' : 'bg-label-secondary';
                return '<span class="badge ' . $badgeClass . '">' . $count . ' users</span>';
            })
            ->addColumn('status', function ($permission) {
                $isActive = $permission->is_active ?? true;
                $statusClass = $isActive ? 'bg-label-success' : 'bg-label-secondary';
                $statusText = $isActive ? 'Active' : 'Inactive';
                return '<span class="badge ' . $statusClass . '">' . $statusText . '</span>';
            })
            ->addColumn('created_date', function ($permission) {
                return $permission->created_at->format('d M Y');
            })
            ->addColumn('action', function ($permission) {
                $viewBtn = '<button type="button" class="btn btn-icon btn-text-info rounded-pill btn-sm view-permission"
                           data-id="' . e($permission->id) . '"
                           data-bs-toggle="tooltip"
                           data-bs-placement="top"
                           title="View Details">
                    <i class="bx bx-show"></i>
                </button>';

                $editBtn = '<button type="button" class="btn btn-icon btn-text-secondary rounded-pill btn-sm edit-permission"
                           data-id="' . e($permission->id) . '"
                           data-bs-toggle="tooltip"
                           data-bs-placement="top"
                           title="Edit Permission">
                    <i class="bx bx-edit"></i>
                </button>';

                $rolesBtn = '<button type="button" class="btn btn-icon btn-text-warning rounded-pill btn-sm manage-roles"
                            data-id="' . e($permission->id) . '"
                            data-bs-toggle="tooltip"
                            data-bs-placement="top"
                            title="Manage Roles">
                    <i class="bx bx-users"></i>
                </button>';

                $deleteBtn = '<button type="button" class="btn btn-icon btn-text-danger rounded-pill btn-sm delete-permission"
                            data-id="' . e($permission->id) . '"
                            data-bs-toggle="tooltip"
                            data-bs-placement="top"
                            title="Delete">
                    <i class="bx bx-trash"></i>
                </button>';

                return '<div class="d-inline-block text-nowrap">' .
                       $viewBtn . $editBtn . $rolesBtn . $deleteBtn .
                       '</div>';
            })
            ->filterColumn('module', function($query, $keyword) {
                $query->where('module', 'like', "%{$keyword}%");
            })
            ->rawColumns(['assigned_to', 'users_count', 'status', 'action'])
            ->make(true);
    }

    /**
     * Store a newly created permission.
     */
    public function store(StorePermissionRequest $request)
    {
        try {
            DB::beginTransaction();

            // Create permission
            $permission = Permission::create([
                'name' => $request->name,
                'guard_name' => 'web',
                'module' => $request->module,
                'description' => $request->description,
                'category' => $request->category,
                'is_active' => $request->is_active ?? true,
            ]);

            // Assign to roles if provided
            if ($request->roles) {
                $roles = Role::whereIn('name', $request->roles)->get();
                foreach ($roles as $role) {
                    $role->givePermissionTo($permission);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Permission created successfully',
                'permission' => $permission->load('roles')
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Permission creation failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error creating permission: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified permission.
     */
    public function show(Permission $permission)
    {
        $permission->load(['roles.users']);

        return response()->json([
            'success' => true,
            'permission' => $permission,
            'stats' => $permission->getStats(),
            'roles' => $permission->getRolesWithDetails(),
        ]);
    }

    /**
     * Show the form for editing the specified permission.
     */
    public function edit(Permission $permission)
    {
        try {
            $permission->load('roles');
            return response()->json([
                'success' => true,
                'permission' => $permission,
                'assigned_roles' => $permission->roles->pluck('name')->toArray()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading permission: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified permission.
     */
    public function update(UpdatePermissionRequest $request, Permission $permission)
    {
        try {
            DB::beginTransaction();

            // Update permission data
            $permission->update([
                'name' => $request->name,
                'module' => $request->module,
                'description' => $request->description,
                'category' => $request->category,
                'is_active' => $request->is_active ?? true,
            ]);

            // Update role assignments if provided
            if ($request->has('roles')) {
                $oldRoles = $permission->roles->pluck('name')->toArray();

                // Remove from all current roles
                foreach ($permission->roles as $role) {
                    $role->revokePermissionTo($permission);
                }

                // Add to new roles
                if ($request->roles) {
                    $newRoles = Role::whereIn('name', $request->roles)->get();
                    foreach ($newRoles as $role) {
                        $role->givePermissionTo($permission);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Permission updated successfully',
                'permission' => $permission->fresh()->load('roles')
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Permission update failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error updating permission: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle permission status (activate/deactivate).
     */
    public function toggleStatus(Request $request, Permission $permission)
    {
        try {
            $action = $request->action;

            if ($action === 'activate') {
                $permission->activate();
                $message = 'Permission activated successfully';
            } elseif ($action === 'deactivate') {
                $permission->deactivate();
                $message = 'Permission deactivated successfully';
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid action'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'status' => $permission->is_active
            ]);

        } catch (\Exception $e) {
            Log::error('Permission status toggle failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error updating permission status'
            ], 500);
        }
    }

    /**
     * Remove the specified permission.
     */
    public function destroy(Permission $permission)
    {
        try {
            // Check if permission can be deleted
            $canDelete = $permission->canBeDeleted();

            if (!$canDelete['can_delete']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete permission',
                    'errors' => $canDelete['errors']
                ], 403);
            }

            $permission->delete();

            return response()->json([
                'success' => true,
                'message' => 'Permission deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Permission deletion failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error deleting permission: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create permissions for a module.
     */
    public function createModulePermissions(Request $request)
    {
        $request->validate([
            'module' => 'required|string|max:255',
            'actions' => 'required|array',
            'actions.*' => 'string|in:view,create,edit,delete,manage,export,import,approve',
            'description' => 'nullable|string|max:500',
        ]);

        try {
            $permissions = Permission::createModulePermissions(
                $request->module,
                $request->actions,
                $request->description
            );

            return response()->json([
                'success' => true,
                'message' => 'Module permissions created successfully',
                'permissions' => count($permissions),
                'created' => $permissions
            ]);

        } catch (\Exception $e) {
            Log::error('Module permission creation failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error creating module permissions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk operations on permissions.
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:activate,deactivate,delete',
            'permission_ids' => 'required|array',
            'permission_ids.*' => 'exists:permissions,id'
        ]);

        try {
            $permissions = Permission::whereIn('id', $request->permission_ids)->get();
            $successCount = 0;

            foreach ($permissions as $permission) {
                switch ($request->action) {
                    case 'activate':
                        $permission->activate();
                        $successCount++;
                        break;
                    case 'deactivate':
                        $permission->deactivate();
                        $successCount++;
                        break;
                    case 'delete':
                        $canDelete = $permission->canBeDeleted();
                        if ($canDelete['can_delete']) {
                            $permission->delete();
                            $successCount++;
                        }
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
                'message' => "{$successCount} permissions {$actionText} successfully"
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
     * Get permission statistics.
     */
    private function getPermissionStatistics(): array
    {
        return [
            'total_permissions' => Permission::excludeModules()->count(),
            'active_permissions' => Permission::excludeModules()->active()->count(),
            'inactive_permissions' => Permission::excludeModules()->inactive()->count(),
            'assigned_permissions' => Permission::excludeModules()->has('roles')->count(),
            'unassigned_permissions' => Permission::excludeModules()->doesntHave('roles')->count(),
            'total_modules' => Permission::excludeModules()->distinct('module')->count('module'),
        ];
    }

    /**
     * Get dashboard statistics for permissions.
     */
    public function getStatistics()
    {
        try {
            $stats = $this->getPermissionStatistics();

            // Calculate percentages
            $total = $stats['total_permissions'];
            $stats['active_percentage'] = $total > 0 ? round(($stats['active_permissions'] / $total) * 100) : 0;
            $stats['assigned_percentage'] = $total > 0 ? round(($stats['assigned_permissions'] / $total) * 100) : 0;

            return response()->json([
                'success' => true,
                'statistics' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get permission statistics: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error retrieving statistics'
            ], 500);
        }
    }
}
