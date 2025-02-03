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
        /*
        seed dummy module list as below:
        Admin Management
        User Management
        Report Management
        Role Management
        */

        $modules = [
            [
                'uuid' => Str::uuid()->toString(),
                'module_name' => 'Admin Management',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'uuid' => Str::uuid()->toString(),
                'module_name' => 'User Management',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'uuid' => Str::uuid()->toString(),
                'module_name' => 'Report Management',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'uuid' => Str::uuid()->toString(),
                'module_name' => 'Role Management',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];

        DB::table('module_list')->insert($modules);
    }
}
