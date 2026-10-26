<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pond_device', function (Blueprint $table) {
            $table->date('stocking_date')->nullable()->after('pond_name');
            $table->integer('stocking_density')->nullable()->after('stocking_date');
            $table->decimal('initial_weight', 8, 2)->nullable()->comment('Berat dalam gram')->after('stocking_density');
            $table->decimal('target_weight', 8, 2)->nullable()->comment('Berat dalam gram')->after('initial_weight');
            $table->date('estimated_harvest_date')->nullable()->after('target_weight');
            $table->string('status')->default('Active')->after('estimated_harvest_date'); 
        });
    }

    public function down(): void
    {
        Schema::table('pond_device', function (Blueprint $table) {
            $table->dropColumn([
                'stocking_date',
                'stocking_density',
                'initial_weight',
                'target_weight',
                'estimated_harvest_date',
                'status'
            ]);
        });
    }
};