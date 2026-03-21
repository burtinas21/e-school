<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TeacherAssignment;
use App\Models\Teacher;
use App\Models\Subject;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TeacherAssignmentController extends Controller
{
    /**
     * Display a listing of teacher assignments.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = TeacherAssignment::with([
            'teacher.user',
            'subject',
            'section.grade'
        ]);

        // Filter by teacher
        if ($request->has('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }

        // Filter by subject
        if ($request->has('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        // Filter by section
        if ($request->has('section_id')) {
            $query->where('section_id', $request->section_id);
        }

        // Filter by grade (through section)
        if ($request->has('grade_id')) {
            $query->whereHas('section', function($q) use ($request) {
                $q->where('grade_id', $request->grade_id);
            });
        }

        // Filter by academic year
        if ($request->has('academic_year')) {
            $query->where('academic_year', $request->academic_year);
        } else {
            // Default to current year
            $query->where('academic_year', now()->year);
        }

        // Filter by primary status
        if ($request->has('is_primary')) {
            $query->where('is_primary', $request->is_primary);
        }

        $assignments = $query->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $assignments
        ]);
    }

    /**
     * Store a newly created teacher assignment.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'teacher_id' => 'required|exists:teachers,id',
            'subject_id' => 'required|exists:subjects,id',
            'section_id' => 'required|exists:sections,id',
            'academic_year' => 'required|integer|min:2000|max:2100',
            'is_primary' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Check for duplicate assignment
        $exists = TeacherAssignment::where('teacher_id', $request->teacher_id)
            ->where('subject_id', $request->subject_id)
            ->where('section_id', $request->section_id)
            ->where('academic_year', $request->academic_year)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'This teacher is already assigned to this subject and section for the academic year'
            ], 422);
        }

        // Verify subject belongs to the grade of the section
        $section = Section::with('grade')->find($request->section_id);
        $subject = Subject::find($request->subject_id);

        if ($subject->grade_id != $section->grade_id) {
            return response()->json([
                'success' => false,
                'message' => 'This subject does not belong to the grade of the selected section'
            ], 422);
        }

        $assignment = TeacherAssignment::create([
            'teacher_id' => $request->teacher_id,
            'subject_id' => $request->subject_id,
            'section_id' => $request->section_id,
            'academic_year' => $request->academic_year,
            'is_primary' => $request->is_primary ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Teacher assignment created successfully',
            'data' => $assignment->load(['teacher.user', 'subject', 'section.grade'])
        ], 201);
    }

    /**
     * Display the specified teacher assignment.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $assignment = TeacherAssignment::with([
            'teacher.user',
            'subject',
            'section.grade'
        ])->find($id);

        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'Teacher assignment not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $assignment
        ]);
    }

    /**
     * Update the specified teacher assignment.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $assignment = TeacherAssignment::find($id);

        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'Teacher assignment not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'teacher_id' => 'sometimes|exists:teachers,id',
            'subject_id' => 'sometimes|exists:subjects,id',
            'section_id' => 'sometimes|exists:sections,id',
            'academic_year' => 'sometimes|integer|min:2000|max:2100',
            'is_primary' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Check for duplicate if changing unique fields
        if ($request->has('teacher_id') || $request->has('subject_id') ||
            $request->has('section_id') || $request->has('academic_year')) {

            $teacherId = $request->teacher_id ?? $assignment->teacher_id;
            $subjectId = $request->subject_id ?? $assignment->subject_id;
            $sectionId = $request->section_id ?? $assignment->section_id;
            $academicYear = $request->academic_year ?? $assignment->academic_year;

            // Verify subject belongs to grade if section or subject changed
            if ($request->has('section_id') || $request->has('subject_id')) {
                $section = Section::with('grade')->find($sectionId);
                $subject = Subject::find($subjectId);

                if ($subject->grade_id != $section->grade_id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This subject does not belong to the grade of the selected section'
                    ], 422);
                }
            }

            $exists = TeacherAssignment::where('teacher_id', $teacherId)
                ->where('subject_id', $subjectId)
                ->where('section_id', $sectionId)
                ->where('academic_year', $academicYear)
                ->where('id', '!=', $id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'This assignment already exists'
                ], 422);
            }
        }

        $assignment->update($request->only([
            'teacher_id', 'subject_id', 'section_id', 'academic_year', 'is_primary'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Teacher assignment updated successfully',
            'data' => $assignment->load(['teacher.user', 'subject', 'section.grade'])
        ]);
    }

    /**
     * Remove the specified teacher assignment.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $assignment = TeacherAssignment::find($id);

        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'Teacher assignment not found'
            ], 404);
        }

        $assignment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Teacher assignment deleted successfully'
        ]);
    }

    /**
     * Get assignments by teacher.
     *
     * @param int $teacherId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByTeacher($teacherId)
    {
        $teacher = Teacher::find($teacherId);

        if (!$teacher) {
            return response()->json([
                'success' => false,
                'message' => 'Teacher not found'
            ], 404);
        }

        $assignments = TeacherAssignment::where('teacher_id', $teacherId)
            ->with(['subject', 'section.grade'])
            ->where('academic_year', now()->year)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $assignments
        ]);
    }

    /**
     * Get assignments by section.
     *
     * @param int $sectionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBySection($sectionId)
    {
        $section = Section::find($sectionId);

        if (!$section) {
            return response()->json([
                'success' => false,
                'message' => 'Section not found'
            ], 404);
        }

        $assignments = TeacherAssignment::where('section_id', $sectionId)
            ->with(['teacher.user', 'subject'])
            ->where('academic_year', now()->year)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $assignments
        ]);
    }

    /**
     * Get assignments by subject.
     *
     * @param int $subjectId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBySubject($subjectId)
    {
        $subject = Subject::find($subjectId);

        if (!$subject) {
            return response()->json([
                'success' => false,
                'message' => 'Subject not found'
            ], 404);
        }

        $assignments = TeacherAssignment::where('subject_id', $subjectId)
            ->with(['teacher.user', 'section.grade'])
            ->where('academic_year', now()->year)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $assignments
        ]);
    }

    /**
     * Get assignments by grade.
     *
     * @param int $gradeId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByGrade($gradeId)
    {
        $assignments = TeacherAssignment::whereHas('section', function($q) use ($gradeId) {
                $q->where('grade_id', $gradeId);
            })
            ->with(['teacher.user', 'subject', 'section'])
            ->where('academic_year', now()->year)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $assignments
        ]);
    }

    /**
     * Get primary teachers for a section.
     *
     * @param int $sectionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPrimaryTeachers($sectionId)
    {
        $assignments = TeacherAssignment::where('section_id', $sectionId)
            ->where('is_primary', true)
            ->where('academic_year', now()->year)
            ->with(['teacher.user', 'subject'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $assignments
        ]);
    }

    /**
     * Bulk create assignments.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'assignments' => 'required|array',
            'assignments.*.teacher_id' => 'required|exists:teachers,id',
            'assignments.*.subject_id' => 'required|exists:subjects,id',
            'assignments.*.section_id' => 'required|exists:sections,id',
            'assignments.*.academic_year' => 'required|integer|min:2000|max:2100',
            'assignments.*.is_primary' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $created = [];
        $errors = [];

        foreach ($request->assignments as $index => $item) {
            // Check for duplicate
            $exists = TeacherAssignment::where('teacher_id', $item['teacher_id'])
                ->where('subject_id', $item['subject_id'])
                ->where('section_id', $item['section_id'])
                ->where('academic_year', $item['academic_year'])
                ->exists();

            if ($exists) {
                $errors[] = "Assignment at index $index already exists";
                continue;
            }

            // Verify subject belongs to grade
            $section = Section::with('grade')->find($item['section_id']);
            $subject = Subject::find($item['subject_id']);

            if ($subject->grade_id != $section->grade_id) {
                $errors[] = "Assignment at index $index: subject does not belong to the grade";
                continue;
            }

            $assignment = TeacherAssignment::create([
                'teacher_id' => $item['teacher_id'],
                'subject_id' => $item['subject_id'],
                'section_id' => $item['section_id'],
                'academic_year' => $item['academic_year'],
                'is_primary' => $item['is_primary'] ?? true,
            ]);

            $created[] = $assignment;
        }

        return response()->json([
            'success' => true,
            'message' => count($created) . ' assignments created successfully',
            'errors' => $errors,
            'data' => $created
        ]);
    }
}
