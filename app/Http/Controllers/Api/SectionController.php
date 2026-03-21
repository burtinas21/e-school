<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\Grade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SectionController extends Controller
{
    /**
     * Display a listing of all sections.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $sections = Section::with('grade')->get();

        return response()->json([
            'success' => true,
            'data' => $sections
        ]);
    }

    /**
     * Store a newly created section.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'grade_id' => 'required|exists:grades,id',
            'name' => 'required|string|max:10',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Check for duplicate section in same grade
        $exists = Section::where('grade_id', $request->grade_id)
            ->where('name', $request->name)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Section already exists in this grade'
            ], 422);
        }

        $section = Section::create([
            'grade_id' => $request->grade_id,
            'name' => $request->name,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Section created successfully',
            'data' => $section->load('grade')
        ], 201);
    }

    /**
     * Display the specified section.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $section = Section::with(['grade', 'students.user'])->find($id);

        if (!$section) {
            return response()->json([
                'success' => false,
                'message' => 'Section not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $section
        ]);
    }

    /**
     * Update the specified section.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $section = Section::find($id);

        if (!$section) {
            return response()->json([
                'success' => false,
                'message' => 'Section not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'grade_id' => 'sometimes|exists:grades,id',
            'name' => 'sometimes|string|max:10',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Check for duplicate if name or grade is being changed
        if ($request->has('name') || $request->has('grade_id')) {
            $gradeId = $request->grade_id ?? $section->grade_id;
            $name = $request->name ?? $section->name;

            $exists = Section::where('grade_id', $gradeId)
                ->where('name', $name)
                ->where('id', '!=', $id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Section already exists in this grade'
                ], 422);
            }
        }

        $section->update($request->only(['grade_id', 'name', 'is_active']));

        return response()->json([
            'success' => true,
            'message' => 'Section updated successfully',
            'data' => $section->load('grade')
        ]);
    }

    /**
     * Remove the specified section.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $section = Section::find($id);

        if (!$section) {
            return response()->json([
                'success' => false,
                'message' => 'Section not found'
            ], 404);
        }

        // Check if section has students
        if ($section->students()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete section because it has students. Remove students first.'
            ], 422);
        }

        $section->delete();

        return response()->json([
            'success' => true,
            'message' => 'Section deleted successfully'
        ]);
    }

    /**
     * Get sections by grade.
     *
     * @param int $grade_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByGrade($grade_id)
    {
        $grade = Grade::find($grade_id);

        if (!$grade) {
            return response()->json([
                'success' => false,
                'message' => 'Grade not found'
            ], 404);
        }

        $sections = Section::where('grade_id', $grade_id)
            ->where('is_active', true)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $sections
        ]);
    }

    /**
     * Get active sections only.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function active()
    {
        $sections = Section::where('is_active', true)
            ->with('grade')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $sections
        ]);
    }
}
