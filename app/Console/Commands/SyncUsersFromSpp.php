<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncUsersFromSpp extends Command
{
    /**
     * Nama command.
     *
     * Jalankan dengan: php artisan sync:users-spp
     */
    protected $signature = 'sync:users-spp';

    protected $description = 'Sync data users dari spp_db.users ke ehelp.users';

    public function handle()
    {
        $this->info('Mula sync dari spp_db.users ke ehelp.users ...');

        // Ambil semua data dari spp_db
        $sppUsers = DB::connection('spp')->table('users')->get();
        $this->info('Jumlah rekod dijumpai: ' . $sppUsers->count());

        foreach ($sppUsers as $user) {

            // Hanya ambil field yang sepadan dengan struktur ehelp.users
            $data = [
                'id'                => $user->id,
                'uuid'              => $user->uuid,
                'id_pgn_ldap'       => $user->id_pgn_ldap ?? '',
                'email'             => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'password'          => $user->password,
                'nama'              => $user->nama,
                'no_kp'             => $user->no_kp ?? '',
                'kodjantina'        => $user->kodjantina,
                'kod_skim'          => $user->kod_skim,
                'kod_kump_khidmat'  => $user->kod_kump_khidmat,
                'kod_jwtn'          => $user->kod_jwtn ?? '',
                'kod_jawatan'       => $user->kod_jawatan,
                'nama_gred'         => $user->nama_gred ?? '',
                'tel_no_p'          => $user->tel_no_p ?? '',
                'tel_no_hp'         => $user->tel_no_hp ?? '', // <-- penting
                'kod_sektor'        => $user->kod_sektor,
                'kod_bah'           => $user->kod_bah,
                'kod_sec'           => $user->kod_sec,
                'kod_unit'          => $user->kod_unit,
                'remember_token'    => $user->remember_token,
                'tarikh_lapormof'   => $user->tarikh_lapormof,
                'wf_ramadhan'       => $user->wf_ramadhan ?? 1,
                'hiraki'            => $user->hiraki,
                'lastlogin_at'      => $user->lastlogin_at ?? now(),
                'kodaktif'          => $user->kodaktif ?? 1,
                'dataaktif'         => $user->dataaktif ?? 1,
                'hadir_kurang_jam'  => $user->hadir_kurang_jam,
                'created_by'        => $user->created_by,
                'created_at'        => $user->created_at ?? now(),
                'updated_by'        => $user->updated_by,
                'updated_at'        => $user->updated_at ?? now(),
                'deleted_by'        => $user->deleted_by,
                'deleted_at'        => $user->deleted_at,
        ];

            // Insert atau update ikut UUID
            DB::table('users')->updateOrInsert(
                ['uuid' => $user->uuid],
                $data
            );
        }

        $this->info('✅ Sync selesai!');
        return Command::SUCCESS;
    }
}
