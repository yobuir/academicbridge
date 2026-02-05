<?php

namespace App\Http\Requests\V1\Employee;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $employee = $this->route('employee');

        $employeeId = $employee instanceof Employee
            ? $employee->id
            : Employee::where('employee_identifier', $employee)->value('id');

        return [
            'employee_identifier' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('employees', 'employee_identifier')->ignore($employeeId),
            ],
            'first_name' => ['sometimes', 'required', 'string', 'max:255'],
            'last_name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('employees', 'email')->ignore($employeeId),
            ],
        ];
    }
}
