<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $duration = null;
        if ($this->check_in && $this->check_out) {
            $duration = $this->check_in->diffInMinutes($this->check_out);
        }

        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'employee' => $this->whenLoaded('employee', fn () => [
                'id' => $this->employee->id,
                'employee_identifier' => $this->employee->employee_identifier,
                'full_name' => $this->employee->first_name.' '.$this->employee->last_name,
            ]),
            'check_in' => $this->check_in?->toIso8601String(),
            'check_out' => $this->check_out?->toIso8601String(),
            'date' => $this->date?->format('Y-m-d'),
            'duration_minutes' => $duration,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
