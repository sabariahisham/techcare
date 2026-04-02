<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\SyncJob;
use App\Models\SyncJobDetail;

class SyncRefsData extends Command
{
    protected $signature = 'app:sync-refs-data {table? : Nama table yang mahu disync} {--all : Sync semua table}';
    protected $description = 'Migrate table tertentu from SPP database to ehelp database';

    protected $syncJob;

    public function handle()
    {
        $startTime = microtime(true);

        $this->info('🚀 Mula sync SPP reference...');

        // CREATE JOB
        $this->syncJob = SyncJob::create([
            'job_name' => 'sync_refs_spp',
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {

            if ($this->option('all')) {

                $this->migrateSektor();
                sleep(5);

                $this->migrateBahagian();
                sleep(5);

                $this->migrateSeksyen();
                sleep(5);

                $this->migrateUnit();
                sleep(5);

                $this->migrateJawatan();
                sleep(5);

                $this->migrateKhidmat();
                sleep(5);

                $this->migrateGred();
                sleep(5);

                $this->migrateSkim();

            } else {

                $table = $this->argument('table');

                match ($table) {
                    'bahagian' => $this->migrateBahagian(),
                    'sektor' => $this->migrateSektor(),
                    'seksyen' => $this->migrateSeksyen(),
                    'unit' => $this->migrateUnit(),
                    'jawatan' => $this->migrateJawatan(),
                    'khidmat' => $this->migrateKhidmat(),
                    'gred' => $this->migrateGred(),
                    'skim' => $this->migrateSkim(),
                    default => $this->warn('Table tidak sah'),
                };
            }

            $executionTime = round(microtime(true) - $startTime, 2);

            $this->syncJob->update([
                'status' => 'success',
                'execution_time' => $executionTime,
                'finished_at' => now(),
            ]);

            $this->info('✅ Sync selesai');

        } catch (\Throwable $e) {

            $this->syncJob->update([
                'status' => 'failed',
                'message' => $e->getMessage(),
                'finished_at' => now(),
            ]);

            $this->error($e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | TEMPLATE FUNCTION (REUSE)
    |--------------------------------------------------------------------------
    */

    protected function logDetail($table, $inserted, $updated, $startTime, $status = 'success', $message = null)
    {
        SyncJobDetail::create([
            'sync_job_id' => $this->syncJob->id,
            'table_name' => $table,
            'status' => $status,
            'total_records' => $inserted + $updated,
            'inserted_records' => $inserted,
            'updated_records' => $updated,
            'started_at' => $startTime,
            'finished_at' => now(),
            'message' => $message,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | MIGRATE FUNCTIONS
    |--------------------------------------------------------------------------
    */

    protected function migrateBahagian()
    {
        $start = now();
        $inserted = 0;
        $updated = 0;

        $data = DB::connection('spp')->table('bahagian')->get();

        foreach ($data as $row) {

            if (empty($row->nama_bah)) continue;

            $exists = DB::table('ref_bahagian')->where('kod_bah', $row->kod_bah)->exists();

            DB::table('ref_bahagian')->updateOrInsert(
                ['kod_bah' => $row->kod_bah],
                [
                    'nama_bah' => $row->nama_bah,
                    'singkat_bah' => $row->singkat_bah,
                    'kod_sektor' => $row->kod_sektor,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $exists ? $updated++ : $inserted++;
        }

        $this->logDetail('bahagian', $inserted, $updated, $start);
        $this->info("✔ bahagian: insert {$inserted}, update {$updated}");
    }

    protected function migrateSektor()
    {
        $start = now();
        $inserted = 0;
        $updated = 0;

        $data = DB::connection('spp')->table('sektor')->get();

        foreach ($data as $row) {

            if (empty($row->nama_sektor)) continue;

            $exists = DB::table('ref_sektor')->where('kod_sektor', $row->kod_sektor)->exists();

            DB::table('ref_sektor')->updateOrInsert(
                ['kod_sektor' => $row->kod_sektor],
                [
                    'nama_sektor' => $row->nama_sektor,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $exists ? $updated++ : $inserted++;
        }

        $this->logDetail('sektor', $inserted, $updated, $start);
        $this->info("✔ sektor: insert {$inserted}, update {$updated}");
    }

    protected function migrateSeksyen()
    {
        $start = now();
        $inserted = 0;
        $updated = 0;

        $data = DB::connection('spp')->table('seksyen')->get();

        foreach ($data as $seksyen) {
            // Skip jika nama_sec adalah null atau empty
            if (empty($seksyen->nama_sec)) {
                $this->warn("Skipping seksyen with kod_sec: {$seksyen->kod_sec} - nama_sec is null/empty");
                continue;
            }

            // Check dependencies
            $sektorExists = DB::table('ref_sektor')->where('kod_sektor', $seksyen->kod_sektor)->exists();
            if (!$sektorExists) {
                $this->warn("Skipping seksyen {$seksyen->kod_sec} - kod_sektor {$seksyen->kod_sektor} not found in ref_sektor");
                continue;
            }
            $bahagianExists = DB::table('ref_bahagian')->where('kod_bah', $seksyen->kod_bah)->exists();
            if (!$bahagianExists) {
                $this->warn("Skipping seksyen {$seksyen->kod_sec} - kod_bah {$seksyen->kod_bah} not found in ref_bahagian");
                continue;
            }

            // Check if kod_bah exists in ref_bahagian before inserting/updating seksyen                        
            if (!$bahagianExists) {
                $this->warn("Skipping seksyen {$seksyen->kod_sec} - kod_bah {$seksyen->kod_bah} not found in ref_bahagian");
                continue;
            }       
            
            DB::table('ref_seksyen')->updateOrInsert(
                ['kod_sec' => $seksyen->kod_sec],
                [
                'nama_sec' => $seksyen->nama_sec,
                'kod_bah' => $seksyen->kod_bah,
                'kod_sektor' => $seksyen->kod_sektor,
                'created_at' => now(),
                'updated_at' => now(),
                ]
            );

            $exists = DB::table('ref_seksyen')->where('kod_sec', $seksyen->kod_sec)->exists();
            $exists ? $updated++ : $inserted++;
        }

        $this->logDetail('seksyen', $inserted, $updated, $start);
        $this->info("✔ seksyen: insert {$inserted}, update {$updated}");       
    }

    protected function migrateUnit()
    {
        $start = now();
        $inserted = 0;
        $updated = 0;

        $data = DB::connection('spp')->table('unit')->get();

        foreach ($data as $unit) {
            // Skip jika nama_unit adalah null atau empty
            if (empty($unit->nama_unit)) {
                $this->warn("Skipping unit with kod_unit: {$unit->kod_unit} - nama_unit is null/empty");
                continue;
            }

            // Check dependencies
            $sektorExists = DB::table('ref_sektor')->where('kod_sektor', $unit->kod_sektor)->exists();
            if (!$sektorExists) {
                $this->warn("Skipping unit {$unit->kod_unit} - kod_sektor {$unit->kod_sektor} not found in ref_sektor");
                continue;
            }
            $bahagianExists = DB::table('ref_bahagian')->where('kod_bah', $unit->kod_bah)->exists();
            if (!$bahagianExists) {
                $this->warn("Skipping unit {$unit->kod_unit} - kod_bah {$unit->kod_bah} not found in ref_bahagian");
                continue;
            }
            $seksyenExists = DB::table('ref_seksyen')->where('kod_sec', $unit->kod_sec)->exists();
            if (!$seksyenExists) {
                $this->warn("Skipping unit {$unit->kod_unit} - kod_sec {$unit->kod_sec} not found in ref_seksyen");
                continue;
            }

            DB::table('ref_unit')->updateOrInsert(
                ['kod_unit' => $unit->kod_unit],
                [
                    'nama_unit' => $unit->nama_unit,
                    'kod_sektor' => $unit->kod_sektor,
                    'kod_bah' => $unit->kod_bah,
                    'kod_sec' => $unit->kod_sec,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $exists = DB::table('ref_unit')->where('kod_unit', $unit->kod_unit)->exists();
            $exists ? $updated++ : $inserted++;
        }

        $this->logDetail('unit', $inserted, $updated, $start);
        $this->info("✔ unit: insert {$inserted}, update {$updated}");      
    }
    
    protected function migrateJawatan()
    {
        $start = now();
        $inserted = 0;
        $updated = 0;

        $data = DB::connection('spp')->table('jawatan')->get();

        foreach ($data as $row) {

            if (empty($row->nama_jawatan)) continue;

            $exists = DB::table('ref_jawatan')->where('kod_jawatan', $row->kod_jawatan)->exists();

            DB::table('ref_jawatan')->updateOrInsert(
                ['kod_jawatan' => $row->kod_jawatan],
                [
                    'nama_jawatan' => $row->nama_jawatan,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $exists ? $updated++ : $inserted++;
        }

        $this->logDetail('jawatan', $inserted, $updated, $start);
        $this->info("✔ jawatan: insert {$inserted}, update {$updated}");   
    }

    protected function migrateKhidmat()
    {
        $start = now();
        $inserted = 0;
        $updated = 0;

        $data = DB::connection('spp')->table('khidmat')->get();

        foreach ($data as $row) {

            if (empty($row->nama_khidmat)) continue;

            $exists = DB::table('ref_khidmat')->where('kod_khidmat', $row->kod_khidmat)->exists();

            DB::table('ref_khidmat')->updateOrInsert(
                ['kod_khidmat' => $row->kod_khidmat],
                [
                    'nama_khidmat' => $row->nama_khidmat,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $exists ? $updated++ : $inserted++;
        }

        $this->logDetail('khidmat', $inserted, $updated, $start);
        $this->info("✔ khidmat: insert {$inserted}, update {$updated}");
    }

    protected function migrateGred()
    {
        $start = now();
        $inserted = 0;
        $updated = 0;

        $data = DB::connection('spp')->table('gred')->get();

        foreach ($data as $row) {

            if (empty($row->nama_gred)) continue;

            $exists = DB::table('ref_gred')->where('kod_gred', $row->kod_gred)->exists();

            DB::table('ref_gred')->updateOrInsert(
                ['kod_gred' => $row->kod_gred],
                [
                    'nama_gred' => $row->nama_gred,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $exists ? $updated++ : $inserted++;
        }

        $this->logDetail('gred', $inserted, $updated, $start);
        $this->info("✔ gred: insert {$inserted}, update {$updated}");
    }

    protected function migrateSkim()
    {
        $start = now();
        $inserted = 0;
        $updated = 0;

        $data = DB::connection('spp')->table('skim')->get();

        foreach ($data as $row) {

            if (empty($row->nama_skim)) continue;

            $exists = DB::table('ref_skim')->where('kod_skim', $row->kod_skim)->exists();

            DB::table('ref_skim')->updateOrInsert(
                ['kod_skim' => $row->kod_skim],
                [
                    'nama_skim' => $row->nama_skim,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $exists ? $updated++ : $inserted++;
        }

        $this->logDetail('skim', $inserted, $updated, $start);
        $this->info("✔ skim: insert {$inserted}, update {$updated}");
    }   


}