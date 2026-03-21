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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guardian_id')->constrained()->cascadeOnDelete();
            $table->enum('type', [
                     'absence',
                     'late',
                     'permission',
                     'daily_summary',
                     'warning',
                     'event',
                    ])->default('absence');
            $table->string('title', 255);
            $table->text('message');
            $table->timestamp('sent_at')->useCurrent();
            $table->timestamp('read_at')->nullable();
            $table->enum('status', ['pending', 'sent', 'failed', 'read']);
           // $table->enum('status',['read', 'unread']);
            $table->timestamps();

            // index for faster quwries
            $table->index(['guardian_id', 'status']);
            $table->index('sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
