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
        Schema::create('ref_khidmat', function (Blueprint $table) {
            $table->id(); // Primary Key (auto increment)
            $table->string('kod_kump_khidmat', 20)->unique(); // Kod kumpulan khidmat - unique
            $table->string('nama_kumpulan_khidmat', 200); // Nama kumpulan khidmat
            $table->timestamps(); // created_at & updated_at
            
            // Index untuk performance
            $table->index('kod_kump_khidmat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ref_khidmat');
    }
};
