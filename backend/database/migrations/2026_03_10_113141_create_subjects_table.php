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
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('name',100);
            $table->foreignId('grade_id')->constrained();
            $table->string('subject_code',20)->unique()->nullable();
            $table->text('description')->nullable();
            $table->decimal('credits',3,1)->default(3.0);  // credit hours
            $table->boolean('is_core')->default(true);  // core or elective
            $table->boolean('is_active')->default(true);  // active status
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
