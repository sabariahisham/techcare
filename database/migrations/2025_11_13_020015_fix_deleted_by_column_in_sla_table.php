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
        if (Schema::hasColumn('sla', 'deleted_by')) {
            Schema::table('sla', function (Blueprint $table) {
                $table->dropForeign(['deleted_by']);
            });

            Schema::table('sla', function (Blueprint $table) {
                $table->dropColumn('deleted_by');
            });
        }

        Schema::table('sla', function (Blueprint $table) {
            // Add the correct deleted_by column as foreign key
            $table->unsignedBigInteger('deleted_by')->nullable()->after('updated_by');
            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sla', function (Blueprint $table) {
            $table->dropForeign(['deleted_by']);
            $table->dropColumn('deleted_by');
        });
    }
};
