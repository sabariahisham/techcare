<?php

namespace App\Http\Controllers\configuration;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class UserManagementController extends Controller
{
    public function index()
    {
        // Get roles
        $roles = Role::whereNotIn('name', ['Administrator'])->get();

        // Get total users count
        $totalUsers = User::count();

        // Get active users count
        $activeUsers = User::where('kodaktif', 1)->count();

        // Get inactive users count
        $inactiveUsers = User::where('kodaktif', 0)->count();

        // Calculate percentages
        $activePercentage = $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100) : 0;
        $inactivePercentage = $totalUsers > 0 ? round(($inactiveUsers / $totalUsers) * 100) : 0;

        Log::debug('Roles being passed to view:', ['roles' => $roles->toArray()]);
        return view('user_management.index', compact('roles', 'totalUsers', 'activeUsers', 'inactiveUsers', 'activePercentage', 'inactivePercentage'));
    }

    public function getData(Request $request)
    {
        $users = User::with(['roles', 'sektor', 'bahagian', 'seksyen', 'unit', 'jawatan']);

        return DataTables::of($users)
            ->addIndexColumn()
            ->addColumn('role', function ($user) {
                return $user->roles->first() ? e($user->roles->first()->name) : '-';
            })
            ->addColumn('sektor_nama', function ($user) {
                return $user->sektor ? $user->sektor->nama_sektor : '-';
            })
            ->addColumn('bahagian_nama', function ($user) {
                return $user->bahagian ? $user->bahagian->nama_bah : '-';
            })
            ->addColumn('seksyen_nama', function ($user) {
                return $user->seksyen ? $user->seksyen->nama_sec : '-';
            })
            ->addColumn('unit_nama', function ($user) {
                return $user->unit ? $user->unit->nama_unit : '-';
            })
            ->addColumn('action', function ($user) {
                // Only show impersonate button - exclude edit and delete buttons
                $impersonateBtn = '';
                $currentUser = Auth::user();

                if ($currentUser && $currentUser->canImpersonate() && $user->canBeImpersonated()) {
                    $impersonateUrl = route('impersonate', ['user' => $user->id]);
                    $impersonateBtn = '<a href="'.e($impersonateUrl).'" class="btn btn-icon btn-text-warning rounded-pill btn-sm impersonate-user" data-bs-toggle="tooltip" data-bs-placement="top" title="Impersonate User" data-user-name="'.e($user->nama).'">
                        <i class="bx bx-user-check"></i>
                    </a>';
                }

                return '<div class="d-inline-block text-nowrap">' . $impersonateBtn . '</div>';
            })
            ->filterColumn('role', function($query, $keyword) {
                $query->whereHas('roles', function($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('sektor_nama', function($query, $keyword) {
                $query->whereHas('sektor', function($q) use ($keyword) {
                    $q->where('nama_sektor', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('bahagian_nama', function($query, $keyword) {
                $query->whereHas('bahagian', function($q) use ($keyword) {
                    $q->where('nama_bah', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('seksyen_nama', function($query, $keyword) {
                $query->whereHas('seksyen', function($q) use ($keyword) {
                    $q->where('nama_sec', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('unit_nama', function($query, $keyword) {
                $query->whereHas('unit', function($q) use ($keyword) {
                    $q->where('nama_unit', 'like', "%{$keyword}%");
                });
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'role' => 'required|string',
            'agency' => 'required|string|max:255'
        ]);

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make('11111111'), // Default password
                'agency' => $request->agency,
            ]);

            // Assign role to user
            $role = Role::where('name', $request->role)->first();
            if ($role) {
                $user->assignRole($role);
            }

            return response()->json([
                'success' => true,
                'message' => 'User created successfully'
            ]);
        } catch (\Exception $e) {
          return response()->json([
                'success' => false,
                'message' => 'Error creating user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        try {
            $user = User::with('roles')->findOrFail($id);
            return response()->json($user);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$id,
            'role' => 'required|string',
            'agency' => 'required|string|max:255'
        ]);

        try {
            $user = User::findOrFail($id);
            $user->update([
                'name' => $request->name,
                'email' => $request->email,
                'agency' => $request->agency,
            ]);

            // Update role
            $role = Role::where('name', $request->role)->first();
            if ($role) {
                $user->syncRoles([$role]);
            }

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $user = User::findOrFail($id);
            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting user: ' . $e->getMessage()
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
