<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Drop FK dulu
        Schema::table('tickets_chronologies', function (Blueprint $table) {
            $table->dropForeign(['level_pegtugas_id']);
        });

        // 2. Alter column jadi integer biasa
        Schema::table('tickets_chronologies', function (Blueprint $table) {
            $table->integer('level_pegtugas_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Rollback ke asal (contoh asalnya unsignedBigInteger + FK)
        Schema::table('tickets_chronologies', function (Blueprint $table) {
            $table->unsignedBigInteger('level_pegtugas_id')->change();
        });

        Schema::table('tickets_chronologies', function (Blueprint $table) {
            $table->foreign('level_pegtugas_id')
                  ->references('id')
                  ->on('levels')   // adjust ikut table asal
                  ->onDelete('cascade');
        });
    }
};
