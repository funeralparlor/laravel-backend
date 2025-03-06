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
    public function index(Request $request)
    {
        // Validate query parameters
        $request->validate([
            'page' => 'nullable|integer|min:1',
            'limit' => 'nullable|integer|min:1',
            'semester' => 'nullable|array',
            'course' => 'nullable|array',
            'campus' => 'nullable|array',
            'scholarship_type' => 'nullable|array',
            'search' => 'nullable|string|max:100',
        ]);
    
        // Get pagination parameters
        $page = $request->query('page', 1);
        $limit = $request->query('limit', 10);
        
        // If limit is -1, we want all records (no pagination)
        $query = Students::query();
        
        // Apply filters
        if ($request->has('semester') && count($request->semester) > 0) {
            $query->whereIn('semester', $request->semester);
        }
        
        if ($request->has('course') && count($request->course) > 0) {
            $query->whereIn('course', $request->course);
        }
        
        if ($request->has('campus') && count($request->campus) > 0) {
            $query->whereIn('campus', $request->campus);
        }
        
        if ($request->has('scholarship_type') && count($request->scholarship_type) > 0) {
            $query->whereIn('scholarship_type', $request->scholarship_type);
        }
        
        // Search by student ID
        if ($request->has('search') && !empty($request->search)) {
            $query->where('student_id', 'LIKE', '%' . $request->search . '%');
        }
        
        if ($limit === -1) {
            $students = $query->get();
            return response()->json([
                'data' => $students,
                'page' => 1,
                'pages' => 1,
                'total' => $students->count(),
            ]);
        } else {
            $students = $query->paginate($limit, ['*'], 'page', $page);
            return response()->json([
                'data' => $students->items(),
                'page' => $students->currentPage(),
                'pages' => $students->lastPage(),
                'total' => $students->total(),
            ]);
        }
    }

    // Create student with validation
    public function store(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            'student_id' => 'required|unique:students|max:255',  //Student Number

            'last_name' => 'required|max:255',             // Last Name

            'first_name' => 'required|max:255',            // Given Name

            'middle_name' => 'nullable|max:255',            // Middle Name

            'course' => 'required|max:255',            // College Course

            'college' => 'required|max:255',             // College Faculty e.g BSIT

            'campus' => 'required|max:255',             // Campus e.g Main Campus

            'year_level' => 'required|max:255',             // Year Level e.g 3rd Year

            'gender' => 'required|unique:students|max:255',             // Gender F or M

            'birthday' => 'required|max:255',             // Date of Birth

            'birth_place' => 'required|max:255',             // Place of Birth

            'comp_address' => 'nullable|max:255',             // Complete Address

            'barangay' => 'required|max:255', // Barangay

            'town' => 'required|max:255',             // Town / City

            'province' => 'required|unique:students|max:255',             // Province e.g Metro Manila

            'email' => 'required|max:255',             // Email Address

            'number' => 'required|max:255',             // Mobile Number

            'father_name' => 'nullable|max:255',             // Father Full Name, Surname First

            'father_occup' => 'required|max:255',             // Father Occupation

            'mother_name' => 'required|max:255',             // Mother Full Name

            'mother_occup' => 'required|max:255',             // Mother Occupation

            'student_status' => 'required|max:255',             // Student Status e.g Regular or Irregular

            'last_sem' => 'required|max:255',             // Last Sem of Enrolment for Inactive

            'section' => 'required|max:255',             // Section

            'approved' => 'required|max:255',             // Approved to share the information

            

         
    
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


/**
 * Handle bulk deletion of students
 * 
 * @param  \Illuminate\Http\Request  $request
 * @return \Illuminate\Http\JsonResponse
 */
public function bulkDelete(Request $request)
{
    // Check if we're getting data from the request body or from a delete request
    $ids = $request->input('ids', []);
    
    // If using DELETE method, the data might be in the request body
    if (empty($ids) && $request->isMethod('delete')) {
        $data = $request->json()->all();
        $ids = $data['ids'] ?? [];
    }
    
    // Validate the IDs
    $validator = Validator::make(['ids' => $ids], [
        'ids' => 'required|array',
        'ids.*' => 'required|integer|exists:students,id',
    ]);
    
    if ($validator->fails()) {
        return response()->json([
            'message' => 'Invalid student IDs',
            'errors' => $validator->errors()
        ], 422);
    }

    // Perform bulk deletion
    $deletedCount = Students::whereIn('id', $ids)->delete();

    return response()->json([
        'message' => "Successfully deleted $deletedCount students",
        'deletedCount' => $deletedCount,
    ]);
}




}