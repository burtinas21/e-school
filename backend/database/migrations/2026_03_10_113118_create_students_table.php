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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
           // $table->string('name',100);
           $table->foreignId('user_id')->constrained()->unique()->cascadeOnDelete();
           $table->foreignId('guardian_id')->constrained()->cascadeOnDelete();
           $table->foreignId('grade_id')->constrained();  // don't cascade delete grades
           $table->foreignId('section_id')->constrained();  // don't delete cascade on sections
           $table->string('addmission_number',20)->unique();
           $table->date('date_of_birth')->nullable();
           $table->enum('gender', ['female', 'male']);
           $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
