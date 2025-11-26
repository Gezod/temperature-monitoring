<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('temperature_readings', function (Blueprint $table) {
            // Jika kolom temperature_value ada, rename ke temperature
            if (Schema::hasColumn('temperature_readings', 'temperature_value')) {
                $table->renameColumn('temperature_value', 'temperature');
            }

            // Jika kolom temperature tidak ada, buat kolom baru
            if (!Schema::hasColumn('temperature_readings', 'temperature')) {
                $table->decimal('temperature', 8, 2)->after('recorded_at');
            }
        });
    }

    public function down()
    {
        Schema::table('temperature_readings', function (Blueprint $table) {
            // Kembalikan jika perlu rollback
            if (Schema::hasColumn('temperature_readings', 'temperature')) {
                $table->renameColumn('temperature', 'temperature_value');
            }
        });
    }
};
