<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    protected $fillable = ['section_name', 'section_code', 'year_level_id', 'max_students', 'status'];
    
    public function college()
    {
        return $this->belongsTo(YearLevel::class);
    }
    
    public function students()
    {
        return $this->hasMany(Students::class);
    }
}
