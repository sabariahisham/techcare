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
        Schema::table('users', function (Blueprint $table) {
            // Add new fields if they don't exist
            if (!Schema::hasColumn('users', 'department')) {
                $table->string('department')->nullable()->after('agency');
            }
            if (!Schema::hasColumn('users', 'section')) {
                $table->string('section')->nullable()->after('department');
            }
            if (!Schema::hasColumn('users', 'unit')) {
                $table->string('unit')->nullable()->after('section');
            }
            if (!Schema::hasColumn('users', 'position_title')) {
                $table->string('position_title')->nullable()->after('unit');
            }

            // Add profile photo columns if they don't exist (Jetstream might have added these)
            if (!Schema::hasColumn('users', 'profile_photo_path')) {
                $table->string('profile_photo_path', 2048)->nullable();
            }
            if (!Schema::hasColumn('users', 'current_team_id')) {
                $table->foreignId('current_team_id')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'department',
                'section',
                'unit',
                'position_title'
            ]);
        });
    }
};
