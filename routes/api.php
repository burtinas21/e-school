<?php

use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CalendarEventController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\GradeController;
use App\Http\Controllers\Api\GuardianController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PeriodController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\SectionController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\SubjectController;
use App\Http\Controllers\Api\TeacherAssignmentController;
use App\Http\Controllers\Api\TeacherController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

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
/**
 * public routes for teacher(no auth)
 */
Route::get('/teachers', [TeacherController::class, 'index']);
Route::get('/teachers/{id}', [TeacherController::class, 'show']);
/**
 * === == public TeacherAssignment (without Auth)====
 */
        /** ========
         *  Public Student routes (without Auth)
         */

       // Route::get('/students', [StudentController::class, 'index']);
      //  Route::get('/students/{id}', [StudentController::class, 'show']);  not anyone can view students list only authorized users can see

// ===========================================
// 🔒 PROTECTED ROUTES (Require Authentication)
// ===========================================
Route::middleware('auth:sanctum')->group(function () {

    // ========== AUTH ==========
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [UserController::class, 'profile']);
    Route::put('/profile', [UserController::class, 'updateProfile']);

    // ========== DASHBOARD ==========
    Route::get('dashboard', [DashboardController::class, 'index']);

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

   Route::middleware('auth:sanctum')->group(function ()
   {
    Route::post('/teachers', [TeacherController::class, 'store']);
    Route::put('/teachers/{id}', [TeacherController::class, 'update']);
    Route::delete('/teachers/{id}', [TeacherController::class, 'destroy']);
   // Route::apiResource('teachers', TeacherController::class);
   });


    // ========== STUDENTS ==========
    Route::prefix('students')->group(function () {
        Route::get('/by-section/{grade_id}/{section_id}',
           [StudentController::class,
        'getByGradeAndSection']);
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
/**
 * who can see teacheassignments admin, student, teachers,and parents
 * since sschool informations must be prtected means, not public on schools websites
 */
    Route::prefix('teacher-assignments')->group(function () {
        Route::get('/', [TeacherAssignmentController::class, 'index']);
        Route::get('{id}', [TeacherAssignmentController::class, 'show']);
        Route::get('by-teacher/{teacher}', [TeacherAssignmentController::class, 'getByTeacher']);
        Route::get('by-section/{section}', [TeacherAssignmentController::class, 'getBySection']);
        Route::get('by-subject/{subject}', [TeacherAssignmentController::class, 'getBySubject']);
        Route::get('by-grade/{grade}', [TeacherAssignmentController::class, 'getByGrade']);

        /**
         * admin only - controller will enforce  roles===
         */
        Route::post('/', [TeacherAssignmentController::class, 'store']);
        Route::put('{id}', [TeacherAssignmentController::class, 'update']);
        Route::delete('{id}', [TeacherAssignmentController::class, 'destroy']);
        Route::post('bulk', [TeacherAssignmentController::class, 'bulkStore']);
        Route::post('/bulk', [TeacherAssignmentController::class, 'bulkStore']);
    });
    Route::apiResource('teacher-assignments', TeacherAssignmentController::class);

    // ========== SCHEDULES ==========
    Route::prefix('schedules')->group(function () {
        Route::get('section/{sectionId}', [ScheduleController::class, 'getSectionSchedule']);
        Route::get('teacher/{teacherId}', [ScheduleController::class, 'getTeacherSchedule']);
        Route::get('weekly/{sectionId}', [ScheduleController::class, 'weeklySchedule']);
        Route::get('active/{sectionId}', [ScheduleController::class, 'getActiveSchedules']);

       /**
        * admin only===
        */
        Route::post('/', [ScheduleController::class, 'store']);
        Route::put('{id}', [ScheduleController::class, 'update']);
        Route::delete('{id}', [ScheduleController::class, 'destroy']);
    });
    //Route::apiResource('schedules', ScheduleController::class);

    // ========== ATTENDANCES ==========
    Route::prefix('attendances')->group(function () {
        Route::post('/class', [AttendanceController::class, 'getClassAttendance']);
        Route::post('/mark', [AttendanceController::class, 'markAttendance']);
        Route::get('/student/{studentId}/history', [AttendanceController::class, 'studentHistory']);
        Route::post('/report', [AttendanceController::class, 'report']);
    });
   // Route::apiResource('attendances', AttendanceController::class)->except(['index', 'store']);

    // ========== NOTIFICATIONS ==========
    Route::prefix('notifications')->group(function () {
    // Core CRUD
        Route::get('/', [NotificationController::class, 'index']);
        Route::post('/', [NotificationController::class, 'store']);
        Route::delete('{id}', [NotificationController::class, 'destroy']);

    // Guardian-specific (read/unread)
        Route::get('unread-count', [NotificationController::class, 'unreadCount']);
        Route::put('{id}/read', [NotificationController::class, 'markAsRead']);
        Route::put('read-all', [NotificationController::class, 'markAllAsRead']);

    // Admin/Teacher endpoints
        Route::get('student/{student_id}', [NotificationController::class, 'studentHistory']);
        Route::post('bulk', [NotificationController::class, 'bulkCreate']);
});
   // Route::apiResource('notifications', NotificationController::class);

    // ========== CALENDAR EVENTS ==========
    Route::prefix('calendar-events')->group(function () {
        // Specialized endpoints first to avoid collision with resource id route
        Route::get('/check-date/{date}', [CalendarEventController::class, 'checkDate']);
        Route::get('/upcoming', [CalendarEventController::class, 'upcoming']);
        Route::get('/upcoming/{days}', [CalendarEventController::class, 'upcoming']);
        Route::get('/month/{year}/{month}', [CalendarEventController::class, 'monthEvents']);

        Route::get('/', [CalendarEventController::class, 'index']);
        Route::post('/', [CalendarEventController::class, 'store']);
        Route::get('{id}', [CalendarEventController::class, 'show'])->whereNumber('id');
        Route::put('{id}', [CalendarEventController::class, 'update'])->whereNumber('id');
        Route::delete('{id}', [CalendarEventController::class, 'destroy'])->whereNumber('id');
    });
   // Route::apiResource('calendar-events', CalendarEventController::class);

   /** The Report Sytem Routes */
   //====== REPORT SYSTEM ROUTES=====
   Route::prefix('reports')->group(function () {
        Route::get('attendance', [ReportController::class, 'attendance']);
   });

   /**
    * === SETTING ROUTES======
    */

   Route::prefix('settings')->group(function ()
   {
    Route::get('/', [SettingController::class, 'index']);
    Route::get('{key}',  [SettingController::class, 'show']);
    Route::put('/', [SettingController::class, 'update']);

   });
});
