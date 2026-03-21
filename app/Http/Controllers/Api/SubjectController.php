<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubjectController extends Controller
{
    public function index()
    {
        $subjects = Subject::with('grade')->get();
        return response()->json(['success' => true, 'data' => $subjects]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'grade_id' => 'required|exists:grades,id',
            'subject_code' => 'nullable|string|unique:subjects',  // Changed from 'code'
            'description' => 'nullable|string',
            'credits' => 'nullable|numeric',
            'is_core' => 'boolean',
            'is_active' => 'boolean',  // Added
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $subject = Subject::create($request->all());
        return response()->json(['success' => true, 'data' => $subject], 201);
    }

    public function show($id)
    {
        $subject = Subject::with('grade')->find($id);
        if (!$subject) {
            return response()->json(['success' => false, 'message' => 'Subject not found'], 404);
        }
        return response()->json(['success' => true, 'data' => $subject]);
    }

    public function update(Request $request, $id)
    {
        $subject = Subject::find($id);
        if (!$subject) {
            return response()->json(['success' => false, 'message' => 'Subject not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'subject_code' => 'nullable|string|unique:subjects,subject_code,' . $id,  // Changed
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $subject->update($request->all());
        return response()->json(['success' => true, 'data' => $subject]);
    }

    public function destroy($id)
    {
        $subject = Subject::find($id);
        if (!$subject) {
            return response()->json(['success' => false, 'message' => 'Subject not found'], 404);
        }
        $subject->delete();
        return response()->json(['success' => true, 'message' => 'Subject deleted']);
    }

    public function getByGrade($grade_id)
    {
        $subjects = Subject::where('grade_id', $grade_id)->get();
        return response()->json(['success' => true, 'data' => $subjects]);
    }
}
