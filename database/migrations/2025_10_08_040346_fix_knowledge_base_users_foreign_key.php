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
        Schema::table('knowledge_base', function (Blueprint $table) {
            // Drop existing foreign key constraints
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);

            // Add new foreign key constraints pointing to the correct users table
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('knowledge_base', function (Blueprint $table) {
            // Drop the corrected foreign key constraints
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);

            // Add back the original foreign key constraints (pointing to users_old)
            $table->foreign('created_by')->references('id')->on('users_old')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users_old')->nullOnDelete();
        });
    }
};
