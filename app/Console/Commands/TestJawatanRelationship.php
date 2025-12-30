<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestJawatanRelationship extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-jawatan-relationship';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Jawatan Relationship...');

        // Check RefJawatan data
        $jawatan = \App\Models\RefJawatan::first();
        if ($jawatan) {
            $this->info('RefJawatan sample:');
            $this->line('kod_jawatan: ' . $jawatan->kod_jawatan);
            $this->line('nama_jawatan: ' . $jawatan->nama_jawatan);
        } else {
            $this->error('No RefJawatan records found!');
        }

        // Check User with jawatan
        $user = \App\Models\User::whereNotNull('kod_jawatan')->with('jawatan')->first();
        if ($user) {
            $this->info('User sample:');
            $this->line('nama: ' . $user->nama);
            $this->line('kod_jawatan: ' . ($user->kod_jawatan ?? 'null'));
            $this->line('jawatan relationship: ' . ($user->jawatan ? 'Found' : 'Not Found'));
            if ($user->jawatan) {
                $this->line('jawatan nama: ' . $user->jawatan->nama_jawatan);
            }
            $this->line('jawatan_name accessor: ' . $user->jawatan_name);
        } else {
            $this->error('No User with kod_jawatan found!');
        }
    }
}
