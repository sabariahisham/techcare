<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
    public function up(): void {
        Schema::create('knowledge_base', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('cat_id')->constrained('ref_categories')->cascadeOnDelete();
            $table->foreignId('subcat_id')->constrained('ref_sub_categories')->cascadeOnDelete();
            $table->foreignId('doc_id')->constrained('documents')->cascadeOnDelete();
            $table->string('kb_title');
            $table->text('kb_desc')->nullable();
            $table->text('kb_tag')->nullable();
            $table->enum('kb_status', ['aktif', 'tidak aktif'])->default('aktif');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void {
        Schema::dropIfExists('knowledge_base');
    }
};