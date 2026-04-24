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
        Schema::create('teacher_assignments', function (Blueprint $table) {
            $table->id();

            // Foreign key to teachers table
            $table->foreignId('teacher_id')
                  ->constrained('teachers')
                  ->cascadeOnDelete();

            // Foreign key to subjects table
            $table->foreignId('subject_id')
                  ->constrained('subjects')
                  ->cascadeOnDelete();

            // Foreign key to sections table (grade is accessed through section)
            $table->foreignId('section_id')
                  ->constrained('sections')
                  ->cascadeOnDelete();

            // Academic year (e.g., 2024)
            $table->year('academic_year');

            // Is this the primary teacher for this subject/section?
            $table->boolean('is_primary')->default(true);

            // Timestamps
            $table->timestamps();

            // Unique constraint to prevent duplicate assignments
            $table->unique(['teacher_id', 'subject_id', 'section_id', 'academic_year'],
                          'unique_teacher_assignment');

            // Indexes for faster queries
            $table->index('teacher_id');
            $table->index('subject_id');
            $table->index('section_id');
            $table->index('academic_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_assignments');
    }
};
