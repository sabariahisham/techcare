<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // $roles = Role::with('users')->orderBy('updated_at', 'desc')->get();
        $roles = Role::with('users')->get();

        // $permissions = Permission::select('name', 'module')
        //     ->get()
        //     ->map(function($permission) {
        //         // Extract just the action part after the dot
        //         $permission->name = substr(strrchr($permission->name, "."), 1);
        //         return $permission;
        //     })
        //     ->groupBy('module');

        $permissions = Permission::select('name', 'module')
        ->get()
        ->groupBy('module');  // Remove the map function that was modifying the name

        // check if any modules exist
        $hasModules = $permissions->count() > 0;

      return view('roles.index', compact('roles', 'permissions', 'hasModules'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
      // dd($request->all());
        // Validate the request
        $request->validate([
            'modalRoleName' => 'required|string|max:255',
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        try {
            $role = Role::create(['name' => $request->modalRoleName]);
            $role->syncPermissions($request->permissions);

            return response()->json([
                'status' => 'success',
                'message' => 'Role created successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get permissions assigned to a specific role.
     */
    public function getRolePermissions($id)
    {
        $role = Role::findById($id);
        $permissions = $role->permissions->pluck('name');

        return response()->json([
            'role' => $role,
            'permissions' => $permissions
        ]);
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
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Role $role)
    {
        try {
            $request->validate([
                'modalRoleName' => 'required|string|max:255',
                'permissions' => 'required|array',
                'permissions.*' => 'exists:permissions,name',
            ]);

            $role->update(['name' => $request->modalRoleName]);
            $role->syncPermissions($request->permissions);

            return response()->json([
                'status' => 'success',
                'message' => 'Role updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        try {
            $role->delete();
            return response()->json([
                'success' => true,
                'message' => 'Role deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to delete role'
            ], 500);
        }
    }
}
