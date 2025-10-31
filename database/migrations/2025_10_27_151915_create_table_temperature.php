<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('temperature', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained()->onDelete('cascade')->nullabel();
            $table->integer('temperature_value')->nullabel();
            $table->timestamp('timestamp')->nullabel();
            $table->timestamp('created_at')->useCurrent()->nullabel();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent()->nullabel();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temperature');
    }
};
