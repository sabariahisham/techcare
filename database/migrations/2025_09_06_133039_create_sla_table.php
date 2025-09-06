<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
    public function up(): void {
        Schema::create('sla', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subcat_id')->constrained('ref_sub_categories')->cascadeOnDelete();
            $table->integer('sla_level'); // 1, 2
            $table->enum('sla_priority', ['High', 'Medium', 'Low']);
            $table->integer('sla_days');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void {
        Schema::dropIfExists('sla');
    }
};