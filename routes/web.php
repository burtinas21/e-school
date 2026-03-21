<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'success' => true,
        'message' => 'E-School Attendance API',
        'version' => '1.0.0',
        'environment' => app()->environment(),
        'documentation' => [
            'info' => 'This is a RESTful API for the E-School Attendance System',
            'base_url' => url('/api'),
            'authentication' => 'Bearer token (obtained from /api/login)',
            'rate_limits' => '60 requests per minute',
        ],
        'endpoints' => [
            'auth' => [
                'POST /api/login' => 'Authenticate user and get token',
                'POST /api/register' => 'Register new user',
                'POST /api/logout' => 'Revoke token (requires auth)',
                'GET /api/profile' => 'Get authenticated user profile',
            ],
            'dashboards' => [
                'GET /api/dashboard/admin' => 'Admin statistics',
                'GET /api/dashboard/teacher' => 'Teacher dashboard data',
                'GET /api/dashboard/student' => 'Student dashboard data',
                'GET /api/dashboard/parent' => 'Parent dashboard data',
            ],
            'core_resources' => [
                'GET /api/users' => 'List all users',
                'GET /api/roles' => 'List all roles',
                'GET /api/grades' => 'List all grades',
                'GET /api/sections' => 'List all sections',
                'GET /api/subjects' => 'List all subjects',
                'GET /api/teachers' => 'List all teachers',
                'GET /api/students' => 'List all students',
                'GET /api/guardians' => 'List all guardians',
                'GET /api/periods' => 'List all periods',
            ],
            'attendance' => [
                'POST /api/attendances/class' => 'Get attendance for a class',
                'POST /api/attendances/mark' => 'Mark attendance',
                'GET /api/attendances/student/{id}/history' => 'Get student attendance history',
                'POST /api/attendances/report' => 'Generate attendance report',
            ],
            'schedules' => [
                'GET /api/schedules/section/{id}' => 'Get section schedule',
                'GET /api/schedules/teacher/{id}' => 'Get teacher schedule',
                'GET /api/schedules/weekly/{id}' => 'Get weekly schedule grid',
            ],
            'notifications' => [
                'GET /api/notifications' => 'Get guardian notifications',
                'GET /api/notifications/unread-count' => 'Get unread count',
                'POST /api/notifications/{id}/read' => 'Mark as read',
            ],
            'calendar' => [
                'GET /api/calendar/upcoming/{days?}' => 'Get upcoming events',
                'GET /api/calendar/month/{year}/{month}' => 'Get month events',
                'GET /api/calendar/check/{date}' => 'Check if date is holiday',
            ],
        ],
        'links' => [
            'login' => url('/api/login'),
            'register' => url('/api/register'),
            'postman_collection' => url('/postman.json'), // Optional
            'github' => 'https://github.com/your-repo', // Optional
        ],
        'timestamp' => now()->toIso8601String(),
    ]);
});
