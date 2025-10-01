<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('maintenance_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['preventive', 'predictive', 'corrective', 'emergency']);
            $table->enum('priority', ['low', 'medium', 'high', 'critical']);
            $table->string('title');
            $table->text('description');
            $table->text('reason');
            $table->date('recommended_date');
            $table->integer('estimated_duration_hours')->nullable();
            $table->decimal('estimated_cost', 10, 2)->nullable();
            $table->json('required_parts')->nullable();
            $table->enum('status', ['pending', 'scheduled', 'in_progress', 'completed', 'cancelled']);
            $table->date('scheduled_date')->nullable();
            $table->date('completed_date')->nullable();
            $table->text('completion_notes')->nullable();
            $table->json('trend_data')->nullable();
            $table->timestamps();

            $table->index(['machine_id', 'status', 'priority']);
            $table->index(['recommended_date', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('maintenance_recommendations');
    }
};
