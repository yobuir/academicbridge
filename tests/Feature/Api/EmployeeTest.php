<?php

use App\Models\Employee;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->token = $this->user->createToken('test-token')->plainTextToken;
});

test('can list employees', function () {
    Employee::factory()->count(5)->create();

    $response = $this->withToken($this->token)
        ->getJson('/api/v1/employees');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'employee_identifier', 'first_name', 'last_name', 'email', 'phone'],
            ],
            'links',
            'meta',
        ]);
});

test('can create employee with valid data', function () {
    $employeeData = [
        'employee_identifier' => 'EMP-1234',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john.doe@example.com',
        'phone' => '+1234567890',
    ];

    $response = $this->withToken($this->token)
        ->postJson('/api/v1/employees', $employeeData);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => ['id', 'employee_identifier', 'first_name', 'last_name', 'email'],
        ]);

    $this->assertDatabaseHas('employees', [
        'employee_identifier' => 'EMP-1234',
        'email' => 'john.doe@example.com',
    ]);
});

test('employee creation fails with duplicate employee_identifier', function () {
    Employee::factory()->create(['employee_identifier' => 'EMP-1234']);

    $response = $this->withToken($this->token)
        ->postJson('/api/v1/employees', [
            'employee_identifier' => 'EMP-1234',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['employee_identifier']);
});

test('employee creation fails with duplicate email', function () {
    Employee::factory()->create(['email' => 'john@example.com']);

    $response = $this->withToken($this->token)
        ->postJson('/api/v1/employees', [
            'employee_identifier' => 'EMP-5678',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('can view single employee', function () {
    $employee = Employee::factory()->create();

    $response = $this->withToken($this->token)
        ->getJson("/api/v1/employees/{$employee->id}");

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'id' => $employee->id,
                'employee_identifier' => $employee->employee_identifier,
                'email' => $employee->email,
            ],
        ]);
});

test('can update employee', function () {
    $employee = Employee::factory()->create();

    $response = $this->withToken($this->token)
        ->putJson("/api/v1/employees/{$employee->id}", [
            'first_name' => 'Updated',
            'last_name' => 'Name',
        ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('employees', [
        'id' => $employee->id,
        'first_name' => 'Updated',
        'last_name' => 'Name',
    ]);
});

test('can delete employee', function () {
    $employee = Employee::factory()->create();

    $response = $this->withToken($this->token)
        ->deleteJson("/api/v1/employees/{$employee->id}");

    $response->assertStatus(200);

    $this->assertDatabaseMissing('employees', [
        'id' => $employee->id,
    ]);
});

test('can search employees by name', function () {
    Employee::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);
    Employee::factory()->create(['first_name' => 'Jane', 'last_name' => 'Smith']);

    $response = $this->withToken($this->token)
        ->getJson('/api/v1/employees?search=John');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data');
});

test('can search employees by email', function () {
    Employee::factory()->create(['email' => 'john@example.com']);
    Employee::factory()->create(['email' => 'jane@example.com']);

    $response = $this->withToken($this->token)
        ->getJson('/api/v1/employees?search=john@example.com');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data');
});

test('can search employees by employee_identifier', function () {
    Employee::factory()->create(['employee_identifier' => 'EMP-1234']);
    Employee::factory()->create(['employee_identifier' => 'EMP-5678']);

    $response = $this->withToken($this->token)
        ->getJson('/api/v1/employees?search=EMP-1234');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data');
});

test('can sort employees', function () {
    Employee::factory()->create(['first_name' => 'Alice']);
    Employee::factory()->create(['first_name' => 'Bob']);
    Employee::factory()->create(['first_name' => 'Charlie']);

    $response = $this->withToken($this->token)
        ->getJson('/api/v1/employees?sort_by=first_name&sort_order=asc');

    $response->assertStatus(200);
    $data = $response->json('data');

    expect($data[0]['first_name'])->toBe('Alice');
});

test('employee endpoints require authentication', function () {
    $response = $this->getJson('/api/v1/employees');
    $response->assertStatus(401);

    $response = $this->postJson('/api/v1/employees', []);
    $response->assertStatus(401);
});
