<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CollegeController extends Controller
{
    public function getCoursesByCollege($college)
    {
        $courses = [
            'CAFA' => ['Bachelor of Science in Architecture', 'Bachelor of Fine Arts'],
            'CAL' => ['Bachelor of Arts in Communication', 'Bachelor of Arts in Literature'],
            'CBEA' => ['Bachelor of Science in Business Administration', 'Bachelor of Science in Accountancy'],
            // Add other colleges and their courses here
        ];

        return response()->json($courses[$college] ?? []);
    }
}