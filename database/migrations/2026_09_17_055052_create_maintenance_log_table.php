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
       Schema::create('maintenance_log', function (Blueprint $table) {
            $table->id('log_id'); // Primary Key
            $table->unsignedBigInteger('user_id'); // Foreign Key ke user
            $table->unsignedBigInteger('device_id'); // Foreign Key ke pond_device
            $table->text('activity_desc');
            $table->date('date');

            // Definisi Foreign Keys
            $table->foreign('user_id')->references('user_id')->on('user')->onDelete('cascade');
            $table->foreign('device_id')->references('device_id')->on('pond_device')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_log');
    }
};
