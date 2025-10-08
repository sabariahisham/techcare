<?php

namespace App\Http\Controllers;

use App\Models\Module;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ModuleListController extends Controller
{
    /**
     * Standardized error response
     */
    private function errorResponse($message = 'An error occurred', $statusCode = 500)
    {
        return response()->json([
            'status' => 'error',
            'message' => $message
        ], $statusCode);
    }

    /**
     * Standardized success response
     */
    private function successResponse($message = 'Operation successful', $data = null)
    {
        $response = [
            'status' => 'success',
            'message' => $message
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response);
    }

    /**
     * Sanitize input data to prevent XSS and injection attacks
     */
    private function sanitizeInput($input)
    {
        if (is_null($input)) {
            return null;
        }

        // Remove any potential XSS attempts
        $sanitized = strip_tags($input);

        // Trim whitespace
        $sanitized = trim($sanitized);

        // Remove multiple consecutive spaces
        $sanitized = preg_replace('/\s+/', ' ', $sanitized);

        // Only allow alphanumeric, spaces, hyphens and underscores
        $sanitized = preg_replace('/[^a-zA-Z0-9\s\-\_]/', '', $sanitized);

        return $sanitized;
    }
    public function index()
    {
        $totalModules = Module::count();
        return view('module.list.index', compact('totalModules'));
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


    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        try {
            // Sanitize input data
            $sanitizedInput = $this->sanitizeInput($request->input('modalModuleName'));
            $request->merge(['modalModuleName' => $sanitizedInput]);

            $request->validate([
                'modalModuleName' => [
                    'required',
                    'string',
                    'max:100',
                    'min:3',
                    'regex:/^[a-zA-Z0-9\s\-\_]+$/',
                    'unique:module_list,module_name'
                ]
            ]);

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

        return $this->successResponse('Module created successfully', $module);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Module creation error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->except(['_token'])
            ]);

            return $this->errorResponse('Unable to create module', 500);
        }
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        try {
            $module = Module::find($id);

            if (!$module) {
                return $this->errorResponse('Module not found', 404);
            }

            return $this->successResponse('Module data retrieved successfully', $module);

        } catch (\Exception $e) {
            Log::error('Module edit error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'module_id' => $id
            ]);

            return $this->errorResponse('Unable to retrieve module data', 500);
        }
    }


    public function update(Request $request, string $id)
    {
      try {
          // Validate ID parameter
          if (!is_numeric($id) || $id <= 0) {
              return $this->errorResponse('Invalid module ID', 400);
          }

          // Find the module first
          $module = Module::find($id);
          if (!$module) {
              return $this->errorResponse('Module not found', 404);
          }

          // Sanitize input data
          $sanitizedInput = $this->sanitizeInput($request->input('editModuleName'));
          $request->merge(['editModuleName' => $sanitizedInput]);

          // Validate the request with custom rule to check for duplicate names
          $request->validate([
              'editModuleName' => [
                  'required',
                  'string',
                  'max:100',
                  'min:3',
                  'regex:/^[a-zA-Z0-9\s\-\_]+$/',
                  'unique:module_list,module_name,' . $id . ',id'
              ]
          ]);

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

      return $this->successResponse('Module updated successfully', $module);

      } catch (\Illuminate\Validation\ValidationException $e) {
          return response()->json([
              'status' => 'error',
              'message' => 'Validation failed',
              'errors' => $e->errors()
          ], 422);
      } catch (\Exception $e) {
          Log::error('Module update error: ' . $e->getMessage(), [
              'trace' => $e->getTraceAsString(),
              'request' => $request->except(['_token']),
              'module_id' => $id
          ]);

          return $this->errorResponse('Unable to update module', 500);
      }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            // Validate ID parameter
            if (!is_numeric($id) || $id <= 0) {
                return $this->errorResponse('Invalid module ID', 400);
            }

            $module = Module::find($id);

            if (!$module) {
                return response()->json(['success' => false, 'message' => 'Module not found']);
            }

            // Delete associated permissions
            Permission::where('module', $module->module_name)->delete();

            // Clear the permissions cache
            app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

            // Delete the module
            $module->delete();

            return response()->json(['success' => true, 'message' => 'Module deleted successfully']);

        } catch (\Exception $e) {
            Log::error('Module deletion error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'module_id' => $id
            ]);

            return response()->json(['success' => false, 'message' => 'Unable to delete module']);
        }
    }
}
