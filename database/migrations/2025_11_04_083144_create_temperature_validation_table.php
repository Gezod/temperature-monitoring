<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('temperature_validations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained()->onDelete('cascade');
            $table->string('upload_session_id');
            $table->json('raw_data');
            $table->json('validation_errors')->nullable();
            $table->boolean('is_validated')->default(false);
            $table->boolean('is_imported')->default(false);
            $table->string('status')->default('pending'); // pending, validated, imported, rejected
            $table->timestamp('uploaded_at');
            $table->timestamps();

            $table->index(['upload_session_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('temperature_validations');
    }
};
