<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // rename column doc_type kepada doc_jenis
            $table->renameColumn('doc_type', 'doc_jenis')->comment('KB untuk jenis dokumen knowledge base, TICKET untuk dokumen tiket');

            // tambah column baru doc_id
            $table->string('doc_id')->unique()->after('id')->comment('if doc_jenis = KB, then doc_id is knowledge_base.id and if doc_jenis = TICKET, then doc_id is tickets.id');

            // buang column doc_size
            $table->dropColumn('doc_size');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // revert balik rename
            $table->renameColumn('doc_jenis', 'doc_type');

            // buang column doc_id
            $table->dropColumn('doc_id');

            // tambah balik column doc_size
            $table->integer('doc_size')->nullable();
        });
    }
};
