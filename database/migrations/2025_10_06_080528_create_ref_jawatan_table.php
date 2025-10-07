<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ref_jawatan', function (Blueprint $table) {
            $table->id(); // Primary Key (auto increment)
            $table->string('kod_jawatan', 20)->unique(); // Kod jawatan - unique
            $table->string('nama_jawatan', 150); // Nama jawatan
            $table->timestamps(); // created_at & updated_at
            
            // Index untuk performance
            $table->index('kod_jawatan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ref_jawatan');
    }
};
