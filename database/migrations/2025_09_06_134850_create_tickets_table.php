<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
    public function up(): void {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('ticket_nolog')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('cat_id')->constrained('ref_categories')->cascadeOnDelete();
            $table->foreignId('doc_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->foreignId('subcat_id')->constrained('ref_sub_categories')->cascadeOnDelete();
            $table->foreignId('tick_status')->constrained('ref_ticket_statuses')->cascadeOnDelete();
            $table->dateTime('tick_duedate')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void {
        Schema::dropIfExists('tickets');
    }
};