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
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_id')->constrained()->cascadeOnDelete();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();
            $table->enum('day_of_week', [
                       'Monday',
                       'Tuesday',
                        'Wednesday',
                        'Thursday',
                        'Friday',
                         ]);
            $table->foreignId('period_id')->constrained('periods')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // prevent duplicate schedules
            $table->unique(['section_id', 'day_of_week','period_id'], 'unique_schedule');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
