<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GradeController extends Controller
{
    /**
     * Display a listing of all grades (public - no auth required)
     */
    public function index(Request $request)
    {
        $query = Grade::query();

        // Filter by level
        if ($request->has('level')) {
            $query->where('level', $request->level);
        }

        // Search by name
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $grades = $query->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $grades
        ]);
    }

    /**
     * Store a newly created grade (Admin only)
     */
    public function store(Request $request)
    {

        // Check if user is authenticated
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required.'
            ], 401);
        }

        // ✅ AUTHORIZATION - Only admin can create
        if (auth()->user()->role_id !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50|unique:grades',
            'level' => 'nullable|integer|min:1|max:12',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $grade = Grade::create([
            'name' => $request->name,
            'level' => $request->level,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Grade created successfully',
            'data' => $grade
        ], 201);
    }

    /**
     * Display the specified grade (public - no auth required)
     */
    public function show($id)
    {
        $grade = Grade::with('sections')->find($id);

        if (!$grade) {
            return response()->json([
                'success' => false,
                'message' => 'Grade not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $grade
        ]);
    }

    /**
     * Update the specified grade (Admin only)
     */
    public function update(Request $request, $id)
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required.'
            ], 401);
        }

        // ✅ AUTHORIZATION - Only admin can update
        if (auth()->user()->role_id !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }

        $grade = Grade::find($id);

        if (!$grade) {
            return response()->json([
                'success' => false,
                'message' => 'Grade not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:50|unique:grades,name,' . $id,
            'level' => 'nullable|integer|min:1|max:12',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $grade->update($request->only(['name', 'level', 'is_active']));

        return response()->json([
            'success' => true,
            'message' => 'Grade updated successfully',
            'data' => $grade
        ]);
    }

    /**
     * Remove the specified grade (Admin only)
     */
    public function destroy($id)
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required.'
            ], 401);
        }

        // ✅ AUTHORIZATION - Only admin can delete
        if (auth()->user()->role_id !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }

        $grade = Grade::find($id);

        if (!$grade) {
            return response()->json([
                'success' => false,
                'message' => 'Grade not found'
            ], 404);
        }

        // Check if grade has sections
        if ($grade->sections()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete grade because it has sections'
            ], 422);
        }

        $grade->delete();

        return response()->json([
            'success' => true,
            'message' => 'Grade deleted successfully'
        ]);
    }

    /**
     * Get active grades only (public - no auth required)
     */
    public function active()
    {
        $grades = Grade::where('is_active', true)->get();

        return response()->json([
            'success' => true,
            'data' => $grades
        ]);
    }

    /**
     * Get all sections for a specific grade (public - no auth required)
     */
    public function sections($id)
    {
        $grade = Grade::find($id);

        if (!$grade) {
            return response()->json([
                'success' => false,
                'message' => 'Grade not found'
            ], 404);
        }

        $sections = Section::where('grade_id', $id)->get();

        return response()->json([
            'success' => true,
            'data' => $sections
        ]);
    }
}
