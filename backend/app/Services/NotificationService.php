<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Attendance;
use App\Models\TeacherAssignment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Notify guardian about student attendance (Real-time).
     */
    public static function notifyGuardianOfAttendance(Attendance $attendance)
    {
        // Eager load ALL necessary relations to avoid lazy-loading issues in transactions
        $student = Student::with(['guardian.user', 'user'])->find($attendance->student_id);
        
        if (!$student || !$student->guardian_id || !$student->guardian || !$student->guardian->receive_notifications) {
            return;
        }

        $statusLabel = ucfirst($attendance->status);
        $subjectName = $attendance->subject->name ?? 'Subject';
        
        // Ensure date is a Carbon instance for safe formatting
        $dateStr = $attendance->date instanceof Carbon ? $attendance->date->toDateString() : Carbon::parse($attendance->date)->toDateString();

        try {
            Notification::create([
                'student_id'  => $student->id,
                'guardian_id' => $student->guardian->id,
                'type'        => $attendance->status === 'absent' ? 'absence' : ($attendance->status === 'late' ? 'late' : 'daily_summary'),
                'title'       => "Attendance Alert: {$statusLabel}",
                'message'     => "Dear Parent, " . ($student->user->name ?? 'Student') . " was marked {$attendance->status} for {$subjectName} on {$dateStr}.",
                'sent_at'     => now(),
                'status'      => 'sent',
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to create notification: " . $e->getMessage());
            // Rethrow so transaction fails and test catches it
            throw $e;
        }
    }

    /**
     * Notify teacher about a new assignment.
     */
    public static function notifyTeacherOfAssignment(TeacherAssignment $assignment)
    {
        $teacher = $assignment->teacher()->with('user')->first();
        if (!$teacher) return;

        Notification::create([
            'teacher_id'  => $teacher->id,
            'type'        => 'event',
            'title'       => 'New Academic Assignment',
            'message'     => "You have been assigned as the " . ($assignment->is_primary ? 'Primary' : 'Assistant') . 
                             " teacher for {$assignment->subject->name} in Section {$assignment->section->name}.",
            'sent_at'     => now(),
            'status'      => 'sent',
        ]);
    }

    /**
     * Detect and notify teacher of consecutive absences (Professional Recommendation).
     */
    public static function checkAbsenceTrend(Student $student, $teacherId)
    {
        $recentAttendances = Attendance::where('student_id', $student->id)
            ->orderBy('date', 'desc')
            ->take(3)
            ->pluck('status');

        if ($recentAttendances->count() === 3 && $recentAttendances->every(fn($s) => $s === 'absent')) {
            Notification::create([
                'teacher_id' => $teacherId,
                'type'       => 'warning',
                'title'      => 'Chronic Absence Alert',
                'message'    => "Student " . ($student->user->name ?? 'Student') . " has been absent for 3 consecutive days. Please consider reaching out to the guardian.",
                'sent_at'    => now(),
                'status'     => 'sent',
            ]);
        }
    }
}
