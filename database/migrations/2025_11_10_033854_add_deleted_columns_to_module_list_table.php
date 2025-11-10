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
            // Add deleted_at after updated_by
            $table->timestamp('deleted_at')->nullable()->after('updated_by');
            // Add deleted_by after deleted_at
            $table->unsignedBigInteger('deleted_by')->nullable()->after('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('module_list', function (Blueprint $table) {
            $table->dropColumn(['deleted_at', 'deleted_by']);
        });
    }
};
