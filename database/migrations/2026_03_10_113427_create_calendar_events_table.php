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
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('event_type', [
                            'holiday',
                            'exam',
                            'event',
                            'closure'
            ])->default('event');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_recurring')->default(false);
            $table->string('recurring_pattern', 50)->nullable()->comment('yearly, monthly, weekly');
            $table->boolean('affects_attendance')->default(true)
                          ->comment('if true, attendance cannot be marked');

                      /*  optional filtering (null means all grades/ sections) */
            $table->string('applicable_grades', 255)->nullable()
                                   ->comment('comma-separated grade IDs');
            $table->string('applicable_sections',255)->nullable()
                                   ->comment('comma-separated grade IDs');

                                   /*who create this event */
           $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->index(['start_date', 'end_date']);
            $table->index('event_type');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};
