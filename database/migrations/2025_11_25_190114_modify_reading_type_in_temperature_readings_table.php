<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyReadingTypeInTemperatureReadingsTable extends Migration
{
    public function up()
    {
        Schema::table('temperature_readings', function (Blueprint $table) {
            $table->string('reading_type', 50)->change(); // atau 100, sesuaikan kebutuhan
            $table->string('source_file', 100)->change();
        });
    }

    public function down()
    {
        Schema::table('temperature_readings', function (Blueprint $table) {
            $table->string('reading_type', 10)->change(); // kembali ke nilai sebelumnya
            $table->string('source_file', 50)->change();
        });
    }
}
