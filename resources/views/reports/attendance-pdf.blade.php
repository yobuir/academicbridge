<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Attendance Report - {{ $date }}</title>
    <style>
        body {
            margin: 20px;
        }

        h1 {
            color: #2563eb;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }

        th {
            background-color: #2563eb;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f9fafb;
        }

        .footer {
            margin-top: 30px;
            font-size: 12px;
            color: #6b7280;
        }
    </style>
</head>

<body>
    <h1>Daily Attendance Report</h1>
    <p><strong>Date:</strong> {{ \Carbon\Carbon::parse($date)->format('F d, Y') }}</p>
    <p><strong>Total Employees:</strong> {{ $attendances->count() }}</p>

    <table>
        <thead>
            <tr>
                <th>Employee ID</th>
                <th>Employee Name</th>
                <th>Email</th>
                <th>Check In</th>
                <th>Check Out</th>
                <th>Duration</th>
            </tr>
        </thead>
        <tbody>
            @forelse($attendances as $attendance)
                <tr>
                    <td>{{ $attendance->employee->employee_identifier }}</td>
                    <td>{{ $attendance->employee->first_name }} {{ $attendance->employee->last_name }}</td>
                    <td>{{ $attendance->employee->email }}</td>
                    <td>{{ $attendance->check_in->format('h:i A') }}</td>
                    <td>{{ $attendance->check_out ? $attendance->check_out->format('h:i A') : 'Not checked out' }}</td>
                    <td>
                        @if($attendance->check_in && $attendance->check_out)
                            {{ $attendance->check_in->diffInMinutes($attendance->check_out) }} minutes
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center;">No attendance records for this date.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Generated on {{ now()->format('F d, Y h:i A') }}</p>
    </div>
</body>

</html>