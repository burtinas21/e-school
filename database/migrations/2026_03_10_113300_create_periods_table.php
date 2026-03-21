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
        Schema::create('periods', function (Blueprint $table) {
            $table->id();
                       // Basic period information
            $table->string('name', 50);              // "Period 1", "Period 2", "Morning Break", "Lunch"
            $table->integer('period_number');         // 1,2,3,4,5,6 (for ordering)
            $table->time('start_time');                // 08:00:00
            $table->time('end_time');                  // 08:45:00

            // Break information
            $table->boolean('is_break')->default(false);
            $table->string('break_name', 50)->nullable(); // "Morning Break", "Lunch"

            // Status
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Indexes for faster queries
            $table->index('period_number');
            $table->index('is_break');

            // Ensure period numbers are unique
            $table->unique('period_number');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('periods');
    }
};
