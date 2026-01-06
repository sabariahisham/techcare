<?php

use App\Models\User;

$user = User::where('nama', 'Superadmin')->with('roles.permissions')->first();

if ($user) {
    echo "=== SUPERADMIN USER VERIFICATION ===\n";
    echo "User: {$user->nama}\n";
    echo "Email: {$user->email}\n";
    echo "ID: {$user->id}\n";
    echo "Active: " . ($user->kodaktif ? 'Yes' : 'No') . "\n";
    echo "Roles: " . $user->getRoleNames()->implode(', ') . "\n";
    echo "Total Permissions: " . $user->getAllPermissions()->count() . "\n";

    echo "\n=== SAMPLE PERMISSIONS ===\n";
    $permissions = $user->getAllPermissions()->take(10);
    foreach ($permissions as $permission) {
        echo "- {$permission->name}\n";
    }

    if ($user->getAllPermissions()->count() > 10) {
        echo "... and " . ($user->getAllPermissions()->count() - 10) . " more permissions\n";
    }
} else {
    echo "Superadmin user not found!\n";
}
