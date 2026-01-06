<?php

require_once 'bootstrap/app.php';

use App\Models\Role;
use App\Models\Permission;
use App\Models\User;

$app = app();

echo "=== EXISTING ROLES ===\n";
$roles = Role::all(['id', 'name', 'description']);
foreach ($roles as $role) {
    echo "ID: {$role->id}, Name: {$role->name}, Description: {$role->description}\n";
}

echo "\n=== EXISTING PERMISSIONS ===\n";
$permissions = Permission::all(['id', 'name', 'description']);
foreach ($permissions as $permission) {
    echo "ID: {$permission->id}, Name: {$permission->name}, Description: {$permission->description}\n";
}

echo "\n=== CHECKING FOR EXISTING SUPERADMIN USER ===\n";
$superadmin = User::where('nama', 'Superadmin')->first();
if ($superadmin) {
    echo "Superadmin user already exists: ID {$superadmin->id}, Email: {$superadmin->email}\n";
} else {
    echo "No Superadmin user found.\n";
}
