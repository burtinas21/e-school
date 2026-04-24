<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class TeacherController extends Controller
{
    /**
     * Display a listing of teachers.
     */
    public function index()
    {
        $teachers = Teacher::with('user')->get();

        return response()->json([
            'success' => true,
            'data' => $teachers
        ]);
    }

    /**
     * Store a newly created teacher.
     */
    public function store(Request $request)
    {
    if (!auth()->check()) {
       return response()->json(['success' => false, 'message' => 'Authentication required.'
       ], 401);
       }
    if (auth()->user()->role_id !== 1) {
       return response()->json(['success' => false, 'message' => 'Unauthorized. Admin access required.'
       ], 403);
       }
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:20',
            'employee_id' => 'required|string|unique:teachers',
            'qualification' => 'required|string',
            'hire_date' => 'nullable|date',
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
            'role_id' => 2, // Teacher role
            'is_active' => true,
        ]);

        // Create teacher profile
        $teacher = Teacher::create([
            'user_id' => $user->id,
            'employee_id' => $request->employee_id,
            'qualification' => $request->qualification,
            'hire_date' => $request->hire_date,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Teacher created successfully',
            'data' => $teacher->load('user')
        ], 201);
    }

    /**
     * Display the specified teacher.
     */
    public function show($id)
    {
        $teacher = Teacher::with('user')->find($id);

        if (!$teacher) {
            return response()->json([
                'success' => false,
                'message' => 'Teacher not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $teacher
        ]);
    }

    /**
     * Update the specified teacher.
     */
    public function update(Request $request, $id)
    {

    if (!auth()->check()) {
       return response()->json(['success' => false, 'message' => 'Authentication required.'
       ], 401);
       }
    if (auth()->user()->role_id !== 1) {
       return response()->json(['success' => false, 'message' => 'Unauthorized. Admin access required.'
       ], 403);
       }
        $teacher = Teacher::find($id);

        if (!$teacher) {
            return response()->json([
                'success' => false,
                'message' => 'Teacher not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:20',
            'employee_id' => 'sometimes|string|unique:teachers,employee_id,' . $id,
            'qualification' => 'sometimes|string',
            'hire_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Update user if name or phone provided
        if ($request->has('name') || $request->has('phone')) {
            $user = $teacher->user;
            if ($request->has('name')) $user->name = $request->name;
            if ($request->has('phone')) $user->phone = $request->phone;
            $user->save();
        }

        // Update teacher
        $teacher->update($request->only([
            'employee_id', 'qualification', 'hire_date'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Teacher updated successfully',
            'data' => $teacher->load('user')
        ]);
    }

    /**
     * Remove the specified teacher.
     */
    public function destroy($id)
    {

    if (!auth()->check()) {
       return response()->json(['success' => false, 'message' => 'Authentication required.'
       ], 401);
       }
    if (auth()->user()->role_id !== 1) {
       return response()->json(['success' => false, 'message' => 'Unauthorized. Admin access required.'
       ], 403);
       }
        $teacher = Teacher::find($id);

        if (!$teacher) {
            return response()->json([
                'success' => false,
                'message' => 'Teacher not found'
            ], 404);
        }

        // Delete user (will cascade to teacher)
        $teacher->user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Teacher deleted successfully'
        ]);
    }
}
