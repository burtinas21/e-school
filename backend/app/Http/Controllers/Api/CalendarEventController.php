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
     * Ensure the user is authenticated.
     *
     * @return void
     * @throws \Illuminate\Http\JsonResponse
     */
    private function requireAuthentication()
    {
        if (!auth()->check()) {
            abort(response()->json([
                'success' => false,
                'message' => 'Authentication required.'
            ], 401));
        }
    }

    /**
     * Ensure the user is an admin.
     *
     * @return void
     * @throws \Illuminate\Http\JsonResponse
     */
    private function requireAdmin()
    {
        $this->requireAuthentication();
        if (auth()->user()->role_id !== 1) {
            abort(response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.'
            ], 403));
        }
    }

    /**
     * Display a listing of calendar events.
     * Supports filters: start_date, end_date, event_type, upcoming.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $this->requireAuthentication();

        $query = CalendarEvent::with('creator');

        // Filter by event type
        if ($request->has('event_type')) {
            $query->where('event_type', $request->event_type);
        }

        // Filter upcoming events (starting from today)
        if ($request->has('upcoming') && filter_var($request->upcoming, FILTER_VALIDATE_BOOLEAN)) {
            $query->where('start_date', '>=', Carbon::today());
        }

        // Date range filtering (overlap logic)
        if ($request->has('start_date') && $request->has('end_date')) {
            $start = Carbon::parse($request->start_date);
            $end   = Carbon::parse($request->end_date);

            $query->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start, $end])
                  ->orWhereBetween('end_date', [$start, $end])
                  ->orWhere(function ($q2) use ($start, $end) {
                      $q2->where('start_date', '<=', $start)
                         ->where('end_date', '>=', $end);
                  });
            });
        }

        $events = $query->orderBy('start_date')->get();

        return response()->json([
            'success' => true,
            'data'    => $events,
        ]);
    }

    /**
     * Store a newly created calendar event (admin only).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $this->requireAdmin();

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'event_type' => 'required|in:holiday,exam,event,closure',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'is_recurring' => 'sometimes|boolean',
            'recurring_pattern' => 'required_if:is_recurring,true|in:yearly,monthly,weekly',
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
        $this->requireAuthentication();

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
     * Update the specified calendar event (admin only).
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $this->requireAdmin();

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
            'recurring_pattern' => 'required_if:is_recurring,true|in:yearly,monthly,weekly',
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
     * Remove the specified calendar event (admin only).
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $this->requireAdmin();

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
     * Check if a given date is a holiday (affects attendance).
     *
     * @param string $date
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkDate($date)
    {
        $this->requireAuthentication();

        try {
            $dateObj = Carbon::parse($date);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid date format'
            ], 422);
        }

        $dateStr = $dateObj->toDateString();

        $event = CalendarEvent::whereDate('start_date', '<=', $dateStr)
            ->whereDate('end_date', '>=', $dateStr)
            ->where('event_type', 'holiday')
            ->where('affects_attendance', true)
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $dateObj->toDateString(),
                'is_holiday' => !is_null($event),
                'event' => $event
            ]
        ]);
    }

    /**
     * Get upcoming events for the next X days (default 30).
     *
     * @param Request $request
     * @param int|null $days
     * @return \Illuminate\Http\JsonResponse
     */
    public function upcoming(Request $request, $days = null)
    {
        $this->requireAuthentication();

        $days = $days ?? $request->query('days', 30);
        $days = (int) $days;
        $start = Carbon::today();
        $end = Carbon::today()->addDays($days);

        $events = CalendarEvent::where('start_date', '>=', $start)
            ->where('start_date', '<=', $end)
            ->orderBy('start_date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $events
        ]);
    }

    /**
     * Get events for a specific month (year, month).
     * Uses overlap logic to include events spanning the month.
     *
     * @param int $year
     * @param int $month
     * @return \Illuminate\Http\JsonResponse
     */
    public function monthEvents($year, $month)
    {
        $this->requireAuthentication();

        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = Carbon::create($year, $month, 1)->endOfMonth();

        $events = CalendarEvent::where(function ($query) use ($start, $end) {
            $query->whereBetween('start_date', [$start, $end])
                  ->orWhereBetween('end_date', [$start, $end])
                  ->orWhere(function ($q) use ($start, $end) {
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
