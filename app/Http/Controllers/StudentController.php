<?php

namespace App\Http\Controllers;

use App\Models\Students;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Imports\StudentsImport;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Illuminate\Support\Facades\Validator;


class StudentController extends Controller
{
    // Get all students
    public function index()
    {
        return response()->json(Students::all());
    }

    // Create student with validation
    public function store(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            'student_id' => 'required|unique:students|max:255',
            'last_name' => 'required|max:255',
            'first_name' => 'required|max:255',
            'middle_name' => 'nullable|max:255',
            'semester' => 'required|max:255',
            'course' => 'required|max:255',
            'campus' => 'required|max:255',
            'scholarship_type' => 'required|max:255',
        ]);
    
        // Create the student
        $students = Students::create($validated);
    
        // Return the created student as JSON
        return response()->json($students, 201); // 201 = Created
    }
    // Update student with validation
    public function update(Request $request, $id)
    {
        $students = Students::findOrFail($id);
        
        $validated = $request->validate([
            'student_id' => [
        'required',
        'max:255',
        Rule::unique('students', 'student_id')->ignore($students->id),
    ],
            'last_name' => 'required|max:255',
            'first_name' => 'required|max:255',
            'middle_name' => 'nullable|max:255',
            'semester' => 'required|max:255',
            'course' => 'required|max:255',
            'campus' => 'required|max:255',
            'scholarship_type' => 'required|max:255',
        ]);

        $students->update($validated);
        return response()->json($students);
    }

    public function show($id)
{
    $students = Students::findOrFail($id);
    return response()->json($students);
}

    // Delete student
    public function destroy($id)
    {
        Students::destroy($id);
        return response()->json(null, 204);
    }

    // Import students
    public function import(Request $request)
{
    $request->validate([
        'file' => 'required|file|mimes:xls,xlsx,xlsm'
    ]);

    $file = $request->file('file');
    $spreadsheet = IOFactory::load($file->getPathname());
    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->toArray();

    // Trim headers and remove empty rows
    $headers = array_map('trim', array_shift($rows));
    $students = [];
    $errors = [];
    
    foreach ($rows as $index => $row) {
        // Skip empty rows
        if (empty(array_filter($row))) {
            continue;
        }

        // Combine headers with row data and ensure string values
        $row = array_map('strval', array_combine($headers, $row));

        // Data validation
        $validator = Validator::make($row, [
            'Student ID' => 'required|unique:students,student_id',
            'Last Name' => 'required',
            'First Name' => 'required',
            'Semester' => 'required',
            'Course' => 'required',
            'Campus' => 'required',
            'Scholarship Type' => 'required|in:Academic,Athletic,Need-Based,Government'
        ]);

        if ($validator->fails()) {
            $errors["Row $index"] = $validator->errors()->all();
            continue;
        }

        $students[] = [
            'student_id' => $row['Student ID'],
            'last_name' => $row['Last Name'],
            'first_name' => $row['First Name'],
            'middle_name' => $row['Middle Name'] ?? null,
            'semester' => $row['Semester'],
            'course' => $row['Course'],
            'campus' => $row['Campus'],
            'scholarship_type' => $row['Scholarship Type'],
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    if (!empty($errors)) {
        return response()->json([
            'message' => 'Some rows failed validation',
            'errors' => $errors
        ], 422);
    }

    try {
        \DB::table('students')->insert($students);
        return response()->json([
            'message' => 'Students imported successfully',
            'count' => count($students)
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Import failed',
            'error' => $e->getMessage()
        ], 500);
    }
}

}