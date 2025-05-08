<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Students extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'student_id', //Student Number
        'last_name', // Last Name
        'first_name', // Given Name
        'middle_name', // Middle Name
        'course', // College Course
        'college', // College Faculty e.g BSIT
        'campus', // Campus e.g Main Campus
        'year_level', // Year Level e.g 3rd Year
        'gender', // Gender F or M
        'birthday', // Date of Birth
        'birth_place', // Place of Birth
        'comp_address', // Complete Address
        'barangay', //Barangay
        'town', // Town / City
        'province', // Province e.g Metro Manila
        'email', // Email Address
        'number', // Mobile Number
        'father_name', // Father Full Name, Surname First
        'father_occup', // Father Occupation
        'mother_name', // Mother Full Name
        'mother_occup', // Mother Occupation
        'student_status', // Student Status e.g Regular or Irregular
        'last_sem', // Last Sem of Enrolment for Inactive
        'section', // Section
        'approved', // Approved to share the information
        'scholar_ship', // Approved to share the information

      
    ];
    protected $dates = ['deleted_at'];


 // Add new relationships
 public function college()
 {
     return $this->belongsTo(College::class);
 }
 
 public function course()
 {
     return $this->belongsTo(Course::class);
 }
 
 public function campus()
 {
     return $this->belongsTo(Campus::class);
 }
 
 public function yearlevel()
 {
     return $this->belongsTo(YearLevel::class);
 }
 
 public function section()
 {
     return $this->belongsTo(Section::class);
 }
 
 public function scholarshipType()
 {
     return $this->belongsTo(Scholarship::class);
 }




}