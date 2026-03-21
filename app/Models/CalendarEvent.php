<?php
// app/Models/CalendarEvent.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;  // ✅ Add this import

class CalendarEvent extends Model
{
    use HasFactory;

    protected $table = 'calendar_events';

    protected $fillable = [
        'title',
        'description',
        'event_type',
        'start_date',
        'end_date',
        'is_recurring',
        'recurring_pattern',
        'affects_attendance',
        'applicable_grades',
        'applicable_sections',
        'created_by'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_recurring' => 'boolean',
        'affects_attendance' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who created this event.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if event is active on a given date.
     */
    public function isActiveOn($date)
    {
        $date = $date instanceof Carbon ? $date : Carbon::parse($date);
        return $date->between($this->start_date, $this->end_date);
    }

    /**
     * Check if this event affects a specific grade.
     */
    public function affectsGrade($gradeId)
    {
        if (!$this->applicable_grades) {
            return true;
        }
        $grades = explode(',', $this->applicable_grades);
        return in_array($gradeId, $grades);
    }

    /**
     * Check if this event affects a specific section.
     */
    public function affectsSection($sectionId)
    {
        if (!$this->applicable_sections) {
            return true;
        }
        $sections = explode(',', $this->applicable_sections);
        return in_array($sectionId, $sections);
    }

    /**
     * ===========================================
     * WEEKEND HELPER METHODS (STATIC)
     * ===========================================
     */

    /**
     * Define school days (can be customized)
     */
    public static function getSchoolDays(): array
    {
        return ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    }

    /**
     * Define weekend days
     */
    public static function getWeekendDays(): array
    {
        return ['Saturday', 'Sunday'];
    }

    /**
     * Check if a given date is a weekend
     */
    public static function isWeekend($date): bool
    {
        $date = $date instanceof Carbon ? $date : Carbon::parse($date);
        return in_array($date->format('l'), self::getWeekendDays());
    }

    /**
     * Check if a given date is a school day (not weekend)
     */
    public static function isSchoolDay($date): bool
    {
        return !self::isWeekend($date);
    }

    /**
     * Check if attendance can be marked on a given date
     * Combines weekend check AND holiday check
     */
    public static function canMarkAttendance($date, $studentId = null): bool
    {
        $date = $date instanceof Carbon ? $date : Carbon::parse($date);
        
        // 1. Check if it's weekend
        if (self::isWeekend($date)) {
            return false;
        }
        
        // 2. Check if it's a holiday
        $holidays = self::where('event_type', 'holiday')
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->where('affects_attendance', true)
            ->get();
        
        if ($holidays->isEmpty()) {
            return true;
        }
        
        // Check student-specific holidays
        if ($studentId) {
            $student = Student::with(['grade', 'section'])->find($studentId);
            
            if ($student) {
                foreach ($holidays as $holiday) {
                    if ($holiday->affectsGrade($student->grade_id) || 
                        $holiday->affectsSection($student->section_id)) {
                        return false;
                    }
                }
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get the reason why attendance cannot be marked
     */
    public static function getAttendanceBlockReason($date): ?array
    {
        $date = $date instanceof Carbon ? $date : Carbon::parse($date);
        
        // Check weekend first
        if (self::isWeekend($date)) {
            return [
                'blocked' => true,
                'reason' => 'weekend',
                'message' => 'School is closed on ' . $date->format('l') . ' (weekend)',
                'date' => $date->format('Y-m-d')
            ];
        }
        
        // Check holidays
        $holiday = self::where('event_type', 'holiday')
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->where('affects_attendance', true)
            ->first();
        
        if ($holiday) {
            return [
                'blocked' => true,
                'reason' => 'holiday',
                'message' => 'School is closed: ' . $holiday->title,
                'event' => $holiday,
                'date' => $date->format('Y-m-d')
            ];
        }
        
        return null;
    }

    /**
     * Get upcoming school closures (weekends + holidays)
     */
    public static function getUpcomingClosures($days = 7): array
    {
        $closures = [];
        
        for ($i = 0; $i < $days; $i++) {
            $date = Carbon::today()->addDays($i);
            $reason = self::getAttendanceBlockReason($date);
            
            if ($reason) {
                $closures[] = [
                    'date' => $date->format('Y-m-d'),
                    'day' => $date->format('l'),
                    'reason' => $reason['reason'],
                    'message' => $reason['message'],
                    'is_weekend' => self::isWeekend($date),
                    'is_holiday' => ($reason['reason'] === 'holiday')
                ];
            }
        }
        
        return $closures;
    }
}