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
        Schema::table('module_list', function (Blueprint $table) {
            // Drop the existing columns
            $table->dropColumn(['created_by', 'updated_by']);
        });

        Schema::table('module_list', function (Blueprint $table) {
            // Add created_by after created_at
            $table->unsignedBigInteger('created_by')->nullable()->after('created_at');
            // Add updated_by after updated_at
            $table->unsignedBigInteger('updated_by')->nullable()->after('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('module_list', function (Blueprint $table) {
            // Drop the reordered columns
            $table->dropColumn(['created_by', 'updated_by']);
        });

        Schema::table('module_list', function (Blueprint $table) {
            // Add them back at the end (original position)
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
        });
    }
};
