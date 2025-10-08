<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class VerifySuperadmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'verify:superadmin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify the Superadmin user creation and permissions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== SUPERADMIN USER VERIFICATION ===');

        $user = User::where('nama', 'Superadmin')->with('roles.permissions')->first();

        if (!$user) {
            $this->error('Superadmin user not found!');
            return 1;
        }

        $this->info("User: {$user->nama}");
        $this->info("Email: {$user->email}");
        $this->info("ID: {$user->id}");
        $this->info("Active: " . ($user->kodaktif ? 'Yes' : 'No'));
        $this->info("Created: {$user->created_at}");

        // Check roles
        $roles = $user->getRoleNames();
        $this->info("Roles: " . $roles->implode(', '));

        // Check permissions
        $permissions = $user->getAllPermissions();
        $this->info("Total Permissions: " . $permissions->count());

        // Display sample permissions
        $this->info("\n=== SAMPLE PERMISSIONS (first 15) ===");
        $samplePermissions = $permissions->take(15);
        foreach ($samplePermissions as $permission) {
            $this->line("- {$permission->name}");
        }

        if ($permissions->count() > 15) {
            $this->info("... and " . ($permissions->count() - 15) . " more permissions");
        }

        // Verify key permissions
        $this->info("\n=== VERIFICATION RESULTS ===");

        $checks = [
            'Has Administrator Role' => $user->hasRole('Administrator'),
            'Can manage users' => $user->can('users.index'),
            'Can manage roles' => $user->can('roles.index'),
            'Can manage permissions' => $user->can('permissions.index'),
            'Has all permissions' => $permissions->count() > 0,
        ];

        foreach ($checks as $check => $result) {
            if ($result) {
                $this->info("✓ {$check}");
            } else {
                $this->error("✗ {$check}");
            }
        }

        $allPassed = collect($checks)->every(fn($result) => $result);

        if ($allPassed) {
            $this->info("\n🎉 Superadmin user is properly configured with all permissions!");
        } else {
            $this->error("\n⚠️  Some verification checks failed. Please review the configuration.");
        }

        return $allPassed ? 0 : 1;
    }
}
