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
        Schema::table('sla', function (Blueprint $table) {
            $table->foreignId('cat_id')
                ->after('id')
                ->constrained('ref_categories')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sla', function (Blueprint $table) {
            $table->dropForeign(['cat_id']);
            $table->dropColumn('cat_id');
        });
    }
};
