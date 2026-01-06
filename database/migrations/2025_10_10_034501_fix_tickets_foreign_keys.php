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
      // Drop the existing foreign key constraints that point to users_backup
      $table->dropForeign(['user_id']);
      $table->dropForeign(['created_by']);
      $table->dropForeign(['updated_by']);

      // Add new foreign key constraints that point to users table
      $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
      $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
      $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('tickets', function (Blueprint $table) {
      // Drop the current foreign key constraints
      $table->dropForeign(['user_id']);
      $table->dropForeign(['created_by']);
      $table->dropForeign(['updated_by']);

      // Restore the original foreign key constraints (pointing to users_backup)
      // Note: Only restore if users_backup table exists
      if (Schema::hasTable('users_backup')) {
        $table->foreign('user_id')->references('id')->on('users_backup')->onDelete('cascade');
        $table->foreign('created_by')->references('id')->on('users_backup')->onDelete('set null');
        $table->foreign('updated_by')->references('id')->on('users_backup')->onDelete('set null');
      }
    });
  }
};
