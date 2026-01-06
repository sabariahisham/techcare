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
        Schema::table('tickets_chronologies', function (Blueprint $table) {
            // Add missing SLA columns that are referenced in the code
            $table->integer('sla_days')->nullable()->after('sla_duedate');
            $table->string('sla_severity', 20)->nullable()->after('sla_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets_chronologies', function (Blueprint $table) {
            $table->dropColumn(['sla_days', 'sla_severity']);
        });
    }
};
