<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pond_device', function (Blueprint $table) {
            $table->id('device_id'); // Primary Key
            $table->unsignedBigInteger('user_id'); // Foreign Key ke table user
            $table->string('mac_address')->unique();
            $table->string('pond_name');

            // Definisi Foreign Key
            $table->foreign('user_id')->references('user_id')->on('user')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pond_device');
    }
};