<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Period;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PeriodController extends Controller
{
    /**
     * Display a listing of periods.
     */
    public function index(Request $request)
    {
        $query = Period::query();
        if ($request->has('is_active'))
            {
                $query->where('is_active', $request->is_active);
            }
        $periods = Period::orderBy('period_number')->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $periods
        ]);
    }

    /**
     * Store a newly created period.
     */
    public function store(Request $request)
    {
        if (!auth()->check()) {
       return response()->json(['success' => false, 'message' => 'Authentication required.'
       ], 401);
       }
    if (auth()->user()->role_id !== 1) {
       return response()->json(['success' => false, 'message' => 'Unauthorized. Admin access required.'
       ], 403);
       }
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50',
            'period_number' => 'required|integer|unique:periods',
            'start_time' => 'required|date_format:H:i:s',
            'end_time' => 'required|date_format:H:i:s|after:start_time',
            'is_break' => 'sometimes|boolean',
            'break_name' => 'nullable|string|max:50',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $period = Period::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Period created successfully',
            'data' => $period
        ], 201);
    }

    /**
     * Display the specified period.
     */
    public function show($id)
    {
        $period = Period::find($id);

        if (!$period) {
            return response()->json([
                'success' => false,
                'message' => 'Period not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $period
        ]);
    }

    /**
     * Update the specified period.
     */
    public function update(Request $request, $id)
    {
        if (!auth()->check()) {
       return response()->json(['success' => false, 'message' => 'Authentication required.'
       ], 401);
       }
    if (auth()->user()->role_id !== 1) {
       return response()->json(['success' => false, 'message' => 'Unauthorized. Admin access required.'
       ], 403);
       }
        $period = Period::find($id);

        if (!$period) {
            return response()->json([
                'success' => false,
                'message' => 'Period not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:50',
            'period_number' => 'sometimes|integer|unique:periods,period_number,' . $id,
            'start_time' => 'sometimes|date_format:H:i:s',
            'end_time' => 'sometimes|date_format:H:i:s|after:start_time',
            'is_break' => 'sometimes|boolean',
            'break_name' => 'nullable|string|max:50',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $period->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Period updated successfully',
            'data' => $period
        ]);
    }

    /**
     * Remove the specified period.
     */
    public function destroy($id)
    {
        if (!auth()->check()) {
       return response()->json(['success' => false, 'message' => 'Authentication required.'
       ], 401);
       }
    if (auth()->user()->role_id !== 1) {
       return response()->json(['success' => false, 'message' => 'Unauthorized. Admin access required.'
       ], 403);
       }
        $period = Period::find($id);

        if (!$period) {
            return response()->json([
                'success' => false,
                'message' => 'Period not found'
            ], 404);
        }

        // Check if period has attendances
        if ($period->attendances()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete period because it has attendance records'
            ], 422);
        }

        $period->delete();

        return response()->json([
            'success' => true,
            'message' => 'Period deleted successfully'
        ]);
    }

    /**
     * Get only class periods (not breaks)
     */
    public function classes()
    {
        $periods = Period::where('is_break', false)
            ->orderBy('period_number')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $periods
        ]);
    }

    /**
     * Get only breaks
     */
    public function breaks()
    {
        $periods = Period::where('is_break', true)
            ->orderBy('period_number')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $periods
        ]);
    }
}
