<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Scholarship extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'description', 'active'];

    /**
     * Get the students with this scholarship type.
     */
    public function students()
    {
        return $this->hasMany(Students::class, 'scholar_ship', 'name');
    }
}