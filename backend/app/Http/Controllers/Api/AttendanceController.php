<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Illuminate\Support\Facades\DB;
class AttendanceController extends Controller
{


/**
 * @group Attendance
 *
 * Mark Attendance
 *
 * @authenticated
 */

    /**
     * Get attendance for a specific class/date (teacher/admin only)
     */
    public function getClassAttendance(Request $request):JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'grade_id'    => 'required|exists:grades,id',
            'section_id'  => 'required|exists:sections,id',
            'subject_id'  => 'required|exists:subjects,id',
            'date'        => 'required|date',
            'period_id'   => 'required|exists:periods,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Normalize date to Y-m-d for storage
        $date = Carbon::parse($request->date)->toDateString();

        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        // Only teachers or admins
        if (!in_array($user->role_id, [1, 2])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // If teacher, check assignment
        if ($user->role_id == 2) {
            $teacher = Teacher::where('user_id', $user->id)->first();
            if (!$teacher) {
                return response()->json(['success' => false, 'message' => 'Teacher profile not found'], 403);
            }
            $assigned = TeacherAssignment::where('teacher_id', $teacher->id)
                ->where('section_id', $request->section_id)
                ->where('subject_id', $request->subject_id)
                ->exists();
            if (!$assigned) {
                return response()->json(['success' => false, 'message' => 'You are not assigned to this section/subject'], 403);
            }
        }

        $students = Student::with('user')
            ->where('section_id', $request->section_id)
            ->get();

        // Use whereDate() to compare only the date part (in case column is datetime)
        $attendances = Attendance::whereDate('date', $date)
            ->where('subject_id', $request->subject_id)
            ->where('period_id', $request->period_id)
            ->get()
            ->keyBy('student_id');

        $data = $students->map(function ($student) use ($attendances) {
            $attendance = $attendances->get($student->id);
            return [
                'student_id'      => $student->id,
                'student_name'    => $student->user->name,
                'admission_number'=> $student->admission_number,
                'status'          => $attendance ? $attendance->status : null,
                'attendance_id'   => $attendance ? $attendance->id : null,
            ];
        });

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Mark attendance for multiple students (teacher only)
     */
    public function markAttendance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'grade_id'          => 'required|exists:grades,id',
            'section_id'        => 'required|exists:sections,id',
            'subject_id'        => 'required|exists:subjects,id',
            'date'              => 'required|date',
            'period_id'         => 'required|exists:periods,id',
            'attendances'       => 'required|array',
            'attendances.*.student_id' => 'required|exists:students,id',
            'attendances.*.status'     => 'required|in:present,absent,late,permission',
            'attendances.*.remarks'    => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $date = Carbon::parse($request->date)->toDateString();

        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $teacher = Teacher::where('user_id', $user->id)->first();
        if (!$teacher) {
            return response()->json(['success' => false, 'message' => 'Only teachers can mark attendance'], 403);
        }

        // Check assignment
        $assigned = TeacherAssignment::where('teacher_id', $teacher->id)
            ->where('section_id', $request->section_id)
            ->where('subject_id', $request->subject_id)
            ->exists();

        if (!$assigned) {
            return response()->json(['success' => false, 'message' => 'You are not assigned to this section/subject'], 403);
        }

        // Future date check
        if (Carbon::parse($date)->isFuture()) {
            return response()->json(['success' => false, 'message' => 'Cannot mark attendance for future dates'], 422);
        }

        // Logic Upgrade: Weekend Check
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;
        $isWeekend = in_array($dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY]);
        $allowWeekend = \App\Models\Setting::get('allow_weekend_marking', false);

        if ($isWeekend && !$allowWeekend) {
            return response()->json(['success' => false, 'message' => 'Attendance marking is disabled on weekends'], 422);
        }

        // Logic Upgrade: Holiday/Closure Check (Calendar Events)
        $holiday = \App\Models\CalendarEvent::whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->whereIn('event_type', ['holiday', 'closure'])
            ->where('affects_attendance', true)
            ->first();

        if ($holiday) {
            return response()->json(['success' => false, 'message' => "Attendance disabled: Today is a school {$holiday->event_type} ({$holiday->title})"], 422);
        }

        try {
            return DB::transaction(function () use ($request, $date, $teacher) {
                $studentIds = collect($request->attendances)->pluck('student_id')->toArray();

                // Pre-check for existing records to ensure idempotency
                $existingIds = Attendance::whereDate('date', $date)
                    ->where('subject_id', $request->subject_id)
                    ->where('period_id', $request->period_id)
                    ->whereIn('student_id', $studentIds)
                    ->pluck('student_id')
                    ->toArray();

                $toCreate = [];
                $skippedCount = 0;

                foreach ($request->attendances as $att) {
                    if (in_array($att['student_id'], $existingIds)) {
                        $skippedCount++;
                        continue;
                    }

                    $attendance = Attendance::create([
                        'student_id' => $att['student_id'],
                        'subject_id' => $request->subject_id,
                        'grade_id'   => $request->grade_id,
                        'section_id' => $request->section_id,
                        'period_id'  => $request->period_id,
                        'teacher_id' => $teacher->id,
                        'date'       => $date,
                        'status'     => $att['status'],
                        'remarks'    => $att['remarks'] ?? null,
                    ]);

                    // Professional Recommendation: Trigger Auto-Notification
                    if (in_array($attendance->status, ['absent', 'late'])) {
                        \App\Services\NotificationService::notifyGuardianOfAttendance($attendance);
                    }
                }

                return response()->json([
                    'success' => true,
                    'message' => count($request->attendances) - $skippedCount . " attendance records saved successfully",
                    'data' => [
                        'created' => count($request->attendances) - $skippedCount,
                        'skipped' => $skippedCount
                    ]
                ]);
            }, 5);
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() == '23000') {
                return response()->json([
                    'success' => false,
                    'message' => 'Attendance already exists for some entries.',
                    'error_code' => 'DUPLICATE_SUBMISSION'
                ], 422);
            }
            throw $e;
        }
    }

    /**
     * Get student attendance history (role‑based)
     */
    public function studentHistory($student_id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $student = Student::find($student_id);
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Student not found'], 404);
        }

        $allowed = false;

        if ($user->role_id == 1) { // Admin
            $allowed = true;
        } elseif ($user->role_id == 2) { // Teacher
            $teacher = Teacher::where('user_id', $user->id)->first();
            if ($teacher) {
                $allowed = TeacherAssignment::where('teacher_id', $teacher->id)
                    ->where('section_id', $student->section_id)
                    ->exists();
            }
        } elseif ($user->role_id == 3) { // Student
            $studentUser = Student::where('user_id', $user->id)->first();
            if ($studentUser && $studentUser->id == $student->id) {
                $allowed = true;
            }
        } elseif ($user->role_id == 4) { // Parent (guardian)
            $guardian = Guardian::where('user_id', $user->id)->first();
            if ($guardian && $guardian->id == $student->guardian_id) {
                $allowed = true;
            }
        }

        if (!$allowed) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $attendances = Attendance::where('student_id', $student_id)
            ->with(['subject', 'period', 'grade', 'section'])
            ->orderBy('date', 'desc')
            ->get();

        return response()->json(['success' => true, 'data' => $attendances]);
    }

    /**
     * Get attendance report for a date range (role‑based)
     */
    public function report(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'section_id' => 'nullable|exists:sections,id',
            'grade_id'   => 'nullable|exists:grades,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $startDate = Carbon::parse($request->start_date)->toDateString();
        $endDate   = Carbon::parse($request->end_date)->toDateString();

        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $allowed = false;

        if ($user->role_id == 1) {
            $allowed = true;
        } elseif ($user->role_id == 2) {
            $teacher = Teacher::where('user_id', $user->id)->first();
            if ($teacher) {
                if ($request->has('section_id')) {
                    $allowed = TeacherAssignment::where('teacher_id', $teacher->id)
                        ->where('section_id', $request->section_id)
                        ->exists();
                } elseif ($request->has('grade_id')) {
                    $allowed = TeacherAssignment::where('teacher_id', $teacher->id)
                        ->whereHas('section', function ($q) use ($request) {
                            $q->where('grade_id', $request->grade_id);
                        })
                        ->exists();
                } else {
                    $allowed = true;
                }
            }
        }

        if (!$allowed) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $query = Attendance::with(['student.user', 'subject', 'period', 'grade', 'section'])
            ->whereBetween('date', [$startDate, $endDate]);

        if ($request->filled('section_id')) {
            $query->where('section_id', $request->section_id);
        }
        if ($request->filled('grade_id')) {
            $query->where('grade_id', $request->grade_id);
        }

        $attendances = $query->get();

        $stats = [
            'total'      => $attendances->count(),
            'present'    => $attendances->where('status', 'present')->count(),
            'absent'     => $attendances->where('status', 'absent')->count(),
            'late'       => $attendances->where('status', 'late')->count(),
            'permission' => $attendances->where('status', 'permission')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'records'    => $attendances,
                'statistics' => $stats,
            ],
        ]);
    }
}
