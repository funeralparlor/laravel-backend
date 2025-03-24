<?php

namespace App\Http\Controllers;

use App\Models\Students;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{
    // Common validation rules to avoid repetition
    private function getValidationRules($id = null)
    {
        $rules = [
            'student_id' => ['required', 'max:255'],
            'last_name' => 'required|max:255',
            'first_name' => 'required|max:255',
            'middle_name' => 'nullable|max:255',
            'course' => 'required|max:255',
            'college' => 'required|max:255',
            'campus' => 'required|max:255',
            'year_level' => 'required|max:255',
            'gender' => 'required|max:255',
            'birthday' => 'required|date',
            'birth_place' => 'required|max:255',
            'comp_address' => 'nullable|max:255',
            'barangay' => 'required|max:255',
            'town' => 'required|max:255',
            'province' => 'required|max:255',
            'email' => 'required|email|max:255',
            'number' => 'required|max:20',
            'father_name' => 'required|max:255',
            'father_occup' => 'required|max:255',
            'mother_name' => 'required|max:255',
            'mother_occup' => 'required|max:255',
            'student_status' => 'required|max:255',
            'last_sem' => 'required|max:255',
            'section' => 'required|max:255',
            'approved' => 'required|in:yes,no,Yes,No,YES,NO,1,0',
            'scholar_ship' => 'required|max:255',
        ];

        // For update operations, modify the student_id rule to ignore the current record
        if ($id) {
            $rules['student_id'][] = Rule::unique('students')->ignore($id);
        } else {
            $rules['student_id'][] = 'unique:students';
        }

        return $rules;
    }

    // Get all students
    public function index(Request $request)
    {
        // Validate query parameters
        $request->validate([
            'page' => 'nullable|integer|min:1',
            'limit' => 'nullable|integer|min:1',
            'year_level' => 'nullable|array',
            'semester' => 'nullable|array',
            'course' => 'nullable|array',
            'campus' => 'nullable|array',
            'scholarship_type' => 'nullable|array',
            'scholar_ship' => 'nullable|array',
            'search' => 'nullable|string|max:100',
        ]);
    
        // Get pagination parameters
        $page = $request->query('page', 1);
        $limit = $request->input('limit', 10);
        
        // Start query
        $query = Students::query();
        
        // Apply filters with consistent approach
        $filterFields = ['year_level', 'semester', 'course', 'campus', 'scholarship_type', 'scholar_ship'];
        foreach ($filterFields as $field) {
            if ($request->has($field) && is_array($request->input($field)) && count($request->input($field)) > 0) {
                $query->whereIn($field, $request->input($field));
            }
        }
        
        // Search by student ID - using a more secure parameterized query
        if ($request->filled('search')) {
            $query->where('student_id', 'LIKE', '%' . $request->input('search') . '%');
        }
        
        // Handle pagination
        if ($limit == -1) {
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
        // Validate the request using common rules
        $validated = $request->validate($this->getValidationRules());
    
        // Create the student
        $student = Students::create($validated);
    
        // Return the created student as JSON
        return response()->json($student, 201);
    }

    // Update student with validation
    public function update(Request $request, $id)
    {
        $student = Students::findOrFail($id);
        
        // Validate using common rules with the current ID
        $validated = $request->validate($this->getValidationRules($id));

        $student->update($validated);
        return response()->json($student);
    }

    // Get single student by ID
    public function show($id)
    {
        $student = Students::findOrFail($id);
        return response()->json($student);
    }

    // Delete student
    public function destroy($id)
    {
        $student = Students::findOrFail($id);
    $student->delete();
    return response()->json(['message' => 'Student moved to trash']);
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
        
        if (empty($rows)) {
            return response()->json([
                'message' => 'The uploaded file contains no data'
            ], 422);
        }
        
        $headers = array_map('trim', array_shift($rows));
        $students = [];
        $errors = [];
        $requiredColumns = [
            'STUDENT NUMBER', 'LAST NAME', 'GIVEN NAME', 'COURSE', 'COLLEGE', 
            'CAMPUS', 'YEAR LEVEL', 'GENDER', 'DATE OF BIRTH', 'PLACE OF BIRTH',
            'BARANGAY', 'TOWN/CITY', 'Province', 'Email', 'FatherName',
            'Father_Occupation', 'MotherName', 'Mother_Occupation', 'Student_Status',
            'Last sem of enrolment for inactive', 'Section', 'Approved to share the information', 'Scholarship Type'
        ];
        
        // Verify all required headers exist
        $missingColumns = array_diff($requiredColumns, $headers);
        if (!empty($missingColumns)) {
            return response()->json([
                'message' => 'The uploaded file is missing required columns',
                'missing_columns' => $missingColumns
            ], 422);
        }
        
        foreach ($rows as $index => $row) {
            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            // Check row length matches headers
            if (count($row) !== count($headers)) {
                $errors["Row " . ($index + 2)] = ["Column count mismatch. Expected " . count($headers) . " columns, got " . count($row)];
                continue;
            }

            // Combine headers with row data and ensure string values
            $rowData = [];
            foreach ($headers as $colIndex => $header) {
                $value = $row[$colIndex];
                $rowData[$header] = is_numeric($value) ? (string)$value : $value;
            }

            // Data validation
            $validator = Validator::make($rowData, [
                'STUDENT NUMBER' => 'required|unique:students,student_id',
                'LAST NAME' => 'required',
                'GIVEN NAME' => 'required',
                'MIDDLE NAME' => 'nullable',
                'COURSE' => 'required',
                'COLLEGE' => 'required',
                'CAMPUS' => 'required',
                'YEAR LEVEL' => 'required',
                'GENDER' => 'required',
                'DATE OF BIRTH' => 'required',
                'PLACE OF BIRTH' => 'required',
                'COMPLETE ADDRESS' => 'nullable',
                'BARANGAY' => 'required',
                'TOWN/CITY' => 'required',
                'Province' => 'required',
                'Email' => 'required|email',
                'MobileNo' => 'nullable',
                'FatherName' => 'required',
                'Father_Occupation' => 'required',
                'MotherName' => 'required',
                'Mother_Occupation' => 'required',
                'Student_Status' => 'required',
                'Last sem of enrolment for inactive' => 'required',
                'Section' => 'required',
                'Approved to share the information' => 'required',
                'Scholarship Type' => 'required'
            ]);

            if ($validator->fails()) {
                $errors["Row " . ($index + 2)] = $validator->errors()->all();
                continue;
            }

            $students[] = [
                'student_id' => $rowData['STUDENT NUMBER'],
                'last_name' => $rowData['LAST NAME'],
                'first_name' => $rowData['GIVEN NAME'],
                'middle_name' => $rowData['MIDDLE NAME'] ?? null,
                'course' => $rowData['COURSE'],
                'college' => $rowData['COLLEGE'],
                'campus' => $rowData['CAMPUS'],
                'year_level' => $rowData['YEAR LEVEL'],
                'gender' => $rowData['GENDER'],
                'birthday' => $rowData['DATE OF BIRTH'],
                'birth_place' => $rowData['PLACE OF BIRTH'],
                'comp_address' => $rowData['COMPLETE ADDRESS'] ?? null,
                'barangay' => $rowData['BARANGAY'],
                'town' => $rowData['TOWN/CITY'],
                'province' => $rowData['Province'],
                'email' => $rowData['Email'],
                'number' => $rowData['MobileNo'] ?? null,
                'father_name' => $rowData['FatherName'],
                'father_occup' => $rowData['Father_Occupation'],
                'mother_name' => $rowData['MotherName'],
                'mother_occup' => $rowData['Mother_Occupation'],
                'student_status' => $rowData['Student_Status'],
                'last_sem' => $rowData['Last sem of enrolment for inactive'],
                'section' => $rowData['Section'],
                'approved' => $rowData['Approved to share the information'],
                'scholar_ship' => $rowData['Scholarship Type'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (empty($students)) {
            return response()->json([
                'message' => 'No valid student data found in the uploaded file',
                'errors' => $errors
            ], 422);
        }

        if (!empty($errors)) {
            return response()->json([
                'message' => 'Some rows failed validation',
                'errors' => $errors
            ], 422);
        }

        try {
            // Use chunking for better memory management with large datasets
            $chunkedStudents = array_chunk($students, 100);
            foreach ($chunkedStudents as $chunk) {
                DB::table('students')->insert($chunk);
            }
            
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
        // Extract IDs from request
        $ids = $request->input('ids', []);
        
        // If using DELETE method, the data might be in the request body
        if (empty($ids) && $request->isMethod('delete')) {
            $data = $request->json()->all();
            $ids = $data['ids'] ?? [];
        }
        
        // Validate the IDs
        $validator = Validator::make(['ids' => $ids], [
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:students,id',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid student IDs',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Use transaction for atomicity
            DB::beginTransaction();
            $deletedCount = Students::whereIn('id', $ids)->delete();
            DB::commit();

            return response()->json([
                'message' => "Moved $deletedCount students to trash",
                'deletedCount' => $deletedCount,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Deletion failed',
                'error' => $e->getMessage()
            ], 500);
        }

        
    
    
        
    }

    public function search(Request $request)
    {
        $request->validate([
            'student_id' => 'nullable|string|max:100',
        ]);
        
        $studentId = $request->input('student_id');
        
        if (empty($studentId)) {
            return response()->json(['data' => [], 'total' => 0]);
        }
        
        $students = Students::where('student_id', 'like', "%{$studentId}%")
                          ->orderBy('last_name')
                          ->limit(50)
                          ->get();
        
        return response()->json(['data' => $students, 'total' => $students->count()]);
    }

    // Add this method inside StudentController
public function dashboard()
{
    // Total number of students
    $totalStudents = Students::count();

    // Students grouped by course
    $studentsByCourse = Students::select('course', DB::raw('count(*) as total'))
        ->groupBy('course')
        ->get();

    // Students grouped by college
    $studentsByCollege = Students::select('college', DB::raw('count(*) as total'))
        ->groupBy('college')
        ->get();

    // Students grouped by campus
    $studentsByCampus = Students::select('campus', DB::raw('count(*) as total'))
        ->groupBy('campus')
        ->get();

    // Gender distribution
    $genderDistribution = Students::select('gender', DB::raw('count(*) as total'))
        ->groupBy('gender')
        ->get();

    // Year level distribution
    $yearLevelDistribution = Students::select('year_level', DB::raw('count(*) as total'))
        ->groupBy('year_level')
        ->get();

     $studentScholar = Students::select('scholar_ship', DB::raw('count(*) as total'))
        ->groupBy('scholar_ship')
        ->get();

    // Student status (active/inactive)
    $studentStatus = Students::select('student_status', DB::raw('count(*) as total'))
        ->groupBy('student_status')
        ->get();

    // Approval status (normalize 'approved' values)
    $approvalStatus = Students::select(
        DB::raw("CASE 
            WHEN LOWER(approved) IN ('yes', '1') THEN 'yes' 
            WHEN LOWER(approved) IN ('no', '0') THEN 'no' 
            ELSE LOWER(approved) 
        END as approval_status"),
        DB::raw('count(*) as total')
    )->groupBy('approval_status')
     ->get();

    return response()->json([
        'total_students' => $totalStudents,
        'students_by_course' => $studentsByCourse,
        'students_by_college' => $studentsByCollege,
        'students_by_campus' => $studentsByCampus,
        'gender_distribution' => $genderDistribution,
        'year_level_distribution' => $yearLevelDistribution,
        'student_status' => $studentStatus,
        'approval_status' => $approvalStatus,
        'student_scholarship' => $studentScholar,
    ]);
}

public function trash(Request $request)
{
    $trashed = Students::onlyTrashed()
        ->paginate($request->input('limit', 10));
        
    return response()->json([
        'data' => $trashed->items(),
        'page' => $trashed->currentPage(),
        'pages' => $trashed->lastPage(),
        'total' => $trashed->total(),
    ]);
}

public function restore($id)
{
    $student = Students::withTrashed()->findOrFail($id);
    $student->restore();
    return response()->json(['message' => 'Student restored successfully']);
}

public function forceDelete($id)
{
    $student = Students::withTrashed()->findOrFail($id);
    $student->forceDelete();
    return response()->json(null, 204);
}


}