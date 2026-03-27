<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Response;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Ensure the user is authenticated.
     */
    private function requireAuth(): void
    {
        if (!auth()->check()) {
            abort(response()->json(['success' => false, 'message' => 'Authentication required.'], 401));
        }
    }

    /**
     * Ensure the user is admin or teacher.
     */
    private function requireAdminOrTeacher(): void
    {
        $this->requireAuth();
        $user = auth()->user();
        if (!in_array($user->role_id, [1, 2])) {
            abort(response()->json(['success' => false, 'message' => 'Unauthorized.'], 403));
        }
    }

    /**
     * Helper: resolve a human‑readable time range into actual dates.
     * Supported: 'today', 'yesterday', 'this_week', 'last_week', 'this_month',
     *            'last_month', 'this_semester', 'last_semester', 'this_year',
     *            'last_year'. Default: custom dates (must be provided).
     */
    private function resolveDateRange(Request $request): array
    {
        $range = $request->input('range');

        switch ($range) {
            case 'today':
                $start = Carbon::today();
                $end = Carbon::today();
                break;
            case 'yesterday':
                $start = Carbon::yesterday();
                $end = Carbon::yesterday();
                break;
            case 'this_week':
                $start = Carbon::now()->startOfWeek();
                $end = Carbon::now()->endOfWeek();
                break;
            case 'last_week':
                $start = Carbon::now()->subWeek()->startOfWeek();
                $end = Carbon::now()->subWeek()->endOfWeek();
                break;
            case 'this_month':
                $start = Carbon::now()->startOfMonth();
                $end = Carbon::now()->endOfMonth();
                break;
            case 'last_month':
                $start = Carbon::now()->subMonth()->startOfMonth();
                $end = Carbon::now()->subMonth()->endOfMonth();
                break;
            case 'this_semester':
                $semester = $this->getCurrentSemester();
                $start = $semester['start'];
                $end = $semester['end'];
                break;
            case 'last_semester':
                $semester = $this->getPreviousSemester();
                $start = $semester['start'];
                $end = $semester['end'];
                break;
            case 'this_year':
                $start = Carbon::now()->startOfYear();
                $end = Carbon::now()->endOfYear();
                break;
            case 'last_year':
                $start = Carbon::now()->subYear()->startOfYear();
                $end = Carbon::now()->subYear()->endOfYear();
                break;
            default:
                // custom dates: must be provided
                $start = Carbon::parse($request->start_date);
                $end = Carbon::parse($request->end_date);
                break;
        }

        return [$start, $end];
    }

    /**
     * Get the current semester boundaries.
     * Assume academic year starts in September and runs to August.
     * Semester 1: September – January
     * Semester 2: February – August
     */
    private function getCurrentSemester(): array
    {
        $now = Carbon::now();
        $year = $now->year;

        if ($now->month >= 9) {
            // Semester 1 of the current academic year (Sep – Jan next year)
            $start = Carbon::create($year, 9, 1);
            $end = Carbon::create($year + 1, 1, 31);
        } elseif ($now->month <= 1) {
            // Still in Semester 1 of previous academic year
            $start = Carbon::create($year - 1, 9, 1);
            $end = Carbon::create($year, 1, 31);
        } else {
            // Semester 2 (Feb – Aug)
            $start = Carbon::create($year, 2, 1);
            $end = Carbon::create($year, 8, 31);
        }

        return ['start' => $start, 'end' => $end];
    }

    /**
     * Get the previous semester boundaries.
     */
    private function getPreviousSemester(): array
    {
        $current = $this->getCurrentSemester();
        $start = $current['start']->copy()->subMonth(6); // approximate, but fine for demo
        $end = $current['end']->copy()->subMonth(6);
        return ['start' => $start, 'end' => $end];
    }

    /**
     * Group attendance records by the given unit.
     * Supported: 'day', 'week', 'month', 'semester', 'year'
     */
    private function groupResults($records, $groupBy)
    {
        $grouped = [];
        foreach ($records as $record) {
            $date = Carbon::parse($record->date);
            switch ($groupBy) {
                case 'day':
                    $key = $date->toDateString();
                    break;
                case 'week':
                    $key = $date->format('Y-\WW');
                    break;
                case 'month':
                    $key = $date->format('Y-m');
                    break;
                case 'semester':
                    // determine semester: 1 (Sep-Jan) or 2 (Feb-Aug)
                    $semester = ($date->month >= 9 || $date->month <= 1) ? 1 : 2;
                    $year = $date->year;
                    if ($semester == 1 && $date->month <= 1) $year--;
                    $key = $year . '-S' . $semester;
                    break;
                case 'year':
                    $key = $date->year;
                    break;
                default:
                    $key = $date->toDateString();
            }

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'date' => $key,
                    'present' => 0,
                    'absent' => 0,
                    'late' => 0,
                    'permission' => 0,
                    'total' => 0,
                ];
            }
            $status = $record->status;
            $grouped[$key][$status]++;
            $grouped[$key]['total']++;
        }

        return array_values($grouped);
    }

    /**
     * Generate attendance report.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function attendance(Request $request)
    {
        $this->requireAdminOrTeacher();

        // Validate input
        $validator = Validator::make($request->all(), [
            'range'      => 'nullable|in:today,yesterday,this_week,last_week,this_month,last_month,this_semester,last_semester,this_year,last_year',
            'start_date' => 'required_without:range|date',
            'end_date'   => 'required_without:range|date|after_or_equal:start_date',
            'grade_id'   => 'nullable|exists:grades,id',
            'section_id' => 'nullable|exists:sections,id',
            'student_id' => 'nullable|exists:students,id',
            'status'     => 'nullable|in:present,absent,late,permission',
            'group_by'   => 'nullable|in:day,week,month,semester,year',
            'per_page'   => 'nullable|integer|min:1|max:500',
            'format'     => 'nullable|in:json,csv',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Resolve date range
        if ($request->has('range')) {
            [$start, $end] = $this->resolveDateRange($request);
        } else {
            $start = Carbon::parse($request->start_date);
            $end = Carbon::parse($request->end_date);
        }

        $query = Attendance::with(['student.user', 'subject', 'section.grade'])
            ->whereBetween('date', [$start, $end]);

        // Role‑based restriction: teachers only see their assigned sections
        if (auth()->user()->role_id == 2) {
            $teacher = Teacher::where('user_id', auth()->id())->first();
            if (!$teacher) {
                return response()->json(['success' => false, 'message' => 'Teacher profile not found'], 403);
            }
            $assignedSectionIds = \App\Models\TeacherAssignment::where('teacher_id', $teacher->id)
                ->pluck('section_id');
            $query->whereIn('section_id', $assignedSectionIds);
        }

        // Apply filters
        if ($request->filled('grade_id')) {
            $query->whereHas('section', fn($q) => $q->where('grade_id', $request->grade_id));
        }
        if ($request->filled('section_id')) {
            $query->where('section_id', $request->section_id);
        }
        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Decide response format
        $format = $request->input('format', 'json');

        // For CSV export, we don't paginate; we stream all records.
        if ($format === 'csv') {
            return $this->exportCsv($query, $request);
        }

        // For JSON, support pagination and grouping
        $perPage = $request->input('per_page', 50);

        if ($request->has('group_by')) {
            // Grouping: retrieve all records (no pagination), then group in memory
            $records = $query->orderBy('date')->get();
            $grouped = $this->groupResults($records, $request->group_by);
            $summary = [
                'total_records' => $records->count(),
                'present'       => $records->where('status', 'present')->count(),
                'absent'        => $records->where('status', 'absent')->count(),
                'late'          => $records->where('status', 'late')->count(),
                'permission'    => $records->where('status', 'permission')->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'grouped' => $grouped,
                    'summary' => $summary,
                    'filters' => $request->only(['range', 'start_date', 'end_date', 'grade_id', 'section_id', 'student_id', 'status', 'group_by']),
                ]
            ]);
        }

        // No grouping: paginate results
        $records = $query->orderBy('date')->paginate($perPage);

        $summary = [
            'total_records' => $records->total(),
            'present'       => $records->where('status', 'present')->count(),
            'absent'        => $records->where('status', 'absent')->count(),
            'late'          => $records->where('status', 'late')->count(),
            'permission'    => $records->where('status', 'permission')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'records' => $records->items(),
                'summary' => $summary,
                'pagination' => [
                    'current_page' => $records->currentPage(),
                    'per_page'     => $records->perPage(),
                    'total'        => $records->total(),
                    'last_page'    => $records->lastPage(),
                ],
                'filters' => $request->only(['range', 'start_date', 'end_date', 'grade_id', 'section_id', 'student_id', 'status']),
            ]
        ]);
    }

    /**
     * Export query results to CSV.
     */
    private function exportCsv($query, Request $request)
    {
        $records = $query->orderBy('date')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="attendance_report_' . now()->format('Y-m-d_His') . '.csv"',
        ];

        $callback = function () use ($records) {
            $file = fopen('php://output', 'w');
            // Column headers
            fputcsv($file, [
                'Date', 'Student Name', 'Admission No', 'Grade', 'Section',
                'Subject', 'Teacher', 'Status', 'Remarks'
            ]);

            foreach ($records as $record) {
                fputcsv($file, [
                    $record->date->toDateString(),
                    $record->student->user->name ?? 'N/A',
                    $record->student->admission_number ?? 'N/A',
                    $record->section->grade->name ?? 'N/A',
                    $record->section->name ?? 'N/A',
                    $record->subject->name ?? 'N/A',
                    $record->teacher->user->name ?? 'N/A',
                    $record->status,
                    $record->remarks,
                ]);
            }
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }
}
