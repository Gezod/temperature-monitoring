<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('anomalies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained()->onDelete('cascade');
            $table->foreignId('temperature_reading_id')->constrained()->onDelete('cascade');
            $table->enum('severity', ['low', 'medium', 'high', 'critical']);
            $table->enum('type', ['temperature_high', 'temperature_low', 'rapid_change', 'sensor_error', 'pattern_deviation']);
            $table->text('description');
            $table->text('possible_causes')->nullable();
            $table->text('recommendations')->nullable();
            $table->enum('status', ['new', 'acknowledged', 'investigating', 'resolved', 'false_positive']);
            $table->timestamp('detected_at');
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->string('acknowledged_by')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            $table->index(['machine_id', 'status', 'severity']);
            $table->index(['detected_at', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('anomalies');
    }
};
