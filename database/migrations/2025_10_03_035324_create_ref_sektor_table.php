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
        Schema::create('ref_sektor', function (Blueprint $table) {
            $table->id(); // Primary Key (auto increment)
            $table->string('kod_sektor', 10)->unique(); // Kod sektor - unique
            $table->string('nama_sektor', 100); // Nama sektor
            $table->timestamps(); // created_at & updated_at
            
            // Index untuk performance
            $table->index('kod_sektor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sektor');
    }
};
