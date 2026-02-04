<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Attendance>
 */
class AttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $checkIn = fake()->dateTimeBetween('-30 days', 'now');
        $checkOut = fake()->boolean(80) ? fake()->dateTimeBetween($checkIn, $checkIn->format('Y-m-d') . ' 23:59:59') : null;

        return [
            'employee_id' => \App\Models\Employee::factory(),
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'date' => $checkIn->format('Y-m-d'),
        ];
    }
}
