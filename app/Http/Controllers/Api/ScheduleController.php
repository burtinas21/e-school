<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\Period;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ScheduleController extends Controller
{
    /**
     * Get schedule for a specific section
     *
     * @param int $section_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSectionSchedule($section_id)
    {
        // Fetch all schedules for the given section
        $schedules = Schedule::where('section_id', $section_id)
            ->with(['subject', 'teacher.user', 'period'])
            ->orderBy('day_of_week')
            ->orderBy('period_id')
            ->get()
            ->groupBy('day_of_week'); // Group by day (Monday, Tuesday, etc.)

        return response()->json([
            'success' => true,
            'data' => $schedules
        ]);
    }

    /**
     * Get teacher's schedule
     *
     * @param int $teacher_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTeacherSchedule($teacher_id)
    {
        // Fetch all schedules for the given teacher
        $schedules = Schedule::where('teacher_id', $teacher_id)
            ->with(['subject', 'section.grade', 'period'])
            ->orderBy('day_of_week')
            ->orderBy('period_id')
            ->get()
            ->groupBy('day_of_week'); // Group by day

        return response()->json([
            'success' => true,
            'data' => $schedules
        ]);
    }

    /**
     * Store a new schedule entry
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Validate incoming request
        $validator = Validator::make($request->all(), [
            'grade_id' => 'required|exists:grades,id',
            'section_id' => 'required|exists:sections,id',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:teachers,id',
            'period_id' => 'required|exists:periods,id',
            'day_of_week' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday',
            'is_active' => 'sometimes|boolean', // Optional, defaults to true
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Check for duplicate schedule (same section, day, and period)
        $exists = Schedule::where('section_id', $request->section_id)
            ->where('day_of_week', $request->day_of_week)
            ->where('period_id', $request->period_id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'A schedule already exists for this section on ' .
                             $request->day_of_week . ' during this period'
            ], 422);
        }

        // Create new schedule record
        $schedule = Schedule::create([
            'grade_id' => $request->grade_id,
            'section_id' => $request->section_id,
            'subject_id' => $request->subject_id,
            'teacher_id' => $request->teacher_id,
            'period_id' => $request->period_id,
            'day_of_week' => $request->day_of_week,
            'is_active' => $request->is_active ?? true, // Default to true if not provided
        ]);

        // Return the created schedule with related data
        return response()->json([
            'success' => true,
            'message' => 'Schedule created successfully',
            'data' => $schedule->load(['subject', 'teacher.user', 'period', 'grade', 'section'])
        ], 201);
    }

    /**
     * Update an existing schedule
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        // Find the schedule
        $schedule = Schedule::find($id);

        if (!$schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Schedule not found'
            ], 404);
        }

        // Validate the fields that can be updated
        $validator = Validator::make($request->all(), [
            'subject_id' => 'sometimes|exists:subjects,id',
            'teacher_id' => 'sometimes|exists:teachers,id',
            'period_id' => 'sometimes|exists:periods,id',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Update only the provided fields
        $schedule->update($request->only([
            'subject_id',
            'teacher_id',
            'period_id',
            'is_active'
        ]));

        // Return the updated schedule
        return response()->json([
            'success' => true,
            'message' => 'Schedule updated successfully',
            'data' => $schedule->load(['subject', 'teacher.user', 'period'])
        ]);
    }

    /**
     * Delete a schedule
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        // Find the schedule
        $schedule = Schedule::find($id);

        if (!$schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Schedule not found'
            ], 404);
        }

        // Delete the schedule
        $schedule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Schedule deleted successfully'
        ]);
    }

    /**
     * Get weekly schedule grid for a section
     * Returns a matrix format: days x periods
     *
     * @param int $section_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function weeklySchedule($section_id)
    {
        // Define school days
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

        // Get all periods ordered by period number
        $periods = Period::orderBy('period_number')->get();

        // Fetch all schedules for this section
        $schedules = Schedule::where('section_id', $section_id)
            ->with(['subject', 'teacher.user'])
            ->get()
            ->keyBy(function ($item) {
                // Create composite key: "Monday_1" for day + period
                return $item->day_of_week . '_' . $item->period_id;
            });

        // Build the grid
        $grid = [];
        foreach ($days as $day) {
            $row = ['day' => $day];

            // Add each period column
            foreach ($periods as $period) {
                $key = $day . '_' . $period->id;
                $schedule = $schedules->get($key);

                if ($schedule) {
                    // Format the schedule data for display
                    $row['period_' . $period->period_number] = [
                        'id' => $schedule->id,
                        'subject' => $schedule->subject->name,
                        'teacher' => $schedule->teacher->user->name,
                        'is_active' => $schedule->is_active,
                    ];
                } else {
                    $row['period_' . $period->period_number] = null; // Empty slot
                }
            }
            $grid[] = $row;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'periods' => $periods->map(function($period) {
                    return [
                        'id' => $period->id,
                        'name' => $period->name,
                        'number' => $period->period_number,
                        'time_range' => $period->time_range,
                    ];
                }),
                'grid' => $grid
            ]
        ]);
    }

    /**
     * Get all active schedules for a section (for dropdown/selection)
     *
     * @param int $section_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getActiveSchedules($section_id)
    {
        $schedules = Schedule::where('section_id', $section_id)
            ->where('is_active', true)
            ->with(['subject', 'period'])
            ->orderBy('day_of_week')
            ->orderBy('period_id')
            ->get()
            ->map(function($schedule) {
                return [
                    'id' => $schedule->id,
                    'display_name' => $schedule->display_name,
                    'day' => $schedule->day_of_week,
                    'subject' => $schedule->subject->name,
                    'time' => $schedule->time_range,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $schedules
        ]);
    }
}
