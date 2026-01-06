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
        Schema::rename('subcat_level_pegtugas', 'officers');
    }

    /**
     * Reverse the migrations.
     */
    
    public function down(): void
    {
        Schema::rename('officers', 'subcat_level_pegtugas');
    }
};
