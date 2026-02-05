<?php

namespace App\Http\Requests\V1\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class CheckOutRequest extends FormRequest
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
        return [
            'attendance_id' => ['required_without:employee_id', 'nullable', 'integer', 'exists:attendances,id'],
            'employee_id' => ['required_without:attendance_id', 'nullable', 'exists:employees,employee_identifier'],
        ];
    }
}
