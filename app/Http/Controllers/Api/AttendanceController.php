<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AttendanceController extends Controller
{
    /**
     * Get attendance for a specific class/date
     */
    public function getClassAttendance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'grade_id' => 'required|exists:grades,id',
            'section_id' => 'required|exists:sections,id',
            'subject_id' => 'required|exists:subjects,id',
            'date' => 'required|date',
            'period_id' => 'required|exists:periods,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Get all students in the section
        $students = Student::with('user')
            ->where('section_id', $request->section_id)
            ->get();

        // Get existing attendance records for this date/subject/period
        $attendances = Attendance::where('date', $request->date)
            ->where('subject_id', $request->subject_id)
            ->where('period_id', $request->period_id)
            ->get()
            ->keyBy('student_id');

        // Prepare response with student list and their attendance status
        $data = $students->map(function ($student) use ($attendances) {
            $attendance = $attendances->get($student->id);
            return [
                'student_id' => $student->id,
                'student_name' => $student->user->name,
                'admission_number' => $student->admission_number,
                'status' => $attendance ? $attendance->status : null,
                'attendance_id' => $attendance ? $attendance->id : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Mark attendance for multiple students
     */
    public function markAttendance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'grade_id' => 'required|exists:grades,id',
            'section_id' => 'required|exists:sections,id',
            'subject_id' => 'required|exists:subjects,id',
            'date' => 'required|date',
            'period_id' => 'required|exists:periods,id',
            'attendances' => 'required|array',
            'attendances.*.student_id' => 'required|exists:students,id',
            'attendances.*.status' => 'required|in:present,absent,late,permission',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $teacher = Auth::user()->teacher;
        $count = 0;

        foreach ($request->attendances as $att) {
            $attendance = Attendance::updateOrCreate(
                [
                    'student_id' => $att['student_id'],
                    'subject_id' => $request->subject_id,
                    'date' => $request->date,
                    'period_id' => $request->period_id,
                ],
                [
                    'teacher_id' => $teacher->id,
                    'grade_id' => $request->grade_id,      // ✅ ADDED
                    'section_id' => $request->section_id,
                    'status' => $att['status'],
                ]
            );
            $count++;
        }

        return response()->json([
            'success' => true,
            'message' => "$count attendance records saved successfully"
        ]);
    }

    /**
     * Get student attendance history
     */
    public function studentHistory($student_id)
    {
        $attendances = Attendance::where('student_id', $student_id)
            ->with(['subject', 'period', 'grade', 'section'])
            ->orderBy('date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $attendances
        ]);
    }

    /**
     * Get attendance report for a date range
     */
    public function report(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'section_id' => 'nullable|exists:sections,id',
            'grade_id' => 'nullable|exists:grades,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $query = Attendance::with(['student.user', 'subject', 'period', 'grade', 'section'])
            ->whereBetween('date', [$request->start_date, $request->end_date]);

        if ($request->section_id) {
            $query->where('section_id', $request->section_id);
        }

        if ($request->grade_id) {
            $query->where('grade_id', $request->grade_id);
        }

        $attendances = $query->get();

        // Calculate statistics
        $stats = [
            'total' => $attendances->count(),
            'present' => $attendances->where('status', 'present')->count(),
            'absent' => $attendances->where('status', 'absent')->count(),
            'late' => $attendances->where('status', 'late')->count(),
            'permission' => $attendances->where('status', 'permission')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'records' => $attendances,
                'statistics' => $stats
            ]
        ]);
    }
}
