<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\SyncJob;

class SyncSPPReferences extends Command
{
    protected $signature = 'sync:spp-ref {table?} {--all}';
    protected $description = 'Sync reference tables from SPP to ehelp database';

    protected array $tables = [
        'bahagian' => ['source' => 'bahagian', 'target' => 'ref_bahagian', 'key' => 'kod_bah'],
        'sektor'   => ['source' => 'sektor', 'target' => 'ref_sektor', 'key' => 'kod_sektor'],
        'seksyen'  => ['source' => 'seksyen', 'target' => 'ref_seksyen', 'key' => 'kod_sec'],
        'unit'     => ['source' => 'unit', 'target' => 'ref_unit', 'key' => 'kod_unit'],
        'jawatan'  => ['source' => 'jawatan', 'target' => 'ref_jawatan', 'key' => 'kod_jawatan'],
        'khidmat'  => ['source' => 'khidmat', 'target' => 'ref_khidmat', 'key' => 'kod_kump_khidmat'],
        'gred'     => ['source' => 'gred', 'target' => 'ref_gred', 'key' => 'nama_gred'],
        'skim'     => ['source' => 'skim', 'target' => 'ref_skim', 'key' => 'kod_skim'],
    ];

    public function handle()
    {
        $startTime = microtime(true);

        $this->info('Mula sync reference tables dari SPP...');

        $syncJob = SyncJob::create([
            'job_name'   => 'sync_spp_references',
            'status'     => 'running',
            'started_at' => now(),
        ]);

        try {

            $totalProcessed = 0;

            if ($this->option('all')) {

                foreach ($this->tables as $name => $config) {

                    $count = $this->syncTable($name, $config);
                    $totalProcessed += $count;

                    // Pause 5 saat antara table
                    sleep(5);
                }

            } else {

                $table = $this->argument('table');

                if (!isset($this->tables[$table])) {
                    $this->warn('Table tidak sah.');
                    return Command::FAILURE;
                }

                $totalProcessed = $this->syncTable($table, $this->tables[$table]);
            }

            $executionTime = round(microtime(true) - $startTime, 2);

            $syncJob->update([
                'status'           => 'success',
                'total_records'    => $totalProcessed,
                'execution_time'   => $executionTime,
                'message'          => 'Reference sync completed successfully',
                'finished_at'      => now(),
            ]);

            $this->info("✅ Sync selesai.");
            $this->info("📊 Total records processed: {$totalProcessed}");
            $this->info("⏱️ Masa diambil: {$executionTime} saat");

            return Command::SUCCESS;

        } catch (\Throwable $e) {

            $executionTime = round(microtime(true) - $startTime, 2);

            $syncJob->update([
                'status'         => 'failed',
                'execution_time' => $executionTime,
                'message'        => $e->getMessage(),
                'finished_at'    => now(),
            ]);

            $this->error('❌ Error: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }

    protected function syncTable(string $name, array $config): int
    {
        $this->info("🔄 Syncing {$name}...");

        $records = DB::connection('spp')
            ->table($config['source'])
            ->get();

        $count = 0;

        foreach ($records as $record) {

            $data = (array) $record;
            $key = $config['key'];

            DB::table($config['target'])->updateOrInsert(
                [$key => $data[$key]],
                $data + [
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            $count++;
        }

        $this->info("✔ {$name} selesai. Total: {$count}");

        return $count;
    }
}