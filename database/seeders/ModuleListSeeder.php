<?php

namespace Database\Seeders;

use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ModuleListSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing module_list data
        DB::table('module_list')->truncate();

        // Extract unique modules from permissions table and populate module_list
        $uniqueModules = DB::table('permissions')
            ->select('module')
            ->whereNotNull('module')
            ->where('module', '!=', '')
            ->distinct()
            ->pluck('module');

        foreach ($uniqueModules as $module) {
            DB::table('module_list')->insert([
                'uuid' => Str::uuid()->toString(),
                'module_name' => $module,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('Successfully populated module_list with ' . $uniqueModules->count() . ' unique modules from permissions table.');
    }
}
