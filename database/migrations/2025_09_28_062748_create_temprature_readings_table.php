<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('temperature_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained()->onDelete('cascade');
            $table->timestamp('recorded_at');
            $table->decimal('temperature', 5, 2);
            $table->enum('reading_type', ['automatic', 'manual', 'imported']);
            $table->string('source_file')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_anomaly')->default(false);
            $table->timestamps();

            $table->index(['machine_id', 'recorded_at']);
            $table->index(['recorded_at', 'is_anomaly']);
            $table->unique(['machine_id', 'recorded_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('temperature_readings');
    }
};
