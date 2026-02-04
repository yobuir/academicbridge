<?php

use App\Jobs\SendAttendanceNotification;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->token = $this->user->createToken('test-token')->plainTextToken;
    $this->employee = Employee::factory()->create();
});

test('can check in employee', function () {
    $this->withoutExceptionHandling();
    Queue::fake();

    $response = $this->withToken($this->token)
        ->postJson('/api/v1/attendances/check-in', [
            'employee_id' => $this->employee->id,
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'message',
            'data' => ['id', 'employee_id', 'check_in', 'date'],
        ]);
    
    // dump(\App\Models\Attendance::all()->toArray());

    $this->assertDatabaseHas('attendances', [
        'employee_id' => $this->employee->id,
    ]);
    
    expect(\App\Models\Attendance::first()->date->toDateString())->toBe(now()->toDateString());

    Queue::assertPushed(SendAttendanceNotification::class);
});

test('cannot check in twice on same day', function () {
    // First check-in
    Attendance::factory()->create([
        'employee_id' => $this->employee->id,
        'date' => now()->toDateString(),
        'check_in' => now(),
        'check_out' => null,
    ]);

    // Attempt second check-in
    $response = $this->withToken($this->token)
        ->postJson('/api/v1/attendances/check-in', [
            'employee_id' => $this->employee->id,
        ]);

    $response->assertStatus(422)
        ->assertJson([
            'message' => 'You have already checked in today.',
        ]);
});

test('can check in after checking out', function () {
    // Previous attendance with check-out
    Attendance::factory()->create([
        'employee_id' => $this->employee->id,
        'date' => now()->toDateString(),
        'check_in' => now()->subHours(8),
        'check_out' => now()->subHours(1),
    ]);

    $response = $this->withToken($this->token)
        ->postJson('/api/v1/attendances/check-in', [
            'employee_id' => $this->employee->id,
        ]);

    $response->assertStatus(201);
});

test('can check out employee', function () {
    $attendance = Attendance::factory()->create([
        'employee_id' => $this->employee->id,
        'date' => now()->toDateString(),
        'check_in' => now()->subHours(8),
        'check_out' => null,
    ]);

    $response = $this->withToken($this->token)
        ->postJson('/api/v1/attendances/check-out', [
            'attendance_id' => $attendance->id,
        ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'data' => ['id', 'check_in', 'check_out'],
        ]);

    $attendance->refresh();
    expect($attendance->check_out)->not->toBeNull();
});

test('cannot check out twice', function () {
    $attendance = Attendance::factory()->create([
        'employee_id' => $this->employee->id,
        'check_in' => now()->subHours(8),
        'check_out' => now(),
    ]);

    $response = $this->withToken($this->token)
        ->postJson('/api/v1/attendances/check-out', [
            'attendance_id' => $attendance->id,
        ]);

    $response->assertStatus(422)
        ->assertJson([
            'message' => 'You have already checked out.',
        ]);
});

test('can list attendances', function () {
    Attendance::factory()->count(5)->create([
        'employee_id' => $this->employee->id,
    ]);

    $response = $this->withToken($this->token)
        ->getJson('/api/v1/attendances');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'employee_id', 'check_in', 'date'],
            ],
        ]);
});

test('can filter attendances by employee', function () {
    $employee2 = Employee::factory()->create();

    Attendance::factory()->count(3)->create(['employee_id' => $this->employee->id]);
    Attendance::factory()->count(2)->create(['employee_id' => $employee2->id]);

    $response = $this->withToken($this->token)
        ->getJson("/api/v1/attendances?employee_id={$this->employee->id}");

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data');
});

test('can filter attendances by date', function () {
    Attendance::factory()->create([
        'employee_id' => $this->employee->id,
        'date' => '2024-01-01',
    ]);
    Attendance::factory()->create([
        'employee_id' => $this->employee->id,
        'date' => '2024-01-02',
    ]);

    $response = $this->withToken($this->token)
        ->getJson('/api/v1/attendances?date=2024-01-01');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data');
});

test('can filter attendances by date range', function () {
    Attendance::factory()->create([
        'employee_id' => $this->employee->id,
        'date' => '2024-01-01',
    ]);
    Attendance::factory()->create([
        'employee_id' => $this->employee->id,
        'date' => '2024-01-15',
    ]);
    Attendance::factory()->create([
        'employee_id' => $this->employee->id,
        'date' => '2024-01-31',
    ]);

    $response = $this->withToken($this->token)
        ->getJson('/api/v1/attendances?date_from=2024-01-10&date_to=2024-01-20');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data');
});

test('can view single attendance', function () {
    $attendance = Attendance::factory()->create([
        'employee_id' => $this->employee->id,
    ]);

    $response = $this->withToken($this->token)
        ->getJson("/api/v1/attendances/{$attendance->id}");

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'id' => $attendance->id,
                'employee_id' => $this->employee->id,
            ],
        ]);
});

test('attendance resource includes duration when checked out', function () {
    $attendance = Attendance::factory()->create([
        'employee_id' => $this->employee->id,
        'check_in' => now()->subHours(8),
        'check_out' => now(),
    ]);

    $response = $this->withToken($this->token)
        ->getJson("/api/v1/attendances/{$attendance->id}");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => ['duration_minutes'],
        ]);

    expect($response->json('data.duration_minutes'))->toBeGreaterThan(0);
});

test('check-in requires authentication', function () {
    $response = $this->postJson('/api/v1/attendances/check-in', [
        'employee_id' => $this->employee->id,
    ]);

    $response->assertStatus(401);
});

test('check-in validates employee exists', function () {
    $response = $this->withToken($this->token)
        ->postJson('/api/v1/attendances/check-in', [
            'employee_id' => 99999,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['employee_id']);
});
