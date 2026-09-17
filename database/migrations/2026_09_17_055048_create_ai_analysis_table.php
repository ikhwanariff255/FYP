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
        Schema::create('ai_analysis', function (Blueprint $table) {
            $table->id('analysis_id'); // Primary Key
            $table->unsignedBigInteger('data_id'); // Foreign Key ke sensor_data
            $table->string('risk_status'); // Optimal, Warning, Critical
            $table->float('estimated_weight');
            $table->timestamp('created_at')->useCurrent();

            // Definisi Foreign Key (One-to-One / One-to-Many mengikut ERD)
            $table->foreign('data_id')->references('data_id')->on('sensor_data')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_analysis');
    }
};
