<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_analysis', function (Blueprint $table) {
            // Tambah kolum hari selepas kolum estimated_weight
            $table->integer('days_in_culture')->nullable()->after('estimated_weight');
        });
    }

    public function down(): void
    {
        Schema::table('ai_analyses', function (Blueprint $table) {
            $table->dropColumn('days_in_culture');
        });
    }
};