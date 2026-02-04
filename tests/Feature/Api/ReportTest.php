<?php

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->token = $this->user->createToken('test-token')->plainTextToken;

    \Barryvdh\Snappy\Facades\SnappyPdf::shouldReceive('loadView')
        ->andReturnSelf();
    
    \Barryvdh\Snappy\Facades\SnappyPdf::shouldReceive('download')
        ->andReturn(response('PDF content', 200, ['Content-Type' => 'application/pdf']));
});

test('can generate pdf report for specific date', function () {

    $employee = Employee::factory()->create();
    $date = '2024-01-15';

    Attendance::factory()->create([
        'employee_id' => $employee->id,
        'date' => $date,
        'check_in' => now()->setDateFrom($date)->setTime(9, 0),
        'check_out' => now()->setDateFrom($date)->setTime(17, 0),
    ]);

    $response = $this->withToken($this->token)
        ->getJson("/api/v1/reports/attendance/pdf?date={$date}");

    $response->assertStatus(200)
        ->assertHeader('content-type', 'application/pdf');
});

test('can generate excel report for specific date', function () {
    $employee = Employee::factory()->create();
    $date = '2024-01-15';

    Attendance::factory()->create([
        'employee_id' => $employee->id,
        'date' => $date,
        'check_in' => now()->setDateFrom($date)->setTime(9, 0),
        'check_out' => now()->setDateFrom($date)->setTime(17, 0),
    ]);

    $response = $this->withToken($this->token)
        ->getJson("/api/v1/reports/attendance/excel?date={$date}");

    $response->assertStatus(200)
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

test('pdf report requires date parameter', function () {
    $response = $this->withToken($this->token)
        ->getJson('/api/v1/reports/attendance/pdf');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['date']);
});

test('excel report requires date parameter', function () {
    $response = $this->withToken($this->token)
        ->getJson('/api/v1/reports/attendance/excel');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['date']);
});

test('pdf report validates date format', function () {
    $response = $this->withToken($this->token)
        ->getJson('/api/v1/reports/attendance/pdf?date=invalid-date');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['date']);
});

test('excel report validates date format', function () {
    $response = $this->withToken($this->token)
        ->getJson('/api/v1/reports/attendance/excel?date=invalid-date');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['date']);
});

test('pdf report works with no attendances', function () {
    $date = '2024-01-15';

    $response = $this->withToken($this->token)
        ->getJson("/api/v1/reports/attendance/pdf?date={$date}");

    $response->assertStatus(200)
        ->assertHeader('content-type', 'application/pdf');
});

test('excel report works with no attendances', function () {
    $date = '2024-01-15';

    $response = $this->withToken($this->token)
        ->getJson("/api/v1/reports/attendance/excel?date={$date}");

    $response->assertStatus(200);
});

test('pdf report includes multiple employees', function () {
    $date = '2024-01-15';

    $employee1 = Employee::factory()->create();
    $employee2 = Employee::factory()->create();

    Attendance::factory()->create([
        'employee_id' => $employee1->id,
        'date' => $date,
    ]);
    Attendance::factory()->create([
        'employee_id' => $employee2->id,
        'date' => $date,
    ]);

    $response = $this->withToken($this->token)
        ->getJson("/api/v1/reports/attendance/pdf?date={$date}");

    $response->assertStatus(200);
});

test('report endpoints require authentication', function () {
    $response = $this->getJson('/api/v1/reports/attendance/pdf?date=2024-01-15');
    $response->assertStatus(401);

    $response = $this->getJson('/api/v1/reports/attendance/excel?date=2024-01-15');
    $response->assertStatus(401);
});
