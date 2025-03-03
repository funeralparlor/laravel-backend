<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Students extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'last_name',
        'first_name',
        'middle_name',
        'semester',
        'course',
        'campus',
        'scholarship_type',
    ];
}
