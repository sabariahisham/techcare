<?php

namespace App\Http\Controllers\Configuration;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class ModuleController extends Controller
{
    /**
     * Display a listing of modules.
     */
    public function index()
    {
        return view('configuration.modules.index');
    }

    /**
     * Get modules data for DataTables.
     */
    public function getModules(Request $request)
    {
        if ($request->ajax()) {
            $modules = Module::with(['creator', 'updater'])
                ->select(['id', 'uuid', 'module_name', 'created_at', 'updated_at', 'created_by', 'updated_by'])
                ->get();

            return DataTables::of($modules)
                ->addIndexColumn()
                ->addColumn('actions', function ($module) {
                    return '
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-sm btn-primary" onclick="editModule(\'' . $module->uuid . '\')">
                                <i class="ti ti-edit"></i> Edit
                            </button>
                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteModule(\'' . $module->uuid . '\')">
                                <i class="ti ti-trash"></i> Delete
                            </button>
                        </div>
                    ';
                })
                ->addColumn('created_by_name', function ($module) {
                    return $module->creator ? $module->creator->nama : '-';
                })
                ->addColumn('updated_by_name', function ($module) {
                    return $module->updater ? $module->updater->nama : '-';
                })
                ->editColumn('created_at', function ($module) {
                    return $module->created_at ? $module->created_at->format('d/m/Y H:i:s') : '-';
                })
                ->editColumn('updated_at', function ($module) {
                    return $module->updated_at ? $module->updated_at->format('d/m/Y H:i:s') : '-';
                })
                ->rawColumns(['actions'])
                ->make(true);
        }

        return response()->json(['error' => 'Invalid request'], 400);
    }

    /**
     * Store a new module.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:module_list,module_name,NULL,id,deleted_at,NULL',
            'status' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Create the module (created_by will be set automatically by the model)
            $module = Module::create([
                'module_name' => $request->name,
            ]);

            // Automatically create 4 permissions for the module
            $permissions = [
                [
                    'name' => $request->name . '.create',
                    'guard_name' => 'web',
                    'module' => $request->name,
                    'description' => 'Create ' . $request->name,
                    'is_active' => (bool)$request->status,
                    'sort_order' => 1,
                ],
                [
                    'name' => $request->name . '.edit',
                    'guard_name' => 'web',
                    'module' => $request->name,
                    'description' => 'Edit ' . $request->name,
                    'is_active' => (bool)$request->status,
                    'sort_order' => 2,
                ],
                [
                    'name' => $request->name . '.view',
                    'guard_name' => 'web',
                    'module' => $request->name,
                    'description' => 'View ' . $request->name,
                    'is_active' => (bool)$request->status,
                    'sort_order' => 3,
                ],
                [
                    'name' => $request->name . '.delete',
                    'guard_name' => 'web',
                    'module' => $request->name,
                    'description' => 'Delete ' . $request->name,
                    'is_active' => (bool)$request->status,
                    'sort_order' => 4,
                ],
            ];

             // create, edit, view, delete

            foreach ($permissions as $permissionData) {
                // Check if a soft-deleted permission with this name exists
                $existingPermission = Permission::withTrashed()
                    ->where('name', $permissionData['name'])
                    ->where('guard_name', $permissionData['guard_name'])
                    ->first();

                if ($existingPermission && $existingPermission->trashed()) {
                    // Restore the soft-deleted permission and update its data
                    $existingPermission->restore();
                    $existingPermission->update([
                        'module' => $permissionData['module'],
                        'description' => $permissionData['description'],
                        'is_active' => $permissionData['is_active'],
                        'sort_order' => $permissionData['sort_order'],
                        'updated_by' => Auth::id(),
                        'deleted_by' => null,
                    ]);
                } else {
                    // Create new permission
                    $permissionData['created_by'] = Auth::id();
                    Permission::create($permissionData);
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'success' => true,
                'data' => $module
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'success' => false,
                'message' => 'Error creating module: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified module.
     */
    public function edit($id)
    {
        try {
            $module = Module::find($id);

            if (!$module) {
                return response()->json([
                    'status' => 'error',
                    'success' => false,
                    'message' => 'Module not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'success' => true,
                'data' => $module
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'success' => false,
                'message' => 'Error fetching module: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified module.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:module_list,module_name,' . $id . ',id,deleted_at,NULL'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $module = Module::find($id);

            if (!$module) {
                return response()->json([
                    'status' => 'error',
                    'success' => false,
                    'message' => 'Module not found'
                ], 404);
            }

            DB::beginTransaction();

            $oldModuleName = $module->module_name;

            // Update the module (updated_by will be set automatically by the model)
            $module->update([
                'module_name' => $request->name,
            ]);

            // Update related permissions if module name changed
            if ($oldModuleName !== $request->name) {
                $permissions = Permission::where('module', $oldModuleName)->get();

                foreach ($permissions as $permission) {
                    $newPermissionName = str_replace($oldModuleName, $request->name, $permission->name);
                    $permission->update([
                        'name' => $newPermissionName,
                        'module' => $request->name,
                        'description' => str_replace($oldModuleName, $request->name, $permission->description),
                        'updated_by' => Auth::id()
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'success' => true,
                'message' => 'Module updated successfully',
                'data' => $module
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false
            ], 500);
        }
    }

    /**
     * Remove the specified module.
     */
    public function destroy($id)
    {
        try {
            $module = Module::find($id);

            if (!$module) {
                return response()->json([
                    'status' => 'error',
                    'success' => false,
                    'message' => 'Module not found'
                ], 404);
            }

            DB::beginTransaction();

            // Delete related permissions with tracking
            $permissions = Permission::where('module', $module->module_name)->get();
            foreach ($permissions as $permission) {
                // For permissions, we'll update deleted_by before deleting
                $permission->update(['deleted_by' => Auth::id()]);
                $permission->delete();
            }

            // Delete the module (deleted_by will be set automatically by the model)
            $module->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'success' => true,
                'message' => 'Module and related permissions deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'success' => false,
                'message' => 'Error deleting module: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get module details with user tracking information
     */
    public function getModuleDetails($id)
    {
        try {
            $module = Module::with(['creator', 'updater', 'deleter'])->find($id);

            if (!$module) {
                return response()->json([
                    'status' => 'error',
                    'success' => false,
                    'message' => 'Module not found'
                ], 404);
            }

            // Get associated permissions
            $permissions = Permission::where('module', $module->module_name)
                ->select(['id', 'name', 'guard_name', 'module'])
                ->get();

            $moduleData = [
                'id' => $module->id,
                'uuid' => $module->uuid,
                'module_name' => $module->module_name,
                'permissions' => $permissions->map(function($permission) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'guard_name' => $permission->guard_name
                    ];
                })->toArray(),
                'permissions_count' => $permissions->count(),
                'created_at' => $module->created_at ? $module->created_at->format('d/m/Y H:i:s') : null,
                'updated_at' => $module->updated_at ? $module->updated_at->format('d/m/Y H:i:s') : null,
                'deleted_at' => $module->deleted_at ? $module->deleted_at->format('d/m/Y H:i:s') : null,
                'created_by' => [
                    'id' => $module->created_by,
                    'name' => $module->creator ? $module->creator->nama : 'System'
                ],
                'updated_by' => [
                    'id' => $module->updated_by,
                    'name' => $module->updater ? $module->updater->nama : null
                ],
                'deleted_by' => [
                    'id' => $module->deleted_by,
                    'name' => $module->deleter ? $module->deleter->nama : null
                ]
            ];

            return response()->json([
                'status' => 'success',
                'success' => true,
                'data' => $moduleData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'success' => false,
                'message' => 'Error fetching module details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get module activity log (audit trail)
     */
    public function getModuleActivityLog($id)
    {
        try {
            $module = Module::withTrashed()->with(['creator', 'updater', 'deleter'])->find($id);

            if (!$module) {
                return response()->json([
                    'status' => 'error',
                    'success' => false,
                    'message' => 'Module not found'
                ], 404);
            }

            $activities = [];

            // Created activity
            if ($module->created_at && $module->creator) {
                $activities[] = [
                    'action' => 'Created',
                    'user' => $module->creator->nama,
                    'user_id' => $module->created_by,
                    'timestamp' => $module->created_at->format('d/m/Y H:i:s'),
                    'details' => "Module '{$module->module_name}' was created"
                ];
            }

            // Updated activity
            if ($module->updated_at && $module->updater && $module->updated_at != $module->created_at) {
                $activities[] = [
                    'action' => 'Updated',
                    'user' => $module->updater->nama,
                    'user_id' => $module->updated_by,
                    'timestamp' => $module->updated_at->format('d/m/Y H:i:s'),
                    'details' => "Module '{$module->module_name}' was updated"
                ];
            }

            // Deleted activity
            if ($module->deleted_at && $module->deleter) {
                $activities[] = [
                    'action' => 'Deleted',
                    'user' => $module->deleter->nama,
                    'user_id' => $module->deleted_by,
                    'timestamp' => $module->deleted_at->format('d/m/Y H:i:s'),
                    'details' => "Module '{$module->module_name}' was deleted"
                ];
            }

            // Sort activities by timestamp (newest first)
            usort($activities, function($a, $b) {
                return strtotime($b['timestamp']) - strtotime($a['timestamp']);
            });

            return response()->json([
                'status' => 'success',
                'success' => true,
                'data' => [
                    'module' => $module->module_name,
                    'activities' => $activities
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'success' => false,
                'message' => 'Error fetching activity log: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test user tracking functionality
     */
    public function testUserTracking()
    {
        try {
            // Get current user
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Create a test module
            $module = Module::create([
                'module_name' => 'Test Module ' . now()->timestamp,
            ]);

            // Update the module
            $module->update([
                'module_name' => 'Updated Test Module ' . now()->timestamp,
            ]);

            // Get module with relationships
            $moduleWithTracking = Module::with(['creator', 'updater'])->find($module->id);

            // Test permission tracking
            $permission = Permission::create([
                'name' => 'test.permission.' . now()->timestamp,
                'guard_name' => 'web',
                'module' => $moduleWithTracking->module_name,
                'description' => 'Test Permission',
                'category' => 'Test',
                'is_active' => true,
                'sort_order' => 1,
            ]);

            // Get permission with relationships
            $permissionWithTracking = Permission::with(['creator', 'updater'])->find($permission->id);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'current_user' => [
                        'id' => $user->id,
                        'name' => $user->nama,
                        'email' => $user->email
                    ],
                    'module_tracking' => [
                        'id' => $moduleWithTracking->id,
                        'name' => $moduleWithTracking->module_name,
                        'created_by' => $moduleWithTracking->created_by,
                        'creator_name' => $moduleWithTracking->creator ? $moduleWithTracking->creator->nama : null,
                        'updated_by' => $moduleWithTracking->updated_by,
                        'updater_name' => $moduleWithTracking->updater ? $moduleWithTracking->updater->nama : null,
                        'created_at' => $moduleWithTracking->created_at,
                        'updated_at' => $moduleWithTracking->updated_at
                    ],
                    'permission_tracking' => [
                        'id' => $permissionWithTracking->id,
                        'name' => $permissionWithTracking->name,
                        'created_by' => $permissionWithTracking->created_by,
                        'creator_name' => $permissionWithTracking->creator ? $permissionWithTracking->creator->nama : null,
                        'updated_by' => $permissionWithTracking->updated_by,
                        'updater_name' => $permissionWithTracking->updater ? $permissionWithTracking->updater->nama : null,
                        'created_at' => $permissionWithTracking->created_at,
                        'updated_at' => $permissionWithTracking->updated_at
                    ]
                ],
                'message' => 'User tracking test completed successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Test failed: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}
