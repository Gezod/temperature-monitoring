<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('monthly_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained()->onDelete('cascade');
            $table->year('year');
            $table->tinyInteger('month');
            $table->decimal('temp_avg', 5, 2)->nullable();
            $table->decimal('temp_min', 5, 2)->nullable();
            $table->decimal('temp_max', 5, 2)->nullable();
            $table->integer('total_readings')->default(0);
            $table->integer('anomaly_count')->default(0);
            $table->decimal('uptime_percentage', 5, 2)->default(100.00);
            $table->json('daily_averages')->nullable();
            $table->timestamps();

            $table->unique(['machine_id', 'year', 'month']);
            $table->index(['year', 'month']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('monthly_summaries');
    }
};
