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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_id')->constrained();
            $table->foreignId('section_id')->constrained();
            $table->foreignId('subject_id')->constrained();
            $table->foreignId('student_id')->constrained();
            $table->foreignId('teacher_id')->constrained();
            $table->foreignId('period_id')->constrained('periods');
            $table->date('date');
            $table->enum('status', [
                'present',
                'absent',
                'late',
                'permission'
                ])->default('present');

            /**
             * to prevent the duplicate  ecord of attendance..
             */
            $table->unique(['student_id', 'subject_id', 'date', 'period_id'], 'unique_attendance');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
