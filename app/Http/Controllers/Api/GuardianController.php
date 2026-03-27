<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guardian;
use App\Models\User;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class GuardianController extends Controller
{
    /**
     * Display a listing of guardians (role‑based).
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

        $query = Guardian::with(['user', 'students.user']);

        // Apply role‑based filtering
        if ($user->role_id == 1) { // Admin
            // no additional filter
        } elseif ($user->role_id == 2) { // Teacher
            $teacher = Teacher::where('user_id', $user->id)->first();
            if ($teacher) {
                // Get all section IDs the teacher teaches
                $sectionIds = TeacherAssignment::where('teacher_id', $teacher->id)
                    ->pluck('section_id')
                    ->unique();
                // Get guardian IDs of students in those sections
                $guardianIds = Student::whereIn('section_id', $sectionIds)
                    ->whereNotNull('guardian_id')
                    ->pluck('guardian_id')
                    ->unique();
                $query->whereIn('id', $guardianIds);
            } else {
                $query->whereRaw('0'); // no assignments → no guardians
            }
        } elseif ($user->role_id == 3) { // Student
            // Students cannot see any guardians (privacy)
            $query->whereRaw('0');
        } elseif ($user->role_id == 4) { // Parent
            $guardian = Guardian::where('user_id', $user->id)->first();
            if ($guardian) {
                $query->where('id', $guardian->id);
            } else {
                $query->whereRaw('0');
            }
        } else {
            $query->whereRaw('0');
        }

        // Additional filters
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        if ($request->has('receive_notifications')) {
            $query->where('receive_notifications', $request->receive_notifications);
        }

        $guardians = $query->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $guardians
        ]);
    }

    /**
     * Store a newly created guardian (admin only).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
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
            'occupation' => 'nullable|string|max:255',
            'relationship' => 'nullable|string|max:50',
            'receive_notifications' => 'sometimes|boolean',
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
            'role_id' => 4, // Guardian role
            'is_active' => true,
        ]);

        // Create guardian profile
        $guardian = Guardian::create([
            'user_id' => $user->id,
            'occupation' => $request->occupation,
            'relationship' => $request->relationship,
            'receive_notifications' => $request->receive_notifications ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Guardian created successfully',
            'data' => $guardian->load('user')
        ], 201);
    }

    /**
     * Display the specified guardian (role‑based).
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $guardian = Guardian::with(['user', 'students.user', 'notifications'])->find($id);

        if (!$guardian) {
            return response()->json(['success' => false, 'message' => 'Guardian not found'], 404);
        }

        $allowed = false;

        if ($user->role_id == 1) { // Admin
            $allowed = true;
        } elseif ($user->role_id == 2) { // Teacher
            $teacher = Teacher::where('user_id', $user->id)->first();
            if ($teacher) {
                // Get students linked to this guardian
                $studentIds = $guardian->students()->pluck('id');
                // Check if teacher teaches any of those students' sections
                $allowed = TeacherAssignment::where('teacher_id', $teacher->id)
                    ->whereIn('section_id', function($query) use ($studentIds) {
                        $query->select('section_id')
                              ->from('students')
                              ->whereIn('id', $studentIds);
                    })
                    ->exists();
            }
        } elseif ($user->role_id == 3) { // Student
            $allowed = false; // students cannot see guardians
        } elseif ($user->role_id == 4) { // Parent
            $guardianUser = Guardian::where('user_id', $user->id)->first();
            if ($guardianUser && $guardianUser->id == $guardian->id) {
                $allowed = true;
            }
        }

        if (!$allowed) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $guardian
        ]);
    }

    /**
     * Update the specified guardian (admin only).
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        if (!auth()->check()) {
            return response()->json(['success' => false, 'message' => 'Authentication required.'], 401);
        }
        if (auth()->user()->role_id !== 1) {
            return response()->json(['success' => false, 'message' => 'Unauthorized. Admin access required.'], 403);
        }

        $guardian = Guardian::find($id);

        if (!$guardian) {
            return response()->json([
                'success' => false,
                'message' => 'Guardian not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:20',
            'occupation' => 'nullable|string|max:255',
            'relationship' => 'nullable|string|max:50',
            'receive_notifications' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Update user if name or phone provided
        if ($request->has('name') || $request->has('phone')) {
            $user = $guardian->user;
            if ($request->has('name')) $user->name = $request->name;
            if ($request->has('phone')) $user->phone = $request->phone;
            $user->save();
        }

        // Update guardian
        $updateData = [];
        if ($request->has('occupation')) $updateData['occupation'] = $request->occupation;
        if ($request->has('relationship')) $updateData['relationship'] = $request->relationship;
        if ($request->has('receive_notifications')) $updateData['receive_notifications'] = $request->receive_notifications;

        if (!empty($updateData)) {
            $guardian->update($updateData);
        }

        return response()->json([
            'success' => true,
            'message' => 'Guardian updated successfully',
            'data' => $guardian->load('user')
        ]);
    }

    /**
     * Remove the specified guardian (admin only).
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        if (!auth()->check()) {
            return response()->json(['success' => false, 'message' => 'Authentication required.'], 401);
        }
        if (auth()->user()->role_id !== 1) {
            return response()->json(['success' => false, 'message' => 'Unauthorized. Admin access required.'], 403);
        }

        $guardian = Guardian::find($id);

        if (!$guardian) {
            return response()->json([
                'success' => false,
                'message' => 'Guardian not found'
            ], 404);
        }

        // Check if guardian has students
        if ($guardian->students()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete guardian because they have linked students'
            ], 422);
        }

        // Delete user (will cascade to guardian)
        $guardian->user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Guardian deleted successfully'
        ]);
    }

    /**
     * Get children (students) for a guardian (role‑based).
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function children($id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $guardian = Guardian::find($id);

        if (!$guardian) {
            return response()->json([
                'success' => false,
                'message' => 'Guardian not found'
            ], 404);
        }

        $allowed = false;

        if ($user->role_id == 1) { // Admin
            $allowed = true;
        } elseif ($user->role_id == 2) { // Teacher
            $teacher = Teacher::where('user_id', $user->id)->first();
            if ($teacher) {
                $studentIds = $guardian->students()->pluck('id');
                $allowed = TeacherAssignment::where('teacher_id', $teacher->id)
                    ->whereIn('section_id', function($query) use ($studentIds) {
                        $query->select('section_id')
                              ->from('students')
                              ->whereIn('id', $studentIds);
                    })
                    ->exists();
            }
        } elseif ($user->role_id == 4) { // Parent
            $guardianUser = Guardian::where('user_id', $user->id)->first();
            if ($guardianUser && $guardianUser->id == $guardian->id) {
                $allowed = true;
            }
        } else {
            $allowed = false;
        }

        if (!$allowed) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $children = $guardian->students()->with(['user', 'grade', 'section'])->get();

        return response()->json([
            'success' => true,
            'data' => $children
        ]);
    }

    /**
     * Link a student to guardian (admin only).
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function linkStudent(Request $request, $id)
    {
        if (!auth()->check()) {
            return response()->json(['success' => false, 'message' => 'Authentication required.'], 401);
        }
        if (auth()->user()->role_id !== 1) {
            return response()->json(['success' => false, 'message' => 'Unauthorized. Admin access required.'], 403);
        }

        $guardian = Guardian::find($id);

        if (!$guardian) {
            return response()->json([
                'success' => false,
                'message' => 'Guardian not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:students,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $student = Student::find($request->student_id);
        $student->guardian_id = $guardian->id;
        $student->save();

        return response()->json([
            'success' => true,
            'message' => 'Student linked to guardian successfully',
            'data' => $guardian->load('students.user')
        ]);
    }

    /**
     * Unlink a student from guardian (admin only).
     *
     * @param int $guardianId
     * @param int $studentId
     * @return \Illuminate\Http\JsonResponse
     */
    public function unlinkStudent($guardianId, $studentId)
    {
        if (!auth()->check()) {
            return response()->json(['success' => false, 'message' => 'Authentication required.'], 401);
        }
        if (auth()->user()->role_id !== 1) {
            return response()->json(['success' => false, 'message' => 'Unauthorized. Admin access required.'], 403);
        }

        $guardian = Guardian::find($guardianId);

        if (!$guardian) {
            return response()->json([
                'success' => false,
                'message' => 'Guardian not found'
            ], 404);
        }

        $student = Student::where('id', $studentId)
            ->where('guardian_id', $guardianId)
            ->first();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student not linked to this guardian'
            ], 404);
        }

        $student->guardian_id = null;
        $student->save();

        return response()->json([
            'success' => true,
            'message' => 'Student unlinked from guardian successfully'
        ]);
    }

    /**
     * Get notification preferences for guardian (role‑based).
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function notificationPreferences($id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $guardian = Guardian::find($id);

        if (!$guardian) {
            return response()->json([
                'success' => false,
                'message' => 'Guardian not found'
            ], 404);
        }

        $allowed = false;

        if ($user->role_id == 1) { // Admin
            $allowed = true;
        } elseif ($user->role_id == 4) { // Parent
            $guardianUser = Guardian::where('user_id', $user->id)->first();
            if ($guardianUser && $guardianUser->id == $guardian->id) {
                $allowed = true;
            }
        }

        if (!$allowed) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'receive_notifications' => $guardian->receive_notifications,
                'unread_count' => $guardian->unread_notifications_count,
            ]
        ]);
    }

    /**
     * Update notification preferences (admin or self).
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateNotificationPreferences(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $guardian = Guardian::find($id);

        if (!$guardian) {
            return response()->json([
                'success' => false,
                'message' => 'Guardian not found'
            ], 404);
        }

        $allowed = false;
        if ($user->role_id == 1) {
            $allowed = true;
        } elseif ($user->role_id == 4) {
            $guardianUser = Guardian::where('user_id', $user->id)->first();
            if ($guardianUser && $guardianUser->id == $guardian->id) {
                $allowed = true;
            }
        }

        if (!$allowed) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'receive_notifications' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $guardian->receive_notifications = $request->receive_notifications;
        $guardian->save();

        return response()->json([
            'success' => true,
            'message' => 'Notification preferences updated successfully',
            'data' => [
                'receive_notifications' => $guardian->receive_notifications,
            ]
        ]);
    }
}
