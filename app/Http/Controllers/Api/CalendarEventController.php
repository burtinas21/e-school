<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class CalendarEventController extends Controller
{
    /**
     * Display a listing of calendar events.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = CalendarEvent::with('creator');

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('start_date', [$request->start_date, $request->end_date]);
        }

        // Filter by event type
        if ($request->has('event_type')) {
            $query->where('event_type', $request->event_type);
        }

        // Filter by active events
        if ($request->has('upcoming')) {
            $query->where('start_date', '>=', now());
        }

        $events = $query->orderBy('start_date')->get();

        return response()->json([
            'success' => true,
            'data' => $events
        ]);
    }

    /**
     * Store a newly created calendar event.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'event_type' => 'required|in:holiday,exam,event,closure',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'is_recurring' => 'sometimes|boolean',
            'recurring_pattern' => 'nullable|string|in:yearly,monthly,weekly',
            'affects_attendance' => 'sometimes|boolean',
            'applicable_grades' => 'nullable|string',
            'applicable_sections' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $event = CalendarEvent::create([
            'title' => $request->title,
            'description' => $request->description,
            'event_type' => $request->event_type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'is_recurring' => $request->is_recurring ?? false,
            'recurring_pattern' => $request->recurring_pattern,
            'affects_attendance' => $request->affects_attendance ?? true,
            'applicable_grades' => $request->applicable_grades,
            'applicable_sections' => $request->applicable_sections,
            'created_by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Calendar event created successfully',
            'data' => $event->load('creator')
        ], 201);
    }

    /**
     * Display the specified calendar event.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $event = CalendarEvent::with('creator')->find($id);

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Calendar event not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $event
        ]);
    }

    /**
     * Update the specified calendar event.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $event = CalendarEvent::find($id);

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Calendar event not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'event_type' => 'sometimes|in:holiday,exam,event,closure',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'is_recurring' => 'sometimes|boolean',
            'recurring_pattern' => 'nullable|string|in:yearly,monthly,weekly',
            'affects_attendance' => 'sometimes|boolean',
            'applicable_grades' => 'nullable|string',
            'applicable_sections' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $event->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Calendar event updated successfully',
            'data' => $event->load('creator')
        ]);
    }

    /**
     * Remove the specified calendar event.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $event = CalendarEvent::find($id);

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Calendar event not found'
            ], 404);
        }

        $event->delete();

        return response()->json([
            'success' => true,
            'message' => 'Calendar event deleted successfully'
        ]);
    }

    /**
     * Check if a specific date is a holiday.
     *
     * @param string $date
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkDate($date)
    {
        $date = Carbon::parse($date);

        $event = CalendarEvent::where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->where('event_type', 'holiday')
            ->where('affects_attendance', true)
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $date->format('Y-m-d'),
                'is_holiday' => !is_null($event),
                'event' => $event
            ]
        ]);
    }

    /**
     * Get upcoming events for the next X days.
     *
     * @param int $days
     * @return \Illuminate\Http\JsonResponse
     */
    public function upcoming($days = 30)
    {
        $events = CalendarEvent::where('start_date', '>=', now())
            ->where('start_date', '<=', now()->addDays($days))
            ->orderBy('start_date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $events
        ]);
    }

    /**
     * Get events for a specific month.
     *
     * @param int $year
     * @param int $month
     * @return \Illuminate\Http\JsonResponse
     */
    public function monthEvents($year, $month)
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = Carbon::create($year, $month, 1)->endOfMonth();

        $events = CalendarEvent::where(function($query) use ($start, $end) {
            $query->whereBetween('start_date', [$start, $end])
                  ->orWhereBetween('end_date', [$start, $end])
                  ->orWhere(function($q) use ($start, $end) {
                      $q->where('start_date', '<=', $start)
                        ->where('end_date', '>=', $end);
                  });
        })->orderBy('start_date')->get();

        return response()->json([
            'success' => true,
            'data' => $events
        ]);
    }
}
