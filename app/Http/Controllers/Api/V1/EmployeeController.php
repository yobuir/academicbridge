<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Employee\StoreEmployeeRequest;
use App\Http\Requests\V1\Employee\UpdateEmployeeRequest;
use App\Http\Resources\V1\EmployeeResource;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;

class EmployeeController extends Controller
{
    /**
     * Display a listing of employees with optional filtering and search.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Employee::query();

        // Search by name (first_name or last_name)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('last_name', 'ILIKE', '%' . $search . '%')
                    ->orWhere('first_name', 'ILIKE', '%' . $search . '%')
                    ->orWhere('email', 'ILIKE', '%' . $search . '%')
                    ->orWhere('employee_identifier', 'ILIKE', '%' . $search . '%');
            });
        }

        // Filter by department
        if ($request->filled('department')) {
            $query->where('department', $request->input('department'));
        }

        // Filter by designation
        if ($request->filled('designation')) {
            $query->where('designation', $request->input('designation'));
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $employees = $query->paginate($request->input('per_page', 15));

        return EmployeeResource::collection($employees);
    }

    /**
     * Store a newly created employee.
     */
    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $data = $request->validated();
        
        // Auto-generate employee_identifier
        do {
            $identifier = 'EMP-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        } while (Employee::where('employee_identifier', $identifier)->exists());
        
        $data['employee_identifier'] = $identifier;
 
        $employee = Employee::create($data);

        return response()->json([
            'message' => 'Employee created successfully.',
            'data' => new EmployeeResource($employee),
        ], 201);
    }

    /**
     * Display the specified employee.
     */
    public function show(Employee $employee): JsonResponse
    {
        return response()->json([
            'data' => new EmployeeResource($employee),
        ]);
    }

    /**
     * Update the specified employee.
     */
    public function update(UpdateEmployeeRequest $request, Employee $employee): JsonResponse
    {
        $data = $request->validated();

        $employee->update($data);

        return response()->json([
            'message' => 'Employee updated successfully.',
            'data' => new EmployeeResource($employee),
        ]);
    }

    /**
     * Remove the specified employee.
     */
    public function destroy(Employee $employee): JsonResponse
    { 
        $employee->delete();

        return response()->json([
            'message' => 'Employee deleted successfully.',
        ], 200);
    }
}
