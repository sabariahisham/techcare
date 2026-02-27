<?php

namespace App\Console\Commands;

use App\Models\SyncJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncUsersFromSpp extends Command
{
    protected $signature = 'sync:users-spp';
    protected $description = 'Sync data users dari spp_db.users ke ehelp.users';

    public function handle()
    {
        $startTime = microtime(true);

        $this->info('Mula sync dari spp_db.users ke ehelp.users ...');

        // Create sync job record (status: running)
        $syncJob = SyncJob::create([
            'job_name'   => 'sync_users_spp',
            'status'     => 'running',
            'started_at' => now(),
        ]);

        try {

            $sppUsers = DB::connection('spp')->table('users')->get();
            $total = $sppUsers->count();

            $this->info("Jumlah rekod dijumpai: {$total}");

            $updated = 0;
            $inserted = 0;

            foreach ($sppUsers as $user) {

                $data = [
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
                    'tel_no_hp'         => $user->tel_no_hp ?? '',
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
                    'updated_at'        => now(),
                    'deleted_by'        => $user->deleted_by,
                    'deleted_at'        => $user->deleted_at,
                ];

                $exists = DB::table('users')
                    ->where('id', $user->id)
                    ->exists();

                if ($exists) {
                    DB::table('users')
                        ->where('id', $user->id)
                        ->update($data);
                    $updated++;
                } else {
                    $data['id'] = $user->id;
                    DB::table('users')->insert($data);
                    $inserted++;
                }
            }

            $executionTime = round(microtime(true) - $startTime, 2);

            // Update sync job record (success)
            $syncJob->update([
                'status'           => 'success',
                'finished_at'      => now(),
                'total_records'    => $total,
                'updated_records'  => $updated,
                'inserted_records' => $inserted,
                'execution_time'   => $executionTime,
                'message'          => 'Sync completed successfully',
            ]);

            Log::info("Users sync completed - Updated: {$updated}, Inserted: {$inserted}, Time: {$executionTime}s");

            $this->info("✅ Sync selesai!");
            $this->info("📊 Updated: {$updated}");
            $this->info("📊 Inserted: {$inserted}");
            $this->info("⏱️  Masa diambil: {$executionTime} saat");

            return Command::SUCCESS;

        } catch (\Throwable $e) {

            $executionTime = round(microtime(true) - $startTime, 2);

            // Update sync job record (failed)
            $syncJob->update([
                'status'         => 'failed',
                'finished_at'    => now(),
                'execution_time' => $executionTime,
                'message'        => $e->getMessage(),
            ]);

            Log::error("Users sync failed: {$e->getMessage()} (Time: {$executionTime}s)");

            $this->error("❌ Error: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}