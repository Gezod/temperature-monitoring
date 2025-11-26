<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('temperature_readings', function (Blueprint $table) {
            // Drop unique constraint
            $table->dropUnique('temperature_readings_machine_id_recorded_at_unique');
        });
    }

    public function down()
    {
        Schema::table('temperature_readings', function (Blueprint $table) {
            // Add back unique constraint
            $table->unique(['machine_id', 'recorded_at'], 'temperature_readings_machine_id_recorded_at_unique');
        });
    }
};
