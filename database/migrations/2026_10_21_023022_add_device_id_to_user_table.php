<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user', function (Blueprint $table) {
            // Menggunakan Schema::table() supaya data sedia ada tidak terpadam
            $table->unsignedBigInteger('device_id')->nullable()->after('role');
            $table->timestamps(); // Menambah created_at & updated_at jika belum ada
        });
    }

    public function down(): void
    {
        Schema::table('user', function (Blueprint $table) {
            $table->dropColumn(['device_id', 'created_at', 'updated_at']);
        });
    }
};