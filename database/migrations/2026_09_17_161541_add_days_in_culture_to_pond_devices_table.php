<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
Schema::table('pond_device', function (Blueprint $table) { // Tukar ikut nama sebenar di pangkalan data            // Menambah kolum integer untuk hari ternakan, diletakkan selepas kolam/nama
            $table->integer('days_in_culture')->default(1)->after('pond_name');
        });
    }

    public function down(): void
    {
        Schema::table('pond_devices', function (Blueprint $table) {
            // Kod jika kau mahu 'rollback' (padam semula kolum ini)
            $table->dropColumn('days_in_culture');
        });
    }
};