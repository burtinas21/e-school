<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();           // e.g., 'school_name'
            $table->json('value')->nullable();          // store any type (string, number, boolean, array)
            $table->string('type')->default('string');  // optional: 'string', 'integer', 'boolean', etc.
            $table->timestamps();                       // for tracking when settings were last updated
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
