<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
    public function up(): void {
        Schema::create('ref_sub_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cat_id')->constrained('ref_categories')->cascadeOnDelete();
            $table->string('subcat_name');
            $table->text('subcat_desc')->nullable();

            // Audit trail
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void {
        Schema::dropIfExists('ref_sub_categories');
    }
};