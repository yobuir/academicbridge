<?php

namespace App\Http\Controllers\Api\V1;

use App\Exports\AttendancesExport;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    /**
     * Generate PDF report for daily attendance.
     */
    public function attendancePdf(Request $request)
    {
        $request->validate([
            'date' => ['required', 'date'],
        ]);

        $date = $request->input('date');
        $attendances = Attendance::with('employee')
            ->where('date', $date)
            ->orderBy('check_in')
            ->get();

        $pdf = SnappyPdf::loadView('reports.attendance-pdf', [
            'attendances' => $attendances,
            'date' => $date,
        ]);

        return $pdf->download('attendance-report-'.$date.'.pdf');
    }

    /**
     * Generate Excel report for daily attendance.
     */
    public function attendanceExcel(Request $request)
    {
        $request->validate([
            'date' => ['required', 'date'],
        ]);

        $date = $request->input('date');

        return Excel::download(
            new AttendancesExport($date),
            'attendance-report-'.$date.'.xlsx'
        );
    }
}
