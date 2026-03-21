<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use App\Models\Grade;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class StudentController extends Controller
{
    /**
     * Display a listing of students.
     */
    public function index()
    {
        $students = Student::with(['user', 'guardian', 'grade', 'section'])->get();

        return response()->json([
            'success' => true,
            'data' => $students
        ]);
    }

    /**
     * Store a newly created student.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:20',
            'guardian_id' => 'nullable|exists:guardians,id',
            'grade_id' => 'required|exists:grades,id',
            'section_id' => 'required|exists:sections,id',
            'admission_number' => 'required|string|unique:students',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Create user account
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'role_id' => 3, // Student role
            'is_active' => true,
        ]);

        // Create student profile
        $student = Student::create([
            'user_id' => $user->id,
            'guardian_id' => $request->guardian_id,
            'grade_id' => $request->grade_id,
            'section_id' => $request->section_id,
            'admission_number' => $request->admission_number,
            'date_of_birth' => $request->date_of_birth,
            'gender' => $request->gender,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Student created successfully',
            'data' => $student->load(['user', 'guardian', 'grade', 'section'])
        ], 201);
    }

    /**
     * Display the specified student.
     */
    public function show($id)
    {
        $student = Student::with(['user', 'guardian', 'grade', 'section'])->find($id);

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $student
        ]);
    }

    /**
     * Update the specified student.
     */
    public function update(Request $request, $id)
    {
        $student = Student::find($id);

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:20',
            'guardian_id' => 'nullable|exists:guardians,id',
            'grade_id' => 'sometimes|exists:grades,id',
            'section_id' => 'sometimes|exists:sections,id',
            'admission_number' => 'sometimes|string|unique:students,admission_number,' . $id,
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Update user if name provided
        if ($request->has('name')) {
            $user = $student->user;
            $user->name = $request->name;
            $user->save();
        }

        // Update student
        $student->update($request->only([
            'guardian_id', 'grade_id', 'section_id',
            'admission_number', 'date_of_birth', 'gender'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Student updated successfully',
            'data' => $student->load(['user', 'guardian', 'grade', 'section'])
        ]);
    }

    /**
     * Remove the specified student.
     */
    public function destroy($id)
    {
        $student = Student::find($id);

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found'
            ], 404);
        }

        // Delete user (will cascade to student)
        $student->user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Student deleted successfully'
        ]);
    }

    /**
     * Get students by grade and section.
     */
    public function getByGradeAndSection($grade_id, $section_id)
    {
        $students = Student::with('user')
            ->where('grade_id', $grade_id)
            ->where('section_id', $section_id)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $students
        ]);
    }
}
