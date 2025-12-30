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
        Schema::table('officers', function (Blueprint $table) {
            $table->unsignedBigInteger('cat_id')->after('id')->nullable();

            // Optional: Add foreign key constraint if cat_id references another table
            // $table->foreign('cat_id')->references('id')->on('categories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('officers', function (Blueprint $table) {
            // Optional: Drop foreign key first if you added it
            // $table->dropForeign(['cat_id']);

            $table->dropColumn('cat_id');
        });
    }
};
