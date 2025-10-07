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
        Schema::create('ref_bahagian', function (Blueprint $table) {
            $table->id(); // Primary Key (auto increment)
            $table->string('kod_bah', 10)->unique(); // Kod bahagian - unique
            $table->string('nama_bah', 100); // Nama bahagian
            $table->string('singkat_bah', 20)->nullable(); // Singkatan bahagian
            $table->string('kod_sektor', 10); // Foreign key ke sektor.kod_sektor
            $table->enum('status', ['active', 'inactive'])->default('active'); // Status
            $table->timestamps(); // created_at & updated_at
            
            // Index untuk performance
            $table->index('kod_bah');
            $table->index('kod_sektor');
            $table->index('status');
            
            // Foreign key constraint
            $table->foreign('kod_sektor')->references('kod_sektor')->on('sektor')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bahagian');
    }
};
