<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UpdateUserPasswordSeeder extends Seeder
{
    public function run(): void
    {
        // Update semua user yang ada dengan password baru
        DB::table('users')->update([
            'password' => Hash::make('111111111111'), // tukar ikut password pilihan
        ]);

        $this->command->info('All user passwords updated successfully!');

        // ATAU kalau nak setkan ikut user tertentu
        /*
        DB::table('users')
            ->where('email', 'user@example.com')
            ->update(['password' => Hash::make('secret')]);
        */
    }
}
