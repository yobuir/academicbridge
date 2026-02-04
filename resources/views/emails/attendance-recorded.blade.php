<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Attendance Recorded</title>
</head>

<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #2563eb;">Attendance Recorded</h2>

        <p>Hello {{ $employee->first_name }} {{ $employee->last_name }},</p>

        <p>Your attendance has been recorded successfully.</p>

        <div style="background-color: #f3f4f6; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <p style="margin: 5px 0;"><strong>Employee ID:</strong> {{ $employee->employee_identifier }}</p>
            <p style="margin: 5px 0;"><strong>Date:</strong> {{ $attendance->date->format('F d, Y') }}</p>
            <p style="margin: 5px 0;"><strong>Check-in Time:</strong> {{ $attendance->check_in->format('h:i A') }}</p>
            @if($attendance->check_out)
                <p style="margin: 5px 0;"><strong>Check-out Time:</strong> {{ $attendance->check_out->format('h:i A') }}</p>
            @endif
        </div>

        <p>Thank you!</p>

        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 20px 0;">
        <p style="font-size: 12px; color: #6b7280;">
            This is an automated message from the Employee Management System.
        </p>
    </div>
</body>

</html>