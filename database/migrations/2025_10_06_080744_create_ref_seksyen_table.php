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
        Schema::create('ref_seksyen', function (Blueprint $table) {
            $table->id(); // Primary Key (auto increment)
            $table->string('kod_sec', 20)->unique(); // Kod seksyen - unique
            $table->string('nama_sec', 150); // Nama seksyen
            $table->string('kod_sektor', 10); // Foreign key ke sektor.kod_sektor
            $table->string('kod_bah', 10); // Foreign key ke bahagian.kod_bah
            $table->timestamps(); // created_at & updated_at

            // Index untuk performance
            $table->index('kod_sec');
            $table->index('kod_sektor');
            $table->index('kod_bah');

            // Foreign key constraints
            $table->foreign('kod_sektor')->references('kod_sektor')->on('ref_sektor')->onDelete('cascade');
            $table->foreign('kod_bah')->references('kod_bah')->on('ref_bahagian')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ref_seksyen');
    }
};
