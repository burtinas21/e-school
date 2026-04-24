<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use App\Models\Grade;
use App\Models\Section;
use App\Models\Teacher;
use App\Models\Guardian;
use App\Models\TeacherAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class StudentController extends Controller
{
    /**
     * Display a listing of students (role‑based).
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $query = Student::with(['user', 'guardian', 'grade', 'section']);

        // Role‑based filtering
        if ($user->role_id == 1) { // Admin
            // see all
        } elseif ($user->role_id == 2) { // Teacher
            $teacher = Teacher::where('user_id', $user->id)->first();
            if ($teacher) {
                $sectionIds = TeacherAssignment::where('teacher_id', $teacher->id)
                    ->pluck('section_id')
                    ->unique();
                $query->whereIn('section_id', $sectionIds);
            } else {
                $query->whereRaw('0'); // no assignments -> no students
            }
        } elseif ($user->role_id == 3) { // Student
            $student = Student::where('user_id', $user->id)->first();
            if ($student) {
                $query->where('id', $student->id);
            } else {
                $query->whereRaw('0');
            }
        } elseif ($user->role_id == 4) { // Parent (Guardian)
            $guardian = Guardian::where('user_id', $user->id)->first();
            if ($guardian) {
                $query->where('guardian_id', $guardian->id);
            } else {
                $query->whereRaw('0');
            }
        } else {
            $query->whereRaw('0');
        }

        // Optional request filters
        if ($request->has('grade_id')) {
            $query->where('grade_id', $request->grade_id);
        }
        if ($request->has('section_id')) {
            $query->where('section_id', $request->section_id);
        }

        $students = $query->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $students
        ]);
    }

    /**
     * Display the specified student (role‑based).
     */
    public function show($id)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $student = Student::with(['user', 'guardian', 'grade', 'section'])->find($id);

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
        } elseif ($user->role_id == 4) { // Parent (Guardian)
            $guardian = Guardian::where('user_id', $user->id)->first();
            if ($guardian && $guardian->id == $student->guardian_id) {
                $allowed = true;
            }
        }

        if (!$allowed) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $student
        ]);
    }

    /**
     * Store a newly created student (admin only).
     */
    public function store(Request $request)
    {
        if (!auth()->check()) {
            return response()->json(['success' => false, 'message' => 'Authentication required.'], 401);
        }
        if (auth()->user()->role_id !== 1) {
            return response()->json(['success' => false, 'message' => 'Unauthorized. Admin access required.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:20',
            'guardian_id' => 'nullable|exists:guardians,id',
            'grade_id' => 'required|exists:grades,id',
            'section_id' => 'required|exists:sections,id',
            'admission_number' => 'required|string|unique:students', // column name: admission_number
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
            'role_id' => 3,
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
     * Update the specified student (admin only).
     */
    public function update(Request $request, $id)
    {
        if (!auth()->check()) {
            return response()->json(['success' => false, 'message' => 'Authentication required.'], 401);
        }
        if (auth()->user()->role_id !== 1) {
            return response()->json(['success' => false, 'message' => 'Unauthorized. Admin access required.'], 403);
        }

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
     * Remove the specified student (admin only).
     */
    public function destroy($id)
    {
        if (!auth()->check()) {
            return response()->json(['success' => false, 'message' => 'Authentication required.'], 401);
        }
        if (auth()->user()->role_id !== 1) {
            return response()->json(['success' => false, 'message' => 'Unauthorized. Admin access required.'], 403);
        }

        $student = Student::find($id);

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found'
            ], 404);
        }

        $student->user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Student deleted successfully'
        ]);
    }

    /**
     * Get students by grade and section (role‑based).
     */
    public function getByGradeAndSection($grade_id, $section_id)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $allowed = false;
        if ($user->role_id == 1) { // Admin
            $allowed = true;
        } elseif ($user->role_id == 2) { // Teacher
            $teacher = Teacher::where('user_id', $user->id)->first();
            if ($teacher) {
                $allowed = TeacherAssignment::where('teacher_id', $teacher->id)
                    ->where('section_id', $section_id)
                    ->exists();
            }
        } else {
            // Students and parents cannot use this endpoint
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if (!$allowed) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

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
