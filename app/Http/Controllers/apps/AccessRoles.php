<?php

namespace App\Http\Controllers\apps;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;

class AccessRoles extends Controller
{
  public function index()
  {
    // get all roles
    // $roles = Role::all();
    $roles = Role::with('users')->get();

    // dd($roles);

    return view('roles.index', compact('roles'));
    // return view('content.apps.app-access-roles');
  }
}
