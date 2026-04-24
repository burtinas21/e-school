<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Notification;
use App\Models\Student;
use App\Models\Attendance;
use Carbon\Carbon;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $students = Student::with('guardian')->get();
        $recentAttendances = Attendance::where('date', '>=', Carbon::today()->subDays(3))
            ->whereIn('status', ['absent', 'late', 'permission'])
            ->with(['student', 'subject'])
            ->get();

        $createdCount = 0;

        foreach ($recentAttendances as $attendance) {
            $student = $students->firstWhere('id', $attendance->student_id);

            if ($student && $student->guardian) {
                // Map attendance status to notification type
                $status = $attendance->status;
                $type = match($status) {
                    'absent' => 'absence',
                    'late' => 'late',
                    'permission' => 'permission',
                    default => 'absence',
                };

                $title = ucfirst($status) . ' Alert';
                $message = "Your child {$student->user->name} was marked {$status} ";
                $message .= "in {$attendance->subject->name} ";
                $message .= "on " . $attendance->date->format('M d, Y') . ".";

                Notification::firstOrCreate(
                    [
                        'student_id' => $student->id,
                        'guardian_id' => $student->guardian_id,
                        'type' => $type,
                        'sent_at' => $attendance->created_at,
                    ],
                    [
                        'title' => $title,
                        'message' => $message,
                        'read_at' => rand(0, 1) ? Carbon::now()->subHours(rand(1, 24)) : null,
                        'status' => 'sent',
                    ]
                );

                $createdCount++;
            }
        }

        $this->command->info("✅ $createdCount notifications seeded successfully!");
    }
}
