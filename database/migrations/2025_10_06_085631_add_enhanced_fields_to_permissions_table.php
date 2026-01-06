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
            // Add enhanced fields for permissions
            if (!Schema::hasColumn('permissions', 'description')) {
                $table->text('description')->nullable()->after('module');
            }
            if (!Schema::hasColumn('permissions', 'category')) {
                $table->string('category', 100)->nullable()->after('description');
            }
            if (!Schema::hasColumn('permissions', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('category');
            }
            if (!Schema::hasColumn('permissions', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('is_active');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn([
                'description',
                'category',
                'is_active',
                'sort_order'
            ]);
        });
    }
};
