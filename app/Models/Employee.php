<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    public function getRouteKeyName()
    {
        return 'employee_identifier';
    }

    protected $fillable = [
        'employee_identifier',
        'first_name',
        'last_name',
        'email',
        'phone',
    ];

    protected function casts(): array
    {
        return [];
    }
}
