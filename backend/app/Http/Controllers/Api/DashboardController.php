<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\Guardian;
use App\Models\User;
use App\Models\Attendance;
use App\Models\TeacherAssignment;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Main entry point: returns dashboard data based on authenticated user's role.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        switch ($user->role_id) {
            case 1: // Admin
                return $this->adminDashboard();
            case 2: // Teacher
                return $this->teacherDashboard($user);
            case 3: // Student
                return $this->studentDashboard($user);
            case 4: // Guardian / Parent
                return $this->parentDashboard($user);
            default:
                return response()->json(['success' => false, 'message' => 'Invalid user role'], 403);
        }
    }

    /**
     * Admin dashboard: overview statistics and recent activity.
     */
    private function adminDashboard()
    {
        $today = Carbon::today();

        $totalTeachers = Teacher::count();
        $totalStudents = Student::count();
        $totalUsers = User::count();
        $totalSections = \App\Models\Section::count();

        $todayAttendance = Attendance::whereDate('date', $today)->count();
        $totalAttendance = Attendance::count();

        // Last 5 days attendance trend
        $trend = [];
        for ($i = 4; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i);
            $count = Attendance::whereDate('date', $date)->count();
            $trend[] = ['date' => $date->toDateString(), 'count' => $count];
        }

        // Recent attendance (last 5 entries)
        $recentAttendance = Attendance::with(['student.user', 'subject', 'section'])
            ->orderBy('date', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($record) {
                return [
                    'id'          => $record->id,
                    'date'        => $record->date->toDateString(),
                    'student_name'=> $record->student->user->name,
                    'subject'     => $record->subject->name,
                    'status'      => $record->status,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'total_teachers'     => $totalTeachers,
                'total_students'     => $totalStudents,
                'total_users'        => $totalUsers,
                'total_sections'     => $totalSections,
                'today_attendance'   => $todayAttendance,
                'total_attendance'   => $totalAttendance,
                'attendance_trend'   => $trend,
                'recent_attendance'  => $recentAttendance,
            ]
        ]);
    }

    /**
     * Teacher dashboard: assigned subjects/sections and today's attendance.
     */
    private function teacherDashboard($user)
    {
        $teacher = Teacher::where('user_id', $user->id)->first();
        if (!$teacher) {
            return response()->json(['success' => false, 'message' => 'Teacher profile not found'], 403);
        }

        $assignments = TeacherAssignment::with(['subject', 'section.grade'])
            ->where('teacher_id', $teacher->id)
            ->get();

        $today = Carbon::today();

        // Attendance summary for each assignment (today)
        $attendanceSummary = [];
        foreach ($assignments as $assignment) {
            $total = Student::where('section_id', $assignment->section_id)->count();
            $present = Attendance::where('subject_id', $assignment->subject_id)
                ->where('section_id', $assignment->section_id)
                ->whereDate('date', $today)
                ->where('status', 'present')
                ->count();

            $attendanceSummary[] = [
                'subject'        => $assignment->subject->name,
                'section'        => $assignment->section->name,
                'grade'          => $assignment->section->grade->name,
                'total_students' => $total,
                'present'        => $present,
                'absent'         => $total - $present,
            ];
        }

        // Recent attendance marked by this teacher (last 5 days)
        $recentAttendance = Attendance::where('teacher_id', $teacher->id)
            ->with(['student.user', 'subject'])
            ->orderBy('date', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($record) {
                return [
                    'id'           => $record->id,
                    'date'         => $record->date->toDateString(),
                    'student_name' => $record->student->user->name,
                    'subject'      => $record->subject->name,
                    'status'       => $record->status,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'name'  => $user->name,
                    'email' => $user->email,
                ],
                'teacher' => [
                    'employee_id'   => $teacher->employee_id ?? 'N/A',
                    'qualification' => $teacher->qualification ?? 'N/A',
                    'hire_date'     => $teacher->hire_date ?? 'N/A',
                ],
                'assignments'          => $assignments,
                'today_attendance'     => $attendanceSummary,
                'recent_attendance'    => $recentAttendance,
            ]
        ]);
    }

    /**
     * Student dashboard: own attendance summary for current month.
     */
    private function studentDashboard($user)
    {
        $student = Student::with(['grade', 'section'])->where('user_id', $user->id)->first();
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Student profile not found'], 403);
        }

        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();

        $attendances = Attendance::where('student_id', $student->id)
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->get();

        $present   = $attendances->where('status', 'present')->count();
        $absent    = $attendances->where('status', 'absent')->count();
        $late      = $attendances->where('status', 'late')->count();
        $permission= $attendances->where('status', 'permission')->count();

        // Attendance trend for the last 7 days (if any)
        $trend = [];
        $today = Carbon::today();
        for ($i = 6; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i);
            $att = Attendance::where('student_id', $student->id)
                ->whereDate('date', $date)
                ->first();
            $trend[] = [
                'date'   => $date->toDateString(),
                'status' => $att ? $att->status : null,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'name'  => $user->name,
                    'email' => $user->email,
                ],
                'student' => [
                    'admission_number' => $student->admission_number,
                    'grade'            => $student->grade->name ?? 'N/A',
                    'section'          => $student->section->name ?? 'N/A',
                ],
                'attendance_summary' => [
                    'present'    => $present,
                    'absent'     => $absent,
                    'late'       => $late,
                    'permission' => $permission,
                    'total'      => $attendances->count(),
                ],
                'attendance_trend' => $trend,
            ]
        ]);
    }

    /**
     * Parent/Guardian dashboard: list of children and their monthly attendance.
     */
    private function parentDashboard($user)
    {
        $guardian = Guardian::where('user_id', $user->id)->first();
        if (!$guardian) {
            return response()->json(['success' => false, 'message' => 'Guardian profile not found'], 403);
        }

        $children = $guardian->students()->with(['user', 'grade', 'section'])->get();

        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();

        $childrenData = [];
        foreach ($children as $child) {
            $attendances = Attendance::where('student_id', $child->id)
                ->whereBetween('date', [$monthStart, $monthEnd])
                ->get();

            $childrenData[] = [
                'id'           => $child->id,
                'name'         => $child->user->name,
                'admission_no' => $child->admission_number,
                'grade'        => $child->grade->name ?? 'N/A',
                'section'      => $child->section->name ?? 'N/A',
                'attendance'   => [
                    'present'    => $attendances->where('status', 'present')->count(),
                    'absent'     => $attendances->where('status', 'absent')->count(),
                    'late'       => $attendances->where('status', 'late')->count(),
                    'permission' => $attendances->where('status', 'permission')->count(),
                    'total'      => $attendances->count(),
                ]
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'guardian' => [
                    'name'  => $user->name,
                    'email' => $user->email,
                ],
                'children' => $childrenData,
            ]
        ]);
    }
}
