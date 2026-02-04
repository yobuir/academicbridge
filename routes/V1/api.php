<?php

use App\Http\Controllers\Api\V1\AttendanceController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\EmployeeController;
use App\Http\Controllers\Api\V1\ReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

route::prefix('v1')->name('v1.')->group(function () {
    // Authentication Routes
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    // Protected Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', fn (Request $request) => $request->user());

        // Employee Routes
        Route::apiResource('employees', EmployeeController::class);

        // Attendance Routes
        Route::post('/attendances/check-in', [AttendanceController::class, 'checkIn']);
        Route::post('/attendances/check-out', [AttendanceController::class, 'checkOut']);
        Route::get('/attendances', [AttendanceController::class, 'index']);
        Route::get('/attendances/{attendance}', [AttendanceController::class, 'show']);

        // Report Routes
        Route::get('/reports/attendance/pdf', [ReportController::class, 'attendancePdf']);
        Route::get('/reports/attendance/excel', [ReportController::class, 'attendanceExcel']);
    });
});
