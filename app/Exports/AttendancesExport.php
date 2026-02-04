<?php

namespace App\Exports;

use App\Models\Attendance;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AttendancesExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(protected string $date) {}

    public function query()
    {
        return Attendance::query()
            ->with('employee')
            ->where('date', $this->date)
            ->orderBy('check_in');
    }

    public function headings(): array
    {
        return [
            'Employee ID',
            'Employee Name',
            'Email',
            'Date',
            'Check In',
            'Check Out',
            'Duration (minutes)',
        ];
    }

    public function map($attendance): array
    {
        $duration = null;
        if ($attendance->check_in && $attendance->check_out) {
            $duration = $attendance->check_in->diffInMinutes($attendance->check_out);
        }

        return [
            $attendance->employee->employee_identifier,
            $attendance->employee->first_name.' '.$attendance->employee->last_name,
            $attendance->employee->email,
            $attendance->date->format('Y-m-d'),
            $attendance->check_in->format('Y-m-d H:i:s'),
            $attendance->check_out?->format('Y-m-d H:i:s') ?? 'Not checked out',
            $duration,
        ];
    }
}
