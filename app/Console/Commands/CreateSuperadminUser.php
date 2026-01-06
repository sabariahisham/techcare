<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use App\Models\Permission;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateSuperadminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:superadmin {--password=password123}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a Superadmin user with Administrator role and all permissions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Superadmin user creation...');

        // Check existing data
        $this->info('Checking existing roles and permissions...');
        $roles = Role::all(['id', 'name', 'description']);
        $permissions = Permission::all(['id', 'name', 'description']);

        $this->info("Found {$roles->count()} roles and {$permissions->count()} permissions");

        // Check if Administrator role exists
        $adminRole = Role::where('name', 'Administrator')->first();

        if (!$adminRole) {
            $this->info('Creating Administrator role...');
            $adminRole = Role::create([
                'name' => 'Administrator',
                'guard_name' => 'web',
                'description' => 'Administrator with full system access',
                'is_active' => true,
                'color' => '#e74c3c',
                'icon' => 'bx-crown'
            ]);
            $this->info('Administrator role created successfully');
        } else {
            $this->info('Administrator role already exists');
        }

        // Assign all permissions to Administrator role
        $allPermissions = Permission::all();
        if ($allPermissions->count() > 0) {
            $adminRole->syncPermissions($allPermissions);
            $this->info("Assigned {$allPermissions->count()} permissions to Administrator role");
        }

        // Check if Superadmin user exists
        $superadmin = User::where('nama', 'Superadmin')->first();

        if ($superadmin) {
            $this->info('Superadmin user already exists');
            // Ensure the user has Administrator role
            if (!$superadmin->hasRole('Administrator')) {
                $superadmin->assignRole($adminRole);
                $this->info('Administrator role assigned to existing Superadmin user');
            }
        } else {
            $this->info('Creating Superadmin user...');
            $password = $this->option('password');

            $superadmin = User::create([
                'nama' => 'Superadmin',
                'email' => 'superadmin@ehelp.com',
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'kodaktif' => true,
                'created_by' => 1,
            ]);

            // Assign Administrator role
            $superadmin->assignRole($adminRole);

            $this->info('Superadmin user created successfully');
            $this->info("Email: {$superadmin->email}");
            $this->info("Password: {$password}");
        }

        // Display summary
        $this->info("\n=== SUMMARY ===");
        $this->info("User: {$superadmin->nama} (ID: {$superadmin->id})");
        $this->info("Email: {$superadmin->email}");
        $this->info("Roles: " . $superadmin->getRoleNames()->implode(', '));
        $this->info("Total Permissions: " . $superadmin->getAllPermissions()->count());

        $this->info("\nSuperadmin user setup completed successfully!");

        return 0;
    }
}
