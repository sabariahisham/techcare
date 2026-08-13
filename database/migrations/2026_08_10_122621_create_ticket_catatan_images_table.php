<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ticket_catatan_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            // Nullable: image is uploaded from the Quill editor before the comment/catatan
            // tindakan it belongs to has been saved. Attached once the comment is stored.
            $table->foreignId('comment_id')->nullable()->constrained('comments')->nullOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('file_name');
            $table->string('file_path')->unique();
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('ticket_catatan_images');
    }
};
