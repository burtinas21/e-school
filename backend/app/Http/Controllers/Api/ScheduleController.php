<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\Period;
use App\Models\Section;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ScheduleController extends Controller
{
    /**
     * Get schedule for a specific section.
     * Access: any authenticated user.
     *
     * @param int $section_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSectionSchedule($section_id)
    {
        $this->requireAuthentication();

        $schedules = Schedule::where('section_id', $section_id)
            ->with(['subject', 'teacher.user', 'period'])
            ->orderBy('day_of_week')
            ->orderBy('period_id')
            ->get()
            ->groupBy('day_of_week');

        return response()->json(['success' => true, 'data' => $schedules]);
    }

    /**
     * Get teacher's schedule.
     * Access: any authenticated user.
     *
     * @param int $teacher_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTeacherSchedule($teacher_id)
    {
        $this->requireAuthentication();

        $schedules = Schedule::where('teacher_id', $teacher_id)
            ->with(['subject', 'section.grade', 'period'])
            ->orderBy('day_of_week')
            ->orderBy('period_id')
            ->get()
            ->groupBy('day_of_week');

        return response()->json(['success' => true, 'data' => $schedules]);
    }

    /**
     * Store a new schedule entry.
     * Access: admin only.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $this->requireAdmin();

        $validator = Validator::make($request->all(), [
            'grade_id'    => 'required|exists:grades,id',
            'section_id'  => 'required|exists:sections,id',
            'subject_id'  => 'required|exists:subjects,id',
            'teacher_id'  => 'required|exists:teachers,id',
            'period_id'   => 'required|exists:periods,id',
            'day_of_week' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday',
            'is_active'   => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Business Rule 1: The section must exist and its grade must match the provided grade_id
        $section = Section::with('grade')->find($request->section_id);
        if ($section->grade_id != $request->grade_id) {
            return response()->json([
                'success' => false,
                'message' => 'Grade ID does not match the section\'s grade'
            ], 422);
        }

        // Business Rule 2: The subject must belong to the grade of the section
        $subject = Subject::find($request->subject_id);
        if ($subject->grade_id != $section->grade_id) {
            return response()->json([
                'success' => false,
                'message' => 'Subject does not belong to the grade of the selected section'
            ], 422);
        }

        // Business Rule 3: No duplicate schedule for the same section, day, and period
        $exists = Schedule::where('section_id', $request->section_id)
            ->where('day_of_week', $request->day_of_week)
            ->where('period_id', $request->period_id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => "A schedule already exists for this section on {$request->day_of_week} during this period"
            ], 422);
        }

        // Business Rule 4: A teacher cannot be scheduled in two different sections at the same day and period
        $teacherConflict = Schedule::where('teacher_id', $request->teacher_id)
            ->where('day_of_week', $request->day_of_week)
            ->where('period_id', $request->period_id)
            ->exists();

        if ($teacherConflict) {
            return response()->json([
                'success' => false,
                'message' => 'This teacher is already scheduled on the same day and period in another section'
            ], 422);
        }

        // Create schedule
        $schedule = Schedule::create([
            'grade_id'    => $request->grade_id,
            'section_id'  => $request->section_id,
            'subject_id'  => $request->subject_id,
            'teacher_id'  => $request->teacher_id,
            'period_id'   => $request->period_id,
            'day_of_week' => $request->day_of_week,
            'is_active'   => $request->is_active ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Schedule created successfully',
            'data'    => $schedule->load(['subject', 'teacher.user', 'period', 'grade', 'section'])
        ], 201);
    }

    /**
     * Update an existing schedule.
     * Access: admin only.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $this->requireAdmin();

        $schedule = Schedule::find($id);
        if (!$schedule) {
            return response()->json(['success' => false, 'message' => 'Schedule not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'subject_id' => 'sometimes|exists:subjects,id',
            'teacher_id' => 'sometimes|exists:teachers,id',
            'period_id'  => 'sometimes|exists:periods,id',
            'is_active'  => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // If updating teacher, check for teacher conflict
        if ($request->has('teacher_id')) {
            $teacherConflict = Schedule::where('teacher_id', $request->teacher_id)
                ->where('day_of_week', $schedule->day_of_week)
                ->where('period_id', $request->period_id ?? $schedule->period_id)
                ->where('id', '!=', $id)
                ->exists();

            if ($teacherConflict) {
                return response()->json([
                    'success' => false,
                    'message' => 'This teacher is already scheduled on the same day and period in another section'
                ], 422);
            }
        }

        // If updating period, ensure no conflict for the same section/day
        if ($request->has('period_id')) {
            $periodConflict = Schedule::where('section_id', $schedule->section_id)
                ->where('day_of_week', $schedule->day_of_week)
                ->where('period_id', $request->period_id)
                ->where('id', '!=', $id)
                ->exists();

            if ($periodConflict) {
                return response()->json([
                    'success' => false,
                    'message' => 'Another schedule already exists for this section on the same day and period'
                ], 422);
            }
        }

        // If updating subject, ensure it still belongs to the grade of the section
        if ($request->has('subject_id')) {
            $subject = Subject::find($request->subject_id);
            if ($subject->grade_id != $schedule->grade_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subject does not belong to the grade of the section'
                ], 422);
            }
        }

        $schedule->update($request->only(['subject_id', 'teacher_id', 'period_id', 'is_active']));

        // Logic Upgrade: Auto-Notify Teacher
        if ($schedule->teacher) {
            \App\Models\Notification::create([
                'teacher_id' => $schedule->teacher_id,
                'type'       => 'event',
                'title'      => 'Schedule Updated',
                'message'    => "The schedule for {$schedule->subject->name} on {$schedule->day_of_week} (Period {$schedule->period->period_number}) has been updated.",
                'sent_at'    => now(),
                'status'     => 'sent',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Schedule updated successfully',
            'data'    => $schedule->load(['subject', 'teacher.user', 'period'])
        ]);
    }

    /**
     * Delete a schedule.
     * Access: admin only.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $this->requireAdmin();

        $schedule = Schedule::find($id);
        if (!$schedule) {
            return response()->json(['success' => false, 'message' => 'Schedule not found'], 404);
        }

        $schedule->delete();

        return response()->json(['success' => true, 'message' => 'Schedule deleted successfully']);
    }

    /**
     * Get weekly schedule grid for a section.
     * Access: any authenticated user.
     *
     * @param int $section_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function weeklySchedule($section_id)
    {
        $this->requireAuthentication();

        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        $periods = Period::orderBy('period_number')->get();

        $schedules = Schedule::where('section_id', $section_id)
            ->with(['subject', 'teacher.user'])
            ->get()
            ->keyBy(fn($item) => $item->day_of_week . '_' . $item->period_id);

        $grid = [];
        foreach ($days as $day) {
            $row = ['day' => $day];
            foreach ($periods as $period) {
                $key = $day . '_' . $period->id;
                $schedule = $schedules->get($key);
                $row['period_' . $period->period_number] = $schedule ? [
                    'id'        => $schedule->id,
                    'subject'   => $schedule->subject->name,
                    'teacher'   => $schedule->teacher->user->name,
                    'is_active' => $schedule->is_active,
                ] : null;
            }
            $grid[] = $row;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'periods' => $periods->map(fn($p) => [
                    'id'          => $p->id,
                    'name'        => $p->name,
                    'number'      => $p->period_number,
                    'time_range'  => $p->time_range,
                ]),
                'grid' => $grid,
            ]
        ]);
    }

    /**
     * Get active schedules for a section (for dropdown/selection).
     * Access: any authenticated user.
     *
     * @param int $section_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getActiveSchedules($section_id)
    {
        $this->requireAuthentication();

        $schedules = Schedule::where('section_id', $section_id)
            ->where('is_active', true)
            ->with(['subject', 'period'])
            ->orderBy('day_of_week')
            ->orderBy('period_id')
            ->get()
            ->map(fn($schedule) => [
                'id'           => $schedule->id,
                'display_name' => $schedule->display_name,
                'day'          => $schedule->day_of_week,
                'subject'      => $schedule->subject->name,
                'time'         => $schedule->time_range,
            ]);

        return response()->json(['success' => true, 'data' => $schedules]);
    }

    /**
     * Helper method to ensure the request is authenticated.
     * Throws a JSON response if not.
     */
    private function requireAuthentication(): void
    {
        if (!auth()->check()) {
            abort(response()->json(['success' => false, 'message' => 'Authentication required.'], 401));
        }
    }

    /**
     * Helper method to ensure the user is an admin.
     */
    private function requireAdmin(): void
    {
        if (!auth()->check()) {
            abort(response()->json(['success' => false, 'message' => 'Authentication required.'], 401));
        }
        if (auth()->user()->role_id !== 1) {
            abort(response()->json(['success' => false, 'message' => 'Unauthorized. Admin access required.'], 403));
        }
    }
}
