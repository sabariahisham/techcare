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
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('ticket_title')->nullable()->after('ticket_nolog')->comment('tajuk tiket');
            $table->text('ticket_desc')->nullable()->after('ticket_title')->comment('deskripsi tiket');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('ticket_title');
            $table->dropColumn('ticket_desc');
        });
    }
};
