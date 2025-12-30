<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migration.
     */
    public function up(): void
    {
        Schema::create('ref_skim', function (Blueprint $table) {
            $table->id(); // id INT(10) AUTO_INCREMENT PRIMARY KEY
            $table->string('kod_skim', 5);
            $table->string('nama_skim', 200);
            $table->timestamps(); // created_at & updated_at
        });
    }

    /**
     * Undur balik migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('ref_skim');
    }
};
