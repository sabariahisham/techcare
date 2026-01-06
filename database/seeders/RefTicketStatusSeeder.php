<?php

namespace Database\Seeders;

use App\Models\RefTicketStatus;
use Illuminate\Database\Seeder;

class RefTicketStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['st_name' => 'Aduan Baru',      'st_desc' => 'Tiket baru diterima.'],
            ['st_name' => 'Aduan Diproses',  'st_desc' => 'Tiket sedang dalam tindakan.'],
            ['st_name' => 'Serahan Kepada',  'st_desc' => 'Tiket diserahkan kepada pegawai lain.'],
            ['st_name' => 'Aduan Dirujuk',   'st_desc' => 'Tiket dirujuk kepada bahagian berkaitan.'],
            ['st_name' => 'Aduan DiBatal',   'st_desc' => 'Tiket telah dibatalkan.'],
            ['st_name' => 'Aduan Selesai',   'st_desc' => 'Tiket selesai diproses.'],
        ];

        foreach ($statuses as $status) {
            RefTicketStatus::firstOrCreate(['st_name' => $status['st_name']], $status);
        }
    }
}
