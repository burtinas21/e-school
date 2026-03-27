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

class AttendanceController extends Controller
{
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
                'addmission_number'=> $student->addmission_number,
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
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Normalize date to Y-m-d
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

        $errors = [];
        $count  = 0;

        foreach ($request->attendances as $att) {
            // Check for existing record using whereDate()
            $exists = Attendance::where('student_id', $att['student_id'])
                ->where('subject_id', $request->subject_id)
                ->whereDate('date', $date)       // compare only date part
                ->where('period_id', $request->period_id)
                ->exists();

            if ($exists) {
                $errors[] = "Attendance already marked for student ID {$att['student_id']}";
                continue;
            }

            Attendance::create([
                'student_id' => $att['student_id'],
                'subject_id' => $request->subject_id,
                'date'       => $date,
                'period_id'  => $request->period_id,
                'teacher_id' => $teacher->id,
                'grade_id'   => $request->grade_id,
                'section_id' => $request->section_id,
                'status'     => $att['status'],
            ]);

            $count++;
        }

        if ($count === 0 && !empty($errors)) {
            return response()->json([
                'success' => false,
                'message' => 'No new attendance records saved',
                'errors'  => $errors,
            ], 422);
        }

        // Match test expectation: "1 attendance records saved successfully"
        return response()->json([
            'success' => true,
            'message' => $count . " attendance records saved successfully",
            'errors'  => $errors,
        ]);
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
