<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateUsersSeeder extends Seeder
{
    public function run(): void
    {
        // Sample users data
        $users = [
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Ali bin Ahmad',
                'email' => 'ali.ahmad@gmail.com',
                'ic_number' => '1111111111',
                'tel_bimbit' => '0123456978',
                'tel_pejabat' => '035561234',
                'kod_sektor' => 10300,
                'kod_jawatan' => 1,
                'kod_bah' => 103001,
                'kod_sec' => 10300101,
                'kod_unit' => 1030010101,
                'kodjantina' => 'L',
                'kod_skim' => 'SKM001',
                'kod_kump_khidmat' => 'KK01',
                'nama_gred' => 'Gred 41',
                'password' => Hash::make('111111111111'),
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Siti binti Hassan',
                'email' => 'siti.hassan@gmail.com',
                'ic_number' => '2222222222',
                'tel_bimbit' => '0187654321',
                'tel_pejabat' => '035567890',
                'kod_sektor' => 10200,
                'kod_jawatan' => 2,
                'kod_bah' => 102001,
                'kod_sec' => 10200101,
                'kod_unit' => 1020010101,
                'kodjantina' => 'P',
                'kod_skim' => 'SKM002',
                'kod_kump_khidmat' => 'KK02',
                'nama_gred' => 'Gred 44',
                'password' => Hash::make('111111111111'),
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // Insert users
        foreach ($users as $user) {
            DB::table('users')->updateOrInsert(
                ['email' => $user['email']], // Check by email
                $user
            );
        }

        $this->command->info('Sample users created successfully!');
    }
}