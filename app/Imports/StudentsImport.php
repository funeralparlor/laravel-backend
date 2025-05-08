<?php

namespace App\Imports;

use App\Models\Students;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class StudentsImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row)
    {
        return new Students([
            'student_id'     => $row['student_id'],
            'last_name'      => $row['last_name'],
            'first_name'     => $row['first_name'],
            'middle_name'    => $row['middle_name'],
            'semester'       => $row['semester'],
            'course'        => $row['course'],
            'campus'         => $row['campus'],
            'scholarship_type' => $row['scholarship_type'],
        ]);
    }

    public function rules(): array
    {
        return [
            'student_id' => 'required|unique:students,student_id|max:255',
            'last_name' => 'required|max:255',
            'first_name' => 'required|max:255',
            'semester' => 'required|max:255',
            'course' => 'required|max:255',
            'campus' => 'required|max:255',
            'scholarship_type' => 'required|max:255',
        ];
    }
}