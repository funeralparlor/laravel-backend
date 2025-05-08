<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class College extends Model // Replace "Option" with the actual option name
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'name', 'description', 'active'];

    /**
     * Get the students associated with this option.
     */
    public function courses()
{
    return $this->hasMany(Course::class);
}
}