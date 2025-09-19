<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('knowledge_base', function (Blueprint $table) {
            // 1. Drop foreign key constraint dulu
            $table->dropForeign(['doc_id']);

            // 2. Drop column doc_id
            $table->dropColumn('doc_id');
        });
    }

    public function down(): void
    {
        Schema::table('knowledge_base', function (Blueprint $table) {
            // Tambah balik column doc_id
            $table->unsignedBigInteger('doc_id')->nullable();

            // Tambah balik foreign key ke table documents
            $table->foreign('doc_id')
                ->references('id')
                ->on('documents')
                ->onDelete('cascade');
        });
    }
};
