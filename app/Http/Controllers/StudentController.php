<?php

namespace App\Http\Controllers;

use App\Models\Students;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

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
            'last_sem' => 'nullable|max:255',
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
            'college' => 'nullable|array',
            'course' => 'nullable|array',
            'campus' => 'nullable|array',
            'student_status' => 'nullable|array',
            'scholar_ship' => 'nullable|array',
            'search' => 'nullable|string|max:100',
        ]);
    
        // Get pagination parameters
        $page = $request->query('page', 1);
        $limit = $request->input('limit', 10);
        
        // Start query
        $query = Students::query();
        
        // Apply filters with consistent approach
        $filterFields = ['year_level', 'college', 'course', 'campus', 'student_status', 'scholar_ship'];
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



 // Define a constant for consistent headers across template and import
 private const EXCEL_HEADERS = [
    'STUDENT NUMBER', 'LAST NAME', 'GIVEN NAME', 'MIDDLE NAME', 'COURSE', 
    'COLLEGE', 'CAMPUS', 'YEAR LEVEL', 'GENDER', 'DATE OF BIRTH', 
    'PLACE OF BIRTH', 'COMPLETE ADDRESS', 'BARANGAY', 'TOWN/CITY', 
    'Province', 'Email', 'MobileNo', 'FatherName', 'Father_Occupation', 
    'MotherName', 'Mother_Occupation', 'Student_Status',
    'Last sem of enrolment for inactive', 'Section', 
    'Approved to share the information', 'Scholarship Type'
];

// Define mappings between Excel headers and database columns
private const COLUMN_MAPPINGS = [
    'STUDENT NUMBER' => 'student_id',
    'LAST NAME' => 'last_name',
    'GIVEN NAME' => 'first_name',
    'MIDDLE NAME' => 'middle_name',
    'COURSE' => 'course',
    'COLLEGE' => 'college',
    'CAMPUS' => 'campus',
    'YEAR LEVEL' => 'year_level',
    'GENDER' => 'gender',
    'DATE OF BIRTH' => 'birthday',
    'PLACE OF BIRTH' => 'birth_place',
    'COMPLETE ADDRESS' => 'comp_address',
    'BARANGAY' => 'barangay',
    'TOWN/CITY' => 'town',
    'Province' => 'province',
    'Email' => 'email',
    'MobileNo' => 'number',
    'FatherName' => 'father_name',
    'Father_Occupation' => 'father_occup',
    'MotherName' => 'mother_name',
    'Mother_Occupation' => 'mother_occup',
    'Student_Status' => 'student_status',
    'Last sem of enrolment for inactive' => 'last_sem',
    'Section' => 'section',
    'Approved to share the information' => 'approved',
    'Scholarship Type' => 'scholar_ship'
];


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
        
        // Verify all required headers exist
        $missingColumns = array_diff(self::EXCEL_HEADERS, $headers);
        if (!empty($missingColumns)) {
            return response()->json([
                'message' => 'The uploaded file is missing required columns',
                'missing_columns' => $missingColumns,
                'tip' => 'Please use the template provided through the /template endpoint to ensure correct format'
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
                // Skip if column doesn't exist in the row
                if (!isset($row[$colIndex])) {
                    continue;
                }
                
                $value = $row[$colIndex];
                // Convert numeric values to strings to avoid type issues
                $rowData[$header] = is_numeric($value) ? (string)$value : $value;
            }

            // Data validation based on the required fields
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
                'Last sem of enrolment for inactive' => 'nullable',
                'Section' => 'required',
                'Approved to share the information' => 'required',
                'Scholarship Type' => 'required'
            ]);

            if ($validator->fails()) {
                $errors["Row " . ($index + 2)] = $validator->errors()->all();
                continue;
            }

            // Map excel headers to database columns
            $studentData = [];
            foreach (self::COLUMN_MAPPINGS as $excelHeader => $dbColumn) {
                $studentData[$dbColumn] = $rowData[$excelHeader] ?? null;
            }

            // Add timestamps
            $studentData['created_at'] = now();
            $studentData['updated_at'] = now();
            
            $students[] = $studentData;
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
        $cacheKey = 'students.dashboard.data';
        $data = Cache::remember($cacheKey, now()->addMinutes(10), function () {
            // Optimized queries using DB facade for reduced Eloquent overhead
            $totalStudents = DB::table('students')->count();
    
            // Batch all groupBy queries using efficient SQL
            $studentsByCourse = DB::table('students')
                ->select('course', DB::raw('count(*) as total'))
                ->groupBy('course')
                ->get();
    
            $studentsByCollege = DB::table('students')
                ->select('college', DB::raw('count(*) as total'))
                ->groupBy('college')
                ->get();
    
            $studentsByCampus = DB::table('students')
                ->select('campus', DB::raw('count(*) as total'))
                ->groupBy('campus')
                ->get();
    
            $genderDistribution = DB::table('students')
                ->select('gender', DB::raw('count(*) as total'))
                ->groupBy('gender')
                ->get();
    
            $yearLevelDistribution = DB::table('students')
                ->select('year_level', DB::raw('count(*) as total'))
                ->groupBy('year_level')
                ->get();
    
            $studentScholar = DB::table('students')
                ->select('scholar_ship', DB::raw('count(*) as total'))
                ->groupBy('scholar_ship')
                ->get();
    
            $studentStatus = DB::table('students')
                ->select('student_status', DB::raw('count(*) as total'))
                ->groupBy('student_status')
                ->get();
    
            // Normalize approval status at query level
            $approvalStatus = DB::table('students')
                ->select(
                    DB::raw("CASE 
                        WHEN LOWER(approved) IN ('yes', '1') THEN 'yes' 
                        WHEN LOWER(approved) IN ('no', '0') THEN 'no' 
                        ELSE LOWER(COALESCE(approved, 'pending')) 
                    END as approval_status"),
                    DB::raw('count(*) as total')
                )
                ->groupBy('approval_status')
                ->get();
    
            return [
                'total_students' => $totalStudents,
                'students_by_course' => $studentsByCourse,
                'students_by_college' => $studentsByCollege,
                'students_by_campus' => $studentsByCampus,
                'gender_distribution' => $genderDistribution,
                'year_level_distribution' => $yearLevelDistribution,
                'student_status' => $studentStatus,
                'approval_status' => $approvalStatus,
                'student_scholarship' => $studentScholar,
            ];
        });
    
        return response()->json($data);
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

/**
 * Export students data to Excel file
 * 
 * @param  \Illuminate\Http\Request  $request
 * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
 */
public function export(Request $request)
{
    // Validate query parameters for filtering
    $request->validate([
        'year_level' => 'nullable|array',
        'college' => 'nullable|array',
        'course' => 'nullable|array',
        'campus' => 'nullable|array',
        'student_status' => 'nullable|array',
        'scholar_ship' => 'nullable|array',
        'search' => 'nullable|string|max:100',
    ]);
    
    // Start query and apply filters
    $query = Students::query();
    
    // Apply the same filters as in the index method
    $filterFields = ['year_level', 'college', 'course', 'campus', 'student_status', 'scholar_ship'];
    foreach ($filterFields as $field) {
        if ($request->has($field) && is_array($request->input($field)) && count($request->input($field)) > 0) {
            $query->whereIn($field, $request->input($field));
        }
    }
    
    // Search by student ID
    if ($request->filled('search')) {
        $query->where('student_id', 'LIKE', '%' . $request->input('search') . '%');
    }
    
    // Get all filtered students
    $students = $query->get();
    
    // Create new spreadsheet
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Set headers
    $headers = [
        'STUDENT NUMBER', 'LAST NAME', 'GIVEN NAME', 'MIDDLE NAME', 'COURSE', 
        'COLLEGE', 'CAMPUS', 'YEAR LEVEL', 'SECTION', 'GENDER', 
        'DATE OF BIRTH', 'PLACE OF BIRTH', 'COMPLETE ADDRESS', 'BARANGAY', 
        'TOWN/CITY', 'Province', 'Email', 'MobileNo', 'FatherName', 
        'Father_Occupation', 'MotherName', 'Mother_Occupation', 'Student_Status',
        'Last sem of enrolment for inactive', 'Approved to share the information', 
        'Scholarship Type'
    ];
    
    // Apply headers to first row
    foreach ($headers as $columnIndex => $header) {
        $sheet->setCellValueByColumnAndRow($columnIndex + 1, 1, $header);
    }
    
    // Style the header row
    $headerRow = $sheet->getStyle('A1:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers)) . '1');
    $headerRow->getFont()->setBold(true);
    $headerRow->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFCCCCCC');
    
    // Add data rows
    $row = 2;
    foreach ($students as $student) {
        $sheet->setCellValueByColumnAndRow(1, $row, $student->student_id);
        $sheet->setCellValueByColumnAndRow(2, $row, $student->last_name);
        $sheet->setCellValueByColumnAndRow(3, $row, $student->first_name);
        $sheet->setCellValueByColumnAndRow(4, $row, $student->middle_name);
        $sheet->setCellValueByColumnAndRow(5, $row, $student->course);
        $sheet->setCellValueByColumnAndRow(6, $row, $student->college);
        $sheet->setCellValueByColumnAndRow(7, $row, $student->campus);
        $sheet->setCellValueByColumnAndRow(8, $row, $student->year_level);
        $sheet->setCellValueByColumnAndRow(9, $row, $student->section);
        $sheet->setCellValueByColumnAndRow(10, $row, $student->gender);
        $sheet->setCellValueByColumnAndRow(11, $row, $student->birthday);
        $sheet->setCellValueByColumnAndRow(12, $row, $student->birth_place);
        $sheet->setCellValueByColumnAndRow(13, $row, $student->comp_address);
        $sheet->setCellValueByColumnAndRow(14, $row, $student->barangay);
        $sheet->setCellValueByColumnAndRow(15, $row, $student->town);
        $sheet->setCellValueByColumnAndRow(16, $row, $student->province);
        $sheet->setCellValueByColumnAndRow(17, $row, $student->email);
        $sheet->setCellValueByColumnAndRow(18, $row, $student->number);
        $sheet->setCellValueByColumnAndRow(19, $row, $student->father_name);
        $sheet->setCellValueByColumnAndRow(20, $row, $student->father_occup);
        $sheet->setCellValueByColumnAndRow(21, $row, $student->mother_name);
        $sheet->setCellValueByColumnAndRow(22, $row, $student->mother_occup);
        $sheet->setCellValueByColumnAndRow(23, $row, $student->student_status);
        $sheet->setCellValueByColumnAndRow(24, $row, $student->last_sem);
        $sheet->setCellValueByColumnAndRow(25, $row, $student->approved);
        $sheet->setCellValueByColumnAndRow(26, $row, $student->scholar_ship);
        $row++;
    }
    
    // Auto size columns for better readability
    foreach (range(1, count($headers)) as $columnIndex) {
        $sheet->getColumnDimensionByColumn($columnIndex)->setAutoSize(true);
    }
    
    // Create Excel writer
    $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
    
    // Create a temporary file
    $tempFile = tempnam(sys_get_temp_dir(), 'students_export_');
    $writer->save($tempFile);
    
    // Generate filename with date
    $fileName = 'students_export_' . date('Y-m-d') . '.xlsx';
    
    // Return the file as download
    return response()->download($tempFile, $fileName, [
        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ])->deleteFileAfterSend(true);
}
/**
     * Generate an Excel template for student import
     * 
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function template()
    {
        // Create new spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Students Import Template');
        
        // Apply headers to first row
        foreach (self::EXCEL_HEADERS as $columnIndex => $header) {
            $columnLetter = Coordinate::stringFromColumnIndex($columnIndex + 1);
            $sheet->setCellValue($columnLetter . '1', $header);
            
            // Add data validation where appropriate
            switch ($header) {
                case 'GENDER':
                    // Add dropdown for gender
                    $validation = $sheet->getCell($columnLetter . '2')->getDataValidation();
                    $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
                    $validation->setAllowBlank(false);
                    $validation->setShowDropDown(true);
                    $validation->setFormula1('"Male,Female,Other"');
                    break;
                    
                case 'Approved to share the information':
                    // Add dropdown for approval
                    $validation = $sheet->getCell($columnLetter . '2')->getDataValidation();
                    $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
                    $validation->setAllowBlank(false);
                    $validation->setShowDropDown(true);
                    $validation->setFormula1('"Yes,No"');
                    break;
                    
                case 'Student_Status':
                    // Add dropdown for student status
                    $validation = $sheet->getCell($columnLetter . '2')->getDataValidation();
                    $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
                    $validation->setAllowBlank(false);
                    $validation->setShowDropDown(true);
                    $validation->setFormula1('"Active,Inactive,Graduated,Leave of Absence"');
                    break;
            }
        }
        
        // Style the header row
        $lastColumn = Coordinate::stringFromColumnIndex(count(self::EXCEL_HEADERS));
        $headerRange = 'A1:' . $lastColumn . '1';
        
        $headerStyle = $sheet->getStyle($headerRange);
        $headerStyle->getFont()->setBold(true);
        $headerStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFCCCCCC');
        
        // Add example data in row 2
        $exampleData = [
            '2023-0001', 'Doe', 'John', 'Michael', 'BSCS', 
            'College of Computing', 'Main Campus', '1st Year', 'Male', '2000-01-01',
            'City Hospital', '123 Main St', 'Central', 'Metro City', 
            'State Province', 'john.doe@example.com', '09123456789', 'Richard Doe',
            'Engineer', 'Mary Doe', 'Teacher', 'Active',
            '', 'A', 'Yes', 'Full Scholarship'
        ];
        
        foreach ($exampleData as $columnIndex => $value) {
            $sheet->setCellValueByColumnAndRow($columnIndex + 1, 2, $value);
        }
        
        // Style the example row differently
        $exampleRange = 'A2:' . $lastColumn . '2';
        $exampleStyle = $sheet->getStyle($exampleRange);
        $exampleStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFEEEEEE');
        $exampleStyle->getFont()->setItalic(true);
        
        // Add formatting hint in row 3
        $sheet->setCellValue('A3', '(Please delete the example row before importing actual data)');
        $sheet->mergeCells('A3:' . $lastColumn . '3');
        $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(10)->getColor()->setARGB('FF888888');
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        
        // Add instructions in row 4
        $sheet->setCellValue('A4', 'Required fields: STUDENT NUMBER, LAST NAME, GIVEN NAME, COURSE, COLLEGE, CAMPUS, YEAR LEVEL, GENDER, DATE OF BIRTH, PLACE OF BIRTH, BARANGAY, TOWN/CITY, Province, Email, FatherName, Father_Occupation, MotherName, Mother_Occupation, Student_Status, Section, Approved to share the information, Scholarship Type');
        $sheet->mergeCells('A4:' . $lastColumn . '4');
        $sheet->getStyle('A4')->getFont()->setBold(true)->setSize(10)->getColor()->setARGB('FF0000FF');
        $sheet->getStyle('A4')->getAlignment()->setWrapText(true);
        $sheet->getRowDimension(4)->setRowHeight(30);
        
        // Auto size columns for better readability
        foreach (range(1, count(self::EXCEL_HEADERS)) as $columnIndex) {
            $sheet->getColumnDimensionByColumn($columnIndex)->setAutoSize(true);
        }
        
        // Add column formatting
        $sheet->getStyle('J2:J1000')->getNumberFormat()->setFormatCode('yyyy-mm-dd'); // Date of birth
        
        // Create Excel writer
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        
        // Create a temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'students_template_');
        $writer->save($tempFile);
        
        // Return the file as download
        return response()->download($tempFile, 'students_import_template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}