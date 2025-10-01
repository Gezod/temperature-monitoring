<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('machines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('type');
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();
            $table->date('installation_date')->nullable();
            $table->json('specifications')->nullable();
            $table->decimal('temp_min_normal', 5, 2)->default(-20.00);
            $table->decimal('temp_max_normal', 5, 2)->default(5.00);
            $table->decimal('temp_critical_min', 5, 2)->default(-25.00);
            $table->decimal('temp_critical_max', 5, 2)->default(10.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['branch_id', 'is_active']);
            $table->index(['type', 'is_active']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('machines');
    }
};
