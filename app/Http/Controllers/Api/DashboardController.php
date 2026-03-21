<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\User;

class DashboardController extends Controller
{
    /**
     * Get teacher dashboard data
     */
    public function teacher()
    {
        $user = Auth::user();
        $teacher = $user->teacher;

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'teacher' => [
                    'employee_id' => $teacher ? $teacher->employee_id : 'N/A',
                    'qualification' => $teacher ? $teacher->qualification : 'N/A',
                    'hire_date' => $teacher ? $teacher->hire_date : 'N/A',
                ]
            ]
        ]);
    }

    /**
     * Get student dashboard data
     */
    public function student()
    {
        $user = Auth::user();
        $student = $user->student;

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'student' => [
                    'admission_number' => $student ? $student->admission_number : 'N/A',
                    'grade' => $student && $student->grade ? $student->grade->name : 'N/A',
                    'section' => $student && $student->section ? $student->section->name : 'N/A',
                ]
            ]
        ]);
    }

    /**
     * Get admin dashboard data
     */
    public function admin()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'stats' => [
                    'teachers' => Teacher::count(),
                    'students' => Student::count(),
                    'users' => User::count(),
                ]
            ]
        ]);
    }

    /**
     * Get parent dashboard data
     */
    public function parent()
    {
        $user = Auth::user();
        $guardian = $user->guardian;

        $children = [];
        if ($guardian) {
            $children = $guardian->students()->with(['user', 'grade', 'section'])->get()->map(function($student) {
                return [
                    'id' => $student->id,
                    'name' => $student->user->name,
                    'admission_number' => $student->admission_number,
                    'grade' => $student->grade->name ?? 'N/A',
                    'section' => $student->section->name ?? 'N/A',
                ];
            });
        }

        return response()->json([
            'success' => true,
            'data' => [
                'guardian' => $guardian,
                'children' => $children
            ]
        ]);
    }
}
