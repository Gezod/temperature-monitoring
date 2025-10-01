<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('system_alerts', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['anomaly', 'maintenance', 'system', 'performance']);
            $table->enum('level', ['info', 'warning', 'error', 'critical']);
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable();
            $table->boolean('is_read')->default(false);
            $table->boolean('is_dismissed')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'level', 'is_read']);
            $table->index(['created_at', 'is_dismissed']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('system_alerts');
    }
};
