<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Extract unique modules from permissions table and populate module_list
        $uniqueModules = DB::table('permissions')
            ->select('module')
            ->whereNotNull('module')
            ->where('module', '!=', '')
            ->distinct()
            ->pluck('module');

        foreach ($uniqueModules as $module) {
            // Check if module already exists in module_list
            $exists = DB::table('module_list')
                ->where('module_name', $module)
                ->exists();

            if (!$exists) {
                DB::table('module_list')->insert([
                    'uuid' => Str::uuid(),
                    'module_name' => $module,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove all entries from module_list that were populated from permissions
        DB::table('module_list')->truncate();
    }
};
