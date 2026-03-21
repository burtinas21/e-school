<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\GradeController;
use App\Http\Controllers\Api\SectionController;
use App\Http\Controllers\Api\SubjectController;
use App\Http\Controllers\Api\TeacherController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\GuardianController;
use App\Http\Controllers\Api\PeriodController;
use App\Http\Controllers\Api\TeacherAssignmentController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\CalendarEventController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ===========================================
// 🔓 PUBLIC ROUTES (No Authentication Required)
// ===========================================
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// 🔓 Grades - Anyone can view
Route::get('/grades', [GradeController::class, 'index']);
Route::get('/grades/active', [GradeController::class, 'active']);
Route::get('/grades/{id}', [GradeController::class, 'show']);
Route::get('/grades/{id}/sections', [GradeController::class, 'sections']);

// 🔓 Sections - Anyone can view
Route::get('/sections', [SectionController::class, 'index']);
Route::get('/sections/active', [SectionController::class, 'active']);
Route::get('/sections/{id}', [SectionController::class, 'show']);
Route::get('/sections/by-grade/{grade_id}', [SectionController::class, 'getByGrade']);

// 🔓 Subjects - Anyone can view
Route::get('/subjects', [SubjectController::class, 'index']);
Route::get('/subjects/{id}', [SubjectController::class, 'show']);
Route::get('/subjects/by-grade/{grade_id}', [SubjectController::class, 'getByGrade']);

// 🔓 Periods - Anyone can view
Route::get('/periods', [PeriodController::class, 'index']);
Route::get('/periods/classes', [PeriodController::class, 'classes']);
Route::get('/periods/breaks', [PeriodController::class, 'breaks']);
Route::get('/periods/{id}', [PeriodController::class, 'show']);

// ===========================================
// 🔒 PROTECTED ROUTES (Require Authentication)
// ===========================================
Route::middleware('auth:sanctum')->group(function () {

    // ========== AUTH ==========
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [UserController::class, 'profile']);
    Route::put('/profile', [UserController::class, 'updateProfile']);

    // ========== DASHBOARDS ==========
    Route::prefix('dashboard')->group(function () {
        Route::get('/teacher', [DashboardController::class, 'teacher']);
        Route::get('/student', [DashboardController::class, 'student']);
        Route::get('/admin', [DashboardController::class, 'admin']);
        Route::get('/parent', [DashboardController::class, 'parent']);
    });

    // ========== USERS ==========
    Route::apiResource('users', UserController::class);

    // ========== ROLES ==========
    Route::apiResource('roles', RoleController::class);

    // ========== GRADES (Protected operations) ==========
    Route::post('/grades', [GradeController::class, 'store']);
    Route::put('/grades/{id}', [GradeController::class, 'update']);
    Route::delete('/grades/{id}', [GradeController::class, 'destroy']);

    // ========== SECTIONS (Protected operations) ==========
    Route::post('/sections', [SectionController::class, 'store']);
    Route::put('/sections/{id}', [SectionController::class, 'update']);
    Route::delete('/sections/{id}', [SectionController::class, 'destroy']);

    // ========== SUBJECTS (Protected operations) ==========
    Route::post('/subjects', [SubjectController::class, 'store']);
    Route::put('/subjects/{id}', [SubjectController::class, 'update']);
    Route::delete('/subjects/{id}', [SubjectController::class, 'destroy']);

    // ========== TEACHERS ==========
    Route::apiResource('teachers', TeacherController::class);

    // ========== STUDENTS ==========
    Route::prefix('students')->group(function () {
        Route::get('/by-section/{grade_id}/{section_id}', [StudentController::class, 'getByGradeAndSection']);
    });
    Route::apiResource('students', StudentController::class);

    // ========== GUARDIANS ==========
    Route::prefix('guardians')->group(function () {
        Route::get('/{id}/children', [GuardianController::class, 'children']);
        Route::post('/{id}/link-student', [GuardianController::class, 'linkStudent']);
        Route::delete('/{guardianId}/unlink-student/{studentId}', [GuardianController::class, 'unlinkStudent']);
        Route::get('/{id}/notification-preferences', [GuardianController::class, 'notificationPreferences']);
        Route::put('/{id}/notification-preferences', [GuardianController::class, 'updateNotificationPreferences']);
    });
    Route::apiResource('guardians', GuardianController::class);

    // ========== PERIODS (Protected operations) ==========
    Route::post('/periods', [PeriodController::class, 'store']);
    Route::put('/periods/{id}', [PeriodController::class, 'update']);
    Route::delete('/periods/{id}', [PeriodController::class, 'destroy']);

    // ========== TEACHER ASSIGNMENTS ==========
    Route::prefix('teacher-assignments')->group(function () {
        Route::get('/by-teacher/{teacherId}', [TeacherAssignmentController::class, 'getByTeacher']);
        Route::get('/by-section/{sectionId}', [TeacherAssignmentController::class, 'getBySection']);
        Route::get('/by-subject/{subjectId}', [TeacherAssignmentController::class, 'getBySubject']);
        Route::get('/by-grade/{gradeId}', [TeacherAssignmentController::class, 'getByGrade']);
        Route::get('/primary/{sectionId}', [TeacherAssignmentController::class, 'getPrimaryTeachers']);
        Route::post('/bulk', [TeacherAssignmentController::class, 'bulkStore']);
    });
    Route::apiResource('teacher-assignments', TeacherAssignmentController::class);

    // ========== SCHEDULES ==========
    Route::prefix('schedules')->group(function () {
        Route::get('/section/{sectionId}', [ScheduleController::class, 'getSectionSchedule']);
        Route::get('/teacher/{teacherId}', [ScheduleController::class, 'getTeacherSchedule']);
        Route::get('/weekly/{sectionId}', [ScheduleController::class, 'weeklySchedule']);
        Route::get('/active/{sectionId}', [ScheduleController::class, 'getActiveSchedules']);
    });
    Route::apiResource('schedules', ScheduleController::class);

    // ========== ATTENDANCES ==========
    Route::prefix('attendances')->group(function () {
        Route::post('/class', [AttendanceController::class, 'getClassAttendance']);
        Route::post('/mark', [AttendanceController::class, 'markAttendance']);
        Route::get('/student/{studentId}/history', [AttendanceController::class, 'studentHistory']);
        Route::post('/report', [AttendanceController::class, 'report']);
    });
    Route::apiResource('attendances', AttendanceController::class)->except(['index', 'store']);

    // ========== NOTIFICATIONS ==========
    Route::prefix('notifications')->group(function () {
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
        Route::get('/student/{studentId}/history', [NotificationController::class, 'studentHistory']);
        Route::post('/bulk', [NotificationController::class, 'bulkCreate']);
    });
    Route::apiResource('notifications', NotificationController::class);

    // ========== CALENDAR EVENTS ==========
    Route::prefix('calendar')->group(function () {
        Route::get('/upcoming/{days?}', [CalendarEventController::class, 'upcoming']);
        Route::get('/month/{year}/{month}', [CalendarEventController::class, 'monthEvents']);
        Route::get('/check/{date}', [CalendarEventController::class, 'checkDate']);
    });
    Route::apiResource('calendar-events', CalendarEventController::class);
});
