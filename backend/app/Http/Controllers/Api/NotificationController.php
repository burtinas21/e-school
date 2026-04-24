<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    private function requireAuth()
    {
        if (!auth()->check()) {
            abort(response()->json(['success' => false, 'message' => 'Authentication required.'], 401));
        }
    }

    private function requireAdminOrTeacher()
    {
        $this->requireAuth();
        $user = auth()->user();
        if (!in_array($user->role_id, [1, 2])) {
            abort(response()->json(['success' => false, 'message' => 'Unauthorized.'], 403));
        }
    }

    private function requireAdmin()
    {
        $this->requireAuth();
        if (auth()->user()->role_id !== 1) {
            abort(response()->json(['success' => false, 'message' => 'Unauthorized. Admin access required.'], 403));
        }
    }

    /**
     * Get notifications for the authenticated user (guardian or student).
     */
    public function index(Request $request)
    {
        $this->requireAuth();
        $user = auth()->user();

        $query = Notification::with('student.user');

        // Determine recipient based on role
        if ($user->role_id === 4 && $user->guardian) { // Guardian
            $query->where('guardian_id', $user->guardian->id);
        } elseif ($user->role_id === 3 && $user->student) { // Student
            $query->where('student_id', $user->student->id);
        } elseif ($user->role_id === 2 && $user->teacher) { // Teacher
            $query->where('teacher_id', $user->teacher->id);
        } elseif ($user->role_id === 1) { // Admin
            // Admins can see all notifications
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to notifications'
            ], 403);
        }

        // Filter by read/unread (only for guardians)
        if ($user->guardian && $request->has('filter')) {
            if ($request->filter === 'unread') {
                $query->whereNull('read_at');
            } elseif ($request->filter === 'read') {
                $query->whereNotNull('read_at');
            }
        }

        $notifications = $query->orderBy('sent_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json(['success' => true, 'data' => $notifications]);
    }

    public function unreadCount()
    {
        $this->requireAuth();
        $user = auth()->user();

        // Unread count based on role
        if ($user->role_id === 4 && $user->guardian) {
            $count = Notification::where('guardian_id', $user->guardian->id)->whereNull('read_at')->count();
        } elseif ($user->role_id === 3 && $user->student) {
            $count = Notification::where('student_id', $user->student->id)->whereNull('read_at')->count();
        } elseif ($user->role_id === 2 && $user->teacher) {
            $count = Notification::where('teacher_id', $user->teacher->id)->whereNull('read_at')->count();
        } elseif ($user->role_id === 1) {
            $count = Notification::whereNull('read_at')->count();
        } else {
            $count = 0;
        }

        return response()->json([
            'success' => true,
            'data' => ['unread_count' => $count]
        ]);
    }


    /**
     * Mark a notification as read (guardians only).
     */
    public function markAsRead($id)
    {
        $this->requireAuth();
        $user = auth()->user();

        $query = Notification::where('id', $id);

        if ($user->role_id === 4 && $user->guardian) {
            $query->where('guardian_id', $user->guardian->id);
        } elseif ($user->role_id === 3 && $user->student) {
            $query->where('student_id', $user->student->id);
        } elseif ($user->role_id === 2 && $user->teacher) {
            $query->where('teacher_id', $user->teacher->id);
        }

        $notification = $query->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found'
            ], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read',
            'data' => $notification
        ]);
    }

    /**
     * Mark all notifications as read (guardians only).
     */
    public function markAllAsRead()
    {
        $this->requireAuth();
        $user = auth()->user();

        $query = Notification::whereNull('read_at');

        if ($user->role_id === 4 && $user->guardian) {
            $query->where('guardian_id', $user->guardian->id);
        } elseif ($user->role_id === 3 && $user->student) {
            $query->where('student_id', $user->student->id);
        } elseif ($user->role_id === 2 && $user->teacher) {
            $query->where('teacher_id', $user->teacher->id);
        }

        $query->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'All relevant notifications marked as read'
        ]);
    }

    /**
     * Create a notification (admin/teacher only).
     */
    public function store(Request $request)
    {
        $this->requireAdminOrTeacher();

        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:students,id',
            'type'       => 'required|in:absence,late,permission,daily_summary,warning,event',
            'title'      => 'required|string|max:255',
            'message'    => 'required|string',
            'status'     => 'sometimes|in:pending,sent,failed,read',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $student = Student::with('guardian')->find($request->student_id);

        if (!$student->guardian) {
            return response()->json([
                'success' => false,
                'message' => 'Student has no guardian assigned'
            ], 422);
        }

        $notification = Notification::create([
            'student_id'  => $request->student_id,
            'guardian_id' => $student->guardian->id,
            'type'        => $request->type,
            'title'       => $request->title,
            'message'     => $request->message,
            'sent_at'     => now(),
            'status'      => $request->status ?? 'sent',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Notification created successfully',
            'data'    => $notification->load(['student.user', 'guardian.user'])
        ], 201);
    }

    /**
     * Get notification history for a specific student (admin/teacher only).
     */
    public function studentHistory($student_id)
    {
        $this->requireAdminOrTeacher();

        $notifications = Notification::where('student_id', $student_id)
            ->with('guardian.user')
            ->orderBy('sent_at', 'desc')
            ->get();

        return response()->json(['success' => true, 'data' => $notifications]);
    }

    /**
     * Delete a notification (admin only).
     */
    public function destroy($id)
    {
        $this->requireAdmin();

        $notification = Notification::find($id);

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found'
            ], 404);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted successfully'
        ]);
    }

    /**
     * Bulk create notifications (admin/teacher only).
     * Supports section-based or broad broadcast.
     */
    public function bulkCreate(Request $request)
    {
        $this->requireAdminOrTeacher();

        $validator = Validator::make($request->all(), [
            'section_id' => 'sometimes|exists:sections,id',
            'type'       => 'required|in:absence,late,permission,daily_summary,warning,event',
            'title'      => 'required|string|max:255',
            'message'    => 'required|string',
            // Broadcast flags
            'send_to_all'       => 'sometimes|boolean',
            'send_to_students'  => 'sometimes|boolean',
            'send_to_guardians' => 'sometimes|boolean',
            'send_to_teachers'  => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $count = 0;

        // 1. Section-based
        if ($request->has('section_id')) {
            $students = Student::where('section_id', $request->section_id)->with('guardian')->get();
            foreach ($students as $student) {
                if ($student->guardian) {
                    $this->createNotification($student, $request);
                    $count++;
                }
            }
        } 
        // 2. Broad Broadcast
        else {
            $students = collect();
            
            if ($request->send_to_all || $request->send_to_students || $request->send_to_guardians) {
                $students = Student::with('guardian')->get();
            }

            foreach ($students as $student) {
                // If student or all
                if ($request->send_to_all || $request->send_to_students) {
                    // Note: System currently treats student notifications as something sent to their profile
                    // But usually notifications are for Guardians. 
                    // Let's ensure we hit the student record if needed.
                }

                if (($request->send_to_all || $request->send_to_guardians) && $student->guardian) {
                    $this->createNotification($student, $request);
                    $count++;
                }
            }

            // Teachers broadcast (if applicable)
            if ($request->send_to_all || $request->send_to_teachers) {
                $teachers = \App\Models\Teacher::all();
                foreach ($teachers as $teacher) {
                    Notification::create([
                        'teacher_id' => $teacher->id,
                        'type'       => $request->type,
                        'title'      => $request->title,
                        'message'    => $request->message,
                        'sent_at'    => now(),
                        'status'     => 'sent',
                    ]);
                    $count++;
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => "$count notifications created/sent successfully"
        ]);
    }

    private function createNotification($student, $request)
    {
        return Notification::create([
            'student_id'  => $student->id,
            'guardian_id' => $student->guardian->id ?? null,
            'type'        => $request->type,
            'title'       => $request->title,
            'message'     => $request->message,
            'sent_at'     => now(),
            'status'      => 'sent',
        ]);
    }
}
