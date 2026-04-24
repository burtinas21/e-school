<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Grades",
 *     description="API Endpoints for Grade Management"
 * )
 */
class GradeController extends Controller
{
    /**
     * Display a listing of all grades (public - no auth required)
     *
     * @OA\Get(
     *     path="/api/grades",
     *     tags={"Grades"},
     *     summary="Get all grades",
     *     description="Returns a paginated list of all grades",
     *     @OA\Parameter(
     *         name="level",
     *         in="query",
     *         description="Filter by grade level",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search grades by name",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
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
     *
     * @OA\Post(
     *     path="/api/grades",
     *     tags={"Grades"},
     *     summary="Create a new grade",
     *     description="Creates a new grade (Admin only)",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", maxLength=50, example="Grade 1"),
     *             @OA\Property(property="level", type="integer", minimum=1, maximum=12, example=1),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Grade created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Grade created successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Authentication required",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Authentication required.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthorized. Admin access required.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
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
     *
     * @OA\Get(
     *     path="/api/grades/{id}",
     *     tags={"Grades"},
     *     summary="Get a specific grade",
     *     description="Returns a specific grade with its sections",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Grade ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Grade not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Grade not found")
     *         )
     *     )
     * )
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
     *
     * @OA\Put(
     *     path="/api/grades/{id}",
     *     tags={"Grades"},
     *     summary="Update a grade",
     *     description="Updates an existing grade (Admin only)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Grade ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", maxLength=50, example="Grade 1"),
     *             @OA\Property(property="level", type="integer", minimum=1, maximum=12, example=1),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Grade updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Grade updated successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Authentication required",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Authentication required.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthorized. Admin access required.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Grade not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Grade not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
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
     *
     * @OA\Delete(
     *     path="/api/grades/{id}",
     *     tags={"Grades"},
     *     summary="Delete a grade",
     *     description="Deletes an existing grade (Admin only)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Grade ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Grade deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Grade deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Authentication required",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Authentication required.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthorized. Admin access required.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Grade not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Grade not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Cannot delete grade with sections",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Cannot delete grade because it has sections")
     *         )
     *     )
     * )
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
