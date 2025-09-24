<?php

namespace App\Http\Controllers;

use App\Models\Module;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class ModuleListController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('module.list.index');
    }

    public function getModules()
    {
        $modules = Module::select(['module_list.id', 'module_list.module_name', 'module_list.created_at', 'module_list.updated_at'])
        ->leftJoin('permissions', 'permissions.module', '=', 'module_list.module_name')
        ->groupBy('module_list.id', 'module_list.module_name', 'module_list.created_at', 'module_list.updated_at')
        ->orderBy('module_list.updated_at', 'desc')
        ->get();

        return response()->json([
            'data' => $modules
        ]);
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
        $moduleExists = Module::where('module_name', $request->modalModuleName)->exists();

        if ($moduleExists) {
            return response()->json([
                'status' => 'error',
                'message' => 'Module already exists'
            ], 422);
        }

        $module = Module::create([
            'module_name' => $request->modalModuleName
        ]);

        // if $module is created successfully, create default permissions for the module: view, create, edit, delete
        if ($module) {

          // format $module->module_name to lowercase and replace spaces with dashes
          $request->module_name = strtolower(str_replace(' ', '-', $module->module_name));

          $permissions = [
              [
                  'name' => $request->module_name . '.view',
                  'guard_name' => 'web',
                  'module' => $module->module_name
              ],
              [
                  'name' => $request->module_name . '.create',
                  'guard_name' => 'web',
                  'module' => $module->module_name
              ],
              [
                  'name' => $request->module_name . '.edit',
                  'guard_name' => 'web',
                  'module' => $module->module_name
              ],
              [
                  'name' => $request->module_name . '.delete',
                  'guard_name' => 'web',
                  'module' => $module->module_name
              ]
          ];

          foreach ($permissions as $permission) {
              Permission::create($permission);
          }

          // Clear the permissions cache
          app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Module created successfully',
            'data' => $module
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
    public function update(Request $request, string $id)
    {
      $module = Module::find($id);
      if (!$module) {
          return response()->json([
              'status' => 'error',
              'message' => 'Module not found'
          ], 404);
      }
      $moduleExists = Module::where('module_name', $request->editModuleName)
          ->where('id', '!=', $id)
          ->exists();
      if ($moduleExists) {
          return response()->json([
              'status' => 'error',
              'message' => 'Module name already exists'
          ], 422);
      }

      $oldModuleName = $module->module_name;
      $module->module_name = $request->editModuleName;
      $module->save();

      // format $request->editModuleName to lowercase and replace spaces with dashes
      $newModuleNameForPermission = strtolower(str_replace(' ', '-', $request->editModuleName));

      // Update permission name and module name in permissions table by looping through all related permissions
      $permissions = Permission::where('module', $oldModuleName)->get();

      foreach ($permissions as $permission) {
          $permission->name = $newModuleNameForPermission . '.' . explode('.', $permission->name)[1];
          $permission->module = $request->editModuleName;
          $permission->save();
      }

      // Clear the permissions cache
      app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

      return response()->json([
          'status' => 'success',
          'message' => 'Module updated successfully',
          'data' => $module
      ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $module = Module::find($id);

        /*
         * Checks if the module exists. If it does, it deletes all permissions
         * associated with the module and then deletes the module itself.
         */
        if ($module) {
          Permission::where('module', $module->module_name)->delete();

          // Clear the permissions cache
          app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

          $module->delete();

          return response()->json(['success' => true, 'message' => 'Module deleted successfully.']);
        } else {
          return response()->json(['success' => false, 'message' => 'Module not found.']);
        }
    }
}
