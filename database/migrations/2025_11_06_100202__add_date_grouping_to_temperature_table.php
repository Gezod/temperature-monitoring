<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('temperature', function (Blueprint $table) {
            $table->date('reading_date')->nullable()->after('timestamp');
            $table->time('reading_time')->nullable()->after('reading_date');
            $table->boolean('is_validated')->default(false)->after('reading_time');
            $table->string('validation_status')->default('pending')->after('is_validated');
            $table->json('validation_notes')->nullable()->after('validation_status');

            $table->index(['machine_id', 'reading_date']);
            $table->index(['reading_date', 'validation_status']);
        });
    }

    public function down(): void
    {
        Schema::table('temperature', function (Blueprint $table) {
            $table->dropColumn(['reading_date', 'reading_time', 'is_validated', 'validation_status', 'validation_notes']);
        });
    }
};
