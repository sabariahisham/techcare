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
        Schema::create('sync_jobs_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sync_job_id')
                  ->constrained('sync_jobs')
                  ->onDelete('cascade');
            $table->string('table_name');
            $table->integer('total_records')->nullable()->default(0);
            $table->integer('inserted_records')->nullable()->default(0);
            $table->integer('updated_records')->nullable()->default(0);
            $table->enum('status', ['success', 'failed'])->default('success');
            $table->decimal('execution_time', 8, 2)->nullable()->default(0.00); 
            $table->text('message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();   
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_jobs_details');
    }
};
