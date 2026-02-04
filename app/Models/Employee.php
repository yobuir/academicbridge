<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_identifier',
        'first_name',
        'last_name',
        'email',
        'phone',
        'photo',
    ];

    protected function casts(): array
    {
        return [];
    }
}
