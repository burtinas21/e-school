<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Student;
use App\Models\Guardian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    /**
     * Get notifications for the authenticated guardian.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $guardian = $user->guardian;

        if (!$guardian) {
            return response()->json([
                'success' => false,
                'message' => 'Only guardians can access notifications'
            ], 403);
        }

        $query = Notification::where('guardian_id', $guardian->id)
            ->with('student.user');

        // Filter by read/unread
        if ($request->has('filter')) {
            if ($request->filter === 'unread') {
                $query->whereNull('read_at');
            } elseif ($request->filter === 'read') {
                $query->whereNotNull('read_at');
            }
        }

        $notifications = $query->orderBy('sent_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $notifications
        ]);
    }

    /**
     * Get unread count for guardian.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function unreadCount()
    {
        $user = Auth::user();
        $guardian = $user->guardian;

        if (!$guardian) {
            return response()->json([
                'success' => false,
                'message' => 'Only guardians can access notifications'
            ], 403);
        }

        $count = Notification::where('guardian_id', $guardian->id)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'success' => true,
            'data' => ['unread_count' => $count]
        ]);
    }

    /**
     * Mark a notification as read.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsRead($id)
    {
        $user = Auth::user();
        $guardian = $user->guardian;

        $notification = Notification::where('id', $id)
            ->where('guardian_id', $guardian->id)
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
     * Mark all notifications as read for the guardian.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAllAsRead()
    {
        $user = Auth::user();
        $guardian = $user->guardian;

        Notification::where('guardian_id', $guardian->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read'
        ]);
    }

    /**
     * Create a new notification (admin/teacher only).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        // Only admin and teachers can create notifications
        if (!in_array($user->role_id, [1, 2])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to create notifications'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:students,id',
            'type' => 'required|in:absence,late,permission,daily_summary,warning,event',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'status' => 'sometimes|in:pending,sent,failed,read',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $student = Student::with('guardian')->find($request->student_id);

        if (!$student->guardian) {
            return response()->json([
                'success' => false,
                'message' => 'Student has no guardian assigned'
            ], 422);
        }

        $notification = Notification::create([
            'student_id' => $request->student_id,
            'guardian_id' => $student->guardian->id,
            'type' => $request->type,
            'title' => $request->title,
            'message' => $request->message,
            'sent_at' => now(),
            'status' => $request->status ?? 'sent',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Notification created successfully',
            'data' => $notification->load(['student.user', 'guardian.user'])
        ], 201);
    }

    /**
     * Get notification history for a specific student (admin/teacher only).
     *
     * @param int $student_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function studentHistory($student_id)
    {
        $user = Auth::user();

        if (!in_array($user->role_id, [1, 2])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $notifications = Notification::where('student_id', $student_id)
            ->with('guardian.user')
            ->orderBy('sent_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $notifications
        ]);
    }

    /**
     * Delete a notification (admin only).
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $user = Auth::user();

        if ($user->role_id !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

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
     * Create bulk notifications (e.g., for all students in a section).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkCreate(Request $request)
    {
        $user = Auth::user();

        if (!in_array($user->role_id, [1, 2])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'section_id' => 'required|exists:sections,id',
            'type' => 'required|in:absence,late,permission,daily_summary,warning,event',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $students = Student::where('section_id', $request->section_id)
            ->with('guardian')
            ->get();

        $count = 0;
        foreach ($students as $student) {
            if ($student->guardian) {
                Notification::create([
                    'student_id' => $student->id,
                    'guardian_id' => $student->guardian->id,
                    'type' => $request->type,
                    'title' => $request->title,
                    'message' => $request->message,
                    'sent_at' => now(),
                    'status' => 'sent',
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
