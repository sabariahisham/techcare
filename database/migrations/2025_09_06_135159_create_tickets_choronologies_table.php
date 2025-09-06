<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
    public function up(): void {
        Schema::create('tickets_chronologies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->foreignId('frm_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('level_pegtugas_id')->constrained('subcat_level_pegtugas')->cascadeOnDelete();
            $table->foreignId('tick_status')->constrained('ref_ticket_statuses')->cascadeOnDelete();
            $table->foreignId('escalate_to')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('sla_duedate')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void {
        Schema::dropIfExists('tickets_chronologies');
    }
};