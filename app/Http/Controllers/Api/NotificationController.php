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

        // Determine recipient
        if ($user->guardian) {
            $query->where('guardian_id', $user->guardian->id);
        } elseif ($user->student) {
            $query->where('student_id', $user->student->id);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Only guardians and students can access notifications'
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

    /**
     * Get unread count (guardians only).
     */
    public function unreadCount()
    {
        $this->requireAuth();
        $user = auth()->user();

        if (!$user->guardian) {
            return response()->json([
                'success' => false,
                'message' => 'Only guardians can access unread count'
            ], 403);
        }

        $count = Notification::where('guardian_id', $user->guardian->id)
            ->whereNull('read_at')
            ->count();

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

        if (!$user->guardian) {
            return response()->json([
                'success' => false,
                'message' => 'Only guardians can mark notifications as read'
            ], 403);
        }

        $notification = Notification::where('id', $id)
            ->where('guardian_id', $user->guardian->id)
            ->first();

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

        if (!$user->guardian) {
            return response()->json([
                'success' => false,
                'message' => 'Only guardians can mark notifications as read'
            ], 403);
        }

        Notification::where('guardian_id', $user->guardian->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read'
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
     * Bulk create notifications for a section (admin/teacher only).
     */
    public function bulkCreate(Request $request)
    {
        $this->requireAdminOrTeacher();

        $validator = Validator::make($request->all(), [
            'section_id' => 'required|exists:sections,id',
            'type'       => 'required|in:absence,late,permission,daily_summary,warning,event',
            'title'      => 'required|string|max:255',
            'message'    => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $students = Student::where('section_id', $request->section_id)
            ->with('guardian')
            ->get();

        $count = 0;
        foreach ($students as $student) {
            if ($student->guardian) {
                Notification::create([
                    'student_id'  => $student->id,
                    'guardian_id' => $student->guardian->id,
                    'type'        => $request->type,
                    'title'       => $request->title,
                    'message'     => $request->message,
                    'sent_at'     => now(),
                    'status'      => 'sent',
                ]);
                $count++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "$count notifications created successfully"
        ]);
    }
}
