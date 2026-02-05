<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Attendance\CheckInRequest;
use App\Http\Requests\V1\Attendance\CheckOutRequest;
use App\Http\Resources\V1\AttendanceResource;
use App\Jobs\SendAttendanceNotification;
use App\Models\Attendance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AttendanceController extends Controller
{
    /**
     * List of attendance records with optional filtering.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Attendance::with('employee');

        // Filter by employee
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->input('employee_id'));
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->input('date_to'));
        }

        // Filter by specific date
        if ($request->filled('date')) {
            $query->whereDate('date', $request->input('date'));
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'date');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $attendances = $query->paginate($request->input('per_page', 15));

        return AttendanceResource::collection($attendances);
    }

    /**
     * Record employee check-in.
     */
    public function checkIn(CheckInRequest $request): JsonResponse
    {
        $identifier = $request->validated()['employee_id'];
        $employee = \App\Models\Employee::where('employee_identifier', $identifier)->firstOrFail();
        $employeeId = $employee->id;
        
        $today = now()->toDateString();

        // Check if employee already checked in today
        $existingAttendance = Attendance::where('employee_id', $employeeId)
            ->whereDate('date', $today)
            ->whereNull('check_out')
            ->first();

        if ($existingAttendance) {
            return response()->json([
                'message' => 'You have already checked in today.',
                'data' => new AttendanceResource($existingAttendance->load('employee')),
            ], 422);
        }

        $attendance = Attendance::create([
            'employee_id' => $employeeId,
            'check_in' => now(),
            'date' => $today,
        ]);

        // Dispatch email notification job
        dispatch(new SendAttendanceNotification($attendance->load('employee')));

        return response()->json([
            'message' => 'Successfully checked in.',
            'data' => new AttendanceResource($attendance->load('employee')),
        ], 201);
    }

    /**
     * Record employee check-out.
     */
    public function checkOut(CheckOutRequest $request): JsonResponse
    {
        $data = $request->validated();

        if (!empty($data['attendance_id'])) {
            $attendance = Attendance::findOrFail($data['attendance_id']);
        } else {
            // Resolve via employee_id
            $identifier = $data['employee_id'];
            $employee = \App\Models\Employee::where('employee_identifier', $identifier)->firstOrFail();
            
            $attendance = Attendance::where('employee_id', $employee->id)
                ->whereNull('check_out')
                ->latest()
                ->first();

            if (!$attendance) {
                return response()->json([
                    'message' => 'No active check-in found for this employee.',
                ], 404);
            }
        }

        if ($attendance->check_out) {
            return response()->json([
                'message' => 'You have already checked out.',
                'data' => new AttendanceResource($attendance->load('employee')),
            ], 422);
        }

        $attendance->update([
            'check_out' => now(),
        ]);

        return response()->json([
            'message' => 'Successfully checked out.',
            'data' => new AttendanceResource($attendance->load('employee')),
        ]);
    }

    /**
     * Display the specified attendance record.
     */
    public function show(Attendance $attendance): JsonResponse
    {
        return response()->json([
            'data' => new AttendanceResource($attendance->load('employee')),
        ]);
    }
}
