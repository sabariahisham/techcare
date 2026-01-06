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
        Schema::table('permissions', function (Blueprint $table) {
            // First, drop the existing user tracking columns
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->dropForeign(['deleted_by']);
            $table->dropColumn(['created_by', 'updated_by', 'deleted_by']);
        });

        Schema::table('permissions', function (Blueprint $table) {
            // Add created_by after created_at
            $table->unsignedBigInteger('created_by')->nullable()->after('created_at');

            // Add updated_by after updated_at
            $table->unsignedBigInteger('updated_by')->nullable()->after('updated_at');

            // Add deleted_at and deleted_by
            $table->timestamp('deleted_at')->nullable()->after('updated_by');
            $table->unsignedBigInteger('deleted_by')->nullable()->after('deleted_at');

            // Add foreign key constraints
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            // Drop foreign key constraints
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->dropForeign(['deleted_by']);

            // Drop the columns
            $table->dropColumn(['created_by', 'updated_by', 'deleted_at', 'deleted_by']);
        });

        Schema::table('permissions', function (Blueprint $table) {
            // Re-add the columns in the original position (after sort_order)
            $table->unsignedBigInteger('created_by')->nullable()->after('sort_order');
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            $table->unsignedBigInteger('deleted_by')->nullable()->after('updated_by');

            // Re-add foreign key constraints
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();
        });
    }
};
