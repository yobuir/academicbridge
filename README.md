# Employee Management System API

A comprehensive Laravel 12 REST API for managing employees and attendance records with automated email notifications and reporting capabilities.

## Features

- **Stateless Authentication** (Laravel Sanctum)
  - User Registration
  - Login/Logout
  - Password Reset Flow
- **Employee Management** (CRUD)
  - Employee profiles with unique identifiers
  - Search and filtering
- **Attendance Tracking**
  - Check-in/Check-out system
  - Duplicate check-in prevention
  - Automated email notifications (queued)
- **Reports**
  - PDF daily attendance reports
  - Excel daily attendance exports
- **OpenAPI Documentation** (Scramble)
  - Auto-generated API docs using PHP 8 attributes
  - Available at `/docs/api`

## Tech Stack

- **PHP** 8.3+
- **Laravel** 12
- **PostgreSQL** (via Laravel Sail)
- **Laravel Sanctum** - API authentication
- **Laravel Excel** - Excel report generation
- **Snappy (wkhtmltopdf)** - PDF generation
- **Scramble** - OpenAPI documentation
- **Pest** - Testing framework
- **Mailpit** - Email testing

## Prerequisites

- Docker Desktop
- Git
- Composer (optional, can use Sail's composer)

## Local Setup

### 1. Clone the Repository

```bash
git clone <repository-url>
cd academicbridge
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Environment Configuration

```bash
cp .env.example .env
php artisan key:generate
```

Update `.env` with the following:

```env
APP_NAME="Employee Management API"
APP_URL=http://localhost

DB_CONNECTION=pgsql
DB_HOST=pgsql
DB_PORT=5432
DB_DATABASE=academicbridge
DB_USERNAME=sail
DB_PASSWORD=password

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025

QUEUE_CONNECTION=database
```

### 4. Start Laravel Sail

```bash
./vendor/bin/sail up -d
```

### 5. Run Migrations

```bash
./vendor/bin/sail artisan migrate:fresh
```

### 6. (Optional) Seed Database

```bash
./vendor/bin/sail artisan db:seed
```

### 7. Start Queue Worker

```bash
./vendor/bin/sail artisan queue:work
```

## API Endpoints

### Authentication

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| POST | `/api/register` | Register new user | No |
| POST | `/api/login` | Login user | No |
| POST | `/api/logout` | Logout user | Yes |
| POST | `/api/forgot-password` | Request password reset | No |
| POST | `/api/reset-password` | Reset password | No |

### Employees

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| GET | `/api/employees` | List all employees | Yes |
| POST | `/api/employees` | Create employee | Yes |
| GET | `/api/employees/{id}` | Get employee details | Yes |
| PUT/PATCH | `/api/employees/{id}` | Update employee | Yes |
| DELETE | `/api/employees/{id}` | Delete employee | Yes |

**Query Parameters:**
- `search` - Search by name, email, or employee_identifier
- `sort_by` - Sort field (default: created_at)
- `sort_order` - asc/desc (default: desc)
- `per_page` - Results per page (default: 15)

### Attendance

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| POST | `/api/attendances/check-in` | Record check-in | Yes |
| POST | `/api/attendances/check-out` | Record check-out (via `attendance_id` or `employee_id`) | Yes |
| GET | `/api/attendances` | List attendance records | Yes |
| GET | `/api/attendances/{id}` | Get attendance details | Yes |

**Check-out Parameters:**
- `attendance_id`: The ID of the attendance record.
- `employee_id`: Alternatively, provide the Employee Identifier (e.g., `EMP-1001`) to check out their active session.

**Query Parameters:**
- `employee_id` - Filter by employee
- `date` - Filter by specific date
- `date_from` - Filter from date
- `date_to` - Filter to date
- `sort_by` - Sort field (default: date)
- `sort_order` - asc/desc (default: desc)

### Reports

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| GET | `/api/reports/attendance/pdf?date=YYYY-MM-DD` | Download PDF report | Yes |
| GET | `/api/reports/attendance/excel?date=YYYY-MM-DD` | Download Excel report | Yes |

## API Documentation

Interactive API documentation is available at:

```
http://localhost/docs/api
```

Powered by Scramble with PHP 8 attributes.

## Testing

Run the test suite:

```bash
./vendor/bin/sail artisan test
```

Run with coverage:

```bash
./vendor/bin/sail artisan test --coverage
```

## Code Quality

Format code with Laravel Pint:

```bash
./vendor/bin/sail bin pint
```

## Email Testing

Access Mailpit web interface:

```
http://localhost:8025
```

All emails sent by the application can be viewed here.

## Queue Management

Monitor queue jobs:

```bash
./vendor/bin/sail artisan queue:monitor
```

Failed jobs:

```bash
./vendor/bin/sail artisan queue:failed
```

Retry failed jobs:

```bash
./vendor/bin/sail artisan queue:retry all
```

## Project Structure

```
app/
├── Exports/
│   └── AttendancesExport.php
├── Http/
│   ├── Controllers/Api/V1/
│   │   ├── AttendanceController.php
│   │   ├── AuthController.php
│   │   ├── EmployeeController.php
│   │   └── ReportController.php
│   ├── Requests/V1/
│   │   ├── Attendance/
│   │   ├── Auth/
│   │   └── Employee/
│   └── Resources/V1/
│       ├── AttendanceResource.php
│       └── EmployeeResource.php
├── Jobs/
│   └── SendAttendanceNotification.php
├── Mail/
│   └── AttendanceRecorded.php
└── Models/
    ├── Attendance.php
    ├── Employee.php
    └── User.php
```

## Database Schema

### Users
- id
- name
- email (unique)
- password
- timestamps

### Employees
- id
- employee_identifier (unique)
- first_name
- last_name
- email (unique)
- phone (nullable) 
- timestamps

### Attendances
- id
- employee_id (foreign key)
- check_in (datetime)
- check_out (datetime, nullable)
- date
- timestamps
- Index: (employee_id, date)

## Troubleshooting

### Database Connection Issues

Ensure Sail is running:
```bash
./vendor/bin/sail up -d
```

### Queue Not Processing

Start the queue worker:
```bash
./vendor/bin/sail artisan queue:work
```

### PDF Generation Issues (`wkhtmltopdf`)

If you encounter `wkhtmltopdf` errors on Windows (locally):
1.  Install [wkhtmltopdf](https://wkhtmltopdf.org/downloads.html).
2.  Add the binary path to your `.env`:
    ```env
    WKHTMLTOPDF_BINARY="C:\Program Files\wkhtmltopdf\bin\wkhtmltopdf.exe"
    WKHTMLTOIMAGE_BINARY="C:\Program Files\wkhtmltopdf\bin\wkhtmltoimage.exe"
    ```
    *(Ensure the paths match your installation)*

Ensure `wkhtmltopdf` is available. On Docker (Sail), it's pre-installed if you use a compatible image, or you may need to install it in a custom Dockerfile.

## License

This project is open-sourced software licensed under the MIT license.

## Support

For issues and questions, please open an issue in the repository.
