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
        Schema::create('sensor_data', function (Blueprint $table) {
            $table->id('data_id'); // Primary Key
            $table->unsignedBigInteger('device_id'); // Foreign Key ke pond_device
            $table->float('temperature');
            $table->float('ph_level');
            $table->float('turbidity');
            $table->timestamp('timestamp')->useCurrent();

            // Definisi Foreign Key
            $table->foreign('device_id')->references('device_id')->on('pond_device')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sensor_data');
    }
};
