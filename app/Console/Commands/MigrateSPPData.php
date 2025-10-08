<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateSPPData extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'migrate:spp {table?} ';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Migrate table tertentu from SPP database to ehelp database';

  /**
   * Execute the console command.
   */
  public function handle()
  {
    $table = $this->argument('table');

    if ($table === 'bahagian') {
      $this->migrateBahagian();
    } elseif ($table === 'sektor') {
      $this->migrateSektor();
    } elseif ($table === 'seksyen') {
      $this->migrateSeksyen();
    } elseif ($table === 'unit') {
      $this->migrateUnit();
    } elseif ($table === 'jawatan') {
      $this->migrateJawatan();
    } elseif ($table === 'khidmat') {
      $this->migrateKhidmat();
    } elseif ($table === 'gred') {
      $this->migrateGred();
    } else {
      $this->info('Sila nyatakan table: bahagian / sektor / seksyen / unit / jawatan / khidmat / gred');
    }
  }

  protected function migrateBahagian()
  {
    $sppBahagian = DB::connection('spp')->table('bahagian')->get();

    foreach ($sppBahagian as $bahagian) {
      // Skip jika nama_bah adalah null atau empty
      if (empty($bahagian->nama_bah)) {
        $this->warn("Skipping bahagian with kod_bah: {$bahagian->kod_bah} - nama_bah is null/empty");
        continue;
      }

      // Use 'mysql' connection (default) instead of 'ehelp_db'
      DB::connection('mysql')->table('ref_bahagian')->updateOrInsert(
        ['kod_bah' => $bahagian->kod_bah],
        [
          'nama_bah' => $bahagian->nama_bah,
          'singkat_bah' => $bahagian->singkat_bah,
          'kod_sektor' => $bahagian->kod_sektor,
          'status' => $bahagian->status == 1 ? 'active' : 'inactive',
          'created_at' => now(),
          'updated_at' => now(),
        ]
      );
    }

    $this->info('Migrasi bahagian selesai.');
  }

  protected function migrateSektor()
  {
    $sppSektor = DB::connection('spp')->table('sektor')->get();

    foreach ($sppSektor as $sektor) {
      // Skip jika nama_sektor adalah null atau empty
      if (empty($sektor->nama_sektor)) {
        $this->warn("Skipping sektor with kod_sektor: {$sektor->kod_sektor} - nama_sektor is null/empty");
        continue;
      }

      // Use 'mysql' connection (default) instead of 'ehelp_db'
      DB::connection('mysql')->table('ref_sektor')->updateOrInsert(
        ['kod_sektor' => $sektor->kod_sektor],
        [
          'nama_sektor' => $sektor->nama_sektor,
          'created_at' => now(),
          'updated_at' => now(),
        ]
      );
    }

    $this->info('Migrasi sektor selesai.');
  }

  protected function migrateSeksyen()
  {
    $sppSeksyen = DB::connection('spp')->table('seksyen')->get();

    foreach ($sppSeksyen as $seksyen) {
      // Skip jika nama_sec adalah null atau empty
      if (empty($seksyen->nama_sec)) {
        $this->warn("Skipping seksyen with kod_sec: {$seksyen->kod_sec} - nama_sec is null/empty");
        continue;
      }

      // Check jika kod_sektor wujud dalam ref_sektor (using default connection)
      $sektorExists = DB::table('ref_sektor')->where('kod_sektor', $seksyen->kod_sektor)->exists();
      if (!$sektorExists) {
        $this->warn("Skipping seksyen {$seksyen->kod_sec} - kod_sektor {$seksyen->kod_sektor} not found in ref_sektor");
        continue;
      }

      // Check jika kod_bah wujud dalam ref_bahagian
      $bahagianExists = DB::table('ref_bahagian')->where('kod_bah', $seksyen->kod_bah)->exists();
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
    }

    $this->info('Migrasi seksyen selesai.');
  }

  protected function migrateUnit()
  {
    $sppUnit = DB::connection('spp')->table('unit')->get();

    foreach ($sppUnit as $unit) {
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
    }

    $this->info('Migrasi unit selesai.');
  }

  protected function migrateJawatan()
  {
    $sppJawatan = DB::connection('spp')->table('jawatan')->get();

    foreach ($sppJawatan as $jawatan) {
      // Skip jika nama_jawatan adalah null atau empty
      if (empty($jawatan->nama_jawatan)) {
        $this->warn("Skipping jawatan with kod_jawatan: {$jawatan->kod_jawatan} - nama_jawatan is null/empty");
        continue;
      }

      DB::table('ref_jawatan')->updateOrInsert(
        ['kod_jawatan' => $jawatan->kod_jawatan],
        [
          'nama_jawatan' => $jawatan->nama_jawatan,
          'created_at' => now(),
          'updated_at' => now(),
        ]
      );
    }

    $this->info('Migrasi jawatan selesai.');
  }

  protected function migrateKhidmat()
  {
    $sppKhidmat = DB::connection('spp')->table('khidmat')->get();

    foreach ($sppKhidmat as $khidmat) {
      // Skip jika nama_kumpulan_khidmat adalah null atau empty
      if (empty($khidmat->nama_kumpulan_khidmat)) {
        $this->warn("Skipping khidmat with kod_kump_khidmat: {$khidmat->kod_kump_khidmat} - nama_kumpulan_khidmat is null/empty");
        continue;
      }

      DB::table('ref_khidmat')->updateOrInsert(
        ['kod_kump_khidmat' => $khidmat->kod_kump_khidmat],
        [
          'nama_kumpulan_khidmat' => $khidmat->nama_kumpulan_khidmat,
          'created_at' => now(),
          'updated_at' => now(),
        ]
      );
    }

    $this->info('Migrasi khidmat selesai.');
  }

  protected function migrateGred()
  {
    $sppGred = DB::connection('spp')->table('gred')->get();

    foreach ($sppGred as $gred) {
      // Skip jika nama_gred adalah null atau empty
      if (empty($gred->nama_gred)) {
        $this->warn("Skipping gred with nama_gred: {$gred->nama_gred} - nama_gred is null/empty");
        continue;
      }

      // Check jika kod_kump_khidmat wujud dalam ref_khidmat
      $khidmatExists = DB::table('ref_khidmat')->where('kod_kump_khidmat', $gred->kod_kump_khidmat)->exists();
      if (!$khidmatExists) {
        $this->warn("Skipping gred {$gred->nama_gred} - kod_kump_khidmat {$gred->kod_kump_khidmat} not found in ref_khidmat");
        continue;
      }

      DB::table('ref_gred')->updateOrInsert(
        ['nama_gred' => $gred->nama_gred, 'kod_kump_khidmat' => $gred->kod_kump_khidmat],
        [
          'nama_gred' => $gred->nama_gred,
          'kod_kump_khidmat' => $gred->kod_kump_khidmat,
          'created_at' => now(),
          'updated_at' => now(),
        ]
      );
    }

    $this->info('Migrasi gred selesai.');
  }
}
