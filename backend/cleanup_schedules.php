<?php

use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Cleaning duplicates from 'schedules' table...\n";

$duplicates = DB::table('schedules')
    ->select('teacher_id', 'day_of_week', 'period_id', DB::raw('MIN(id) as min_id'))
    ->groupBy('teacher_id', 'day_of_week', 'period_id')
    ->having(DB::raw('count(*)'), '>', 1)
    ->get();

foreach ($duplicates as $duplicate) {
    echo "Found duplicate for Teacher Unit: Teacher={$duplicate->teacher_id}, Day={$duplicate->day_of_week}, Period={$duplicate->period_id}. Keeping ID: {$duplicate->min_id}\n";
    
    $deleted = DB::table('schedules')
        ->where('teacher_id', $duplicate->teacher_id)
        ->where('day_of_week', $duplicate->day_of_week)
        ->where('period_id', $duplicate->period_id)
        ->where('id', '!=', $duplicate->min_id)
        ->delete();
        
    echo "Deleted $deleted redundant copies.\n";
}

echo "Cleanup complete.\n";
