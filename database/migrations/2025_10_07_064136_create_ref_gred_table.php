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
        Schema::create('ref_gred', function (Blueprint $table) {
            $table->id(); // Primary Key (auto increment)
            $table->string('kod_kump_khidmat', 20); // Foreign key ke ref_khidmat.kod_kump_khidmat
            $table->string('nama_gred', 100); // Nama gred
            $table->timestamps(); // created_at & updated_at
            
            // Index untuk performance
            $table->index('kod_kump_khidmat');
            $table->index('nama_gred');
            
            // Foreign key constraint
            $table->foreign('kod_kump_khidmat')->references('kod_kump_khidmat')->on('ref_khidmat')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ref_gred');
    }
};
