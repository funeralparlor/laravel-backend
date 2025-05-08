<?php

namespace App\Http\Controllers;

use App\Models\College;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class CollegeController extends Controller
{
    /**
     * Get validation rules for college
     */
    private function getCollegeValidationRules($id = null)
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'active' => ['boolean'],
            'courses' => ['sometimes', 'array']
        ];

        if ($id) {
            $rules['name'][] = \Illuminate\Validation\Rule::unique('colleges')->ignore($id);
        } else {
            $rules['name'][] = 'unique:colleges';
        }

        return $rules;
    }

    /**
     * Get validation rules for course
     */
    private function getCourseValidationRules()
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'active' => ['boolean']
        ];
    }

    /**
     * Display a listing of colleges with their courses.
     */
    public function index(Request $request)
    {
        $request->validate([
            'page' => 'nullable|integer|min:1',
            'limit' => 'nullable|integer|min:1',
            'search' => 'nullable|string|max:100',
        ]);

        $page = $request->query('page', 1);
        $limit = $request->input('limit', 10);
        
        $query = College::with('courses');
        
        if ($request->filled('search')) {
            $query->where('name', 'LIKE', '%' . $request->input('search') . '%');
        }
        
        if ($limit == -1) {
            $colleges = $query->get();
            return response()->json([
                'data' => $colleges,
                'page' => 1,
                'pages' => 1,
                'total' => $colleges->count(),
            ]);
        } else {
            $colleges = $query->paginate($limit, ['*'], 'page', $page);
            return response()->json([
                'data' => $colleges->items(),
                'page' => $colleges->currentPage(),
                'pages' => $colleges->lastPage(),
                'total' => $colleges->total(),
            ]);
        }
    }

    /**
     * Store a newly created college with courses.
     */
    public function store(Request $request)
    {
        // Validate college data
        $validated = $request->validate($this->getCollegeValidationRules());
        
        // Start a database transaction
        DB::beginTransaction();
        
        try {
            // Create the college
            $college = College::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'active' => $validated['active'] ?? true
            ]);
            
            // Process courses if provided
            if (isset($validated['courses']) && is_array($validated['courses'])) {
                foreach ($validated['courses'] as $courseData) {
                    // Validate each course
                    $courseValidator = Validator::make($courseData, $this->getCourseValidationRules());
                    
                    if ($courseValidator->fails()) {
                        DB::rollBack();
                        return response()->json([
                            'message' => 'Validation failed for course data',
                            'errors' => $courseValidator->errors()
                        ], 422);
                    }
                    
                    // Create course associated with this college
                    $college->courses()->create([
                        'name' => $courseData['name'],
                        'description' => $courseData['description'] ?? null,
                        'active' => $courseData['active'] ?? true
                    ]);
                }
            }
            
            // Commit the transaction
            DB::commit();
            
            // Return the college with its courses
            return response()->json($college->load('courses'), 201);
            
        } catch (\Exception $e) {
            // Roll back the transaction in case of an error
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create college with courses',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified college with its courses.
     */
    public function show($id)
    {
        $college = College::with('courses')->findOrFail($id);
        return response()->json($college);
    }

    /**
     * Update the specified college and its courses.
     */
    public function update(Request $request, $id)
    {
        // Find the college
        $college = College::findOrFail($id);
        
        // Validate college data
        $validated = $request->validate($this->getCollegeValidationRules($id));
        
        // Start a database transaction
        DB::beginTransaction();
        
        try {
            // Update the college
            $college->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'active' => $validated['active'] ?? true
            ]);
            
            // Process courses if provided
            if (isset($validated['courses']) && is_array($validated['courses'])) {
                // Get current course IDs
                $existingCourseIds = $college->courses->pluck('id')->toArray();
                $updatedCourseIds = [];
                
                foreach ($validated['courses'] as $courseData) {
                    // Validate each course
                    $courseValidator = Validator::make($courseData, $this->getCourseValidationRules());
                    
                    if ($courseValidator->fails()) {
                        DB::rollBack();
                        return response()->json([
                            'message' => 'Validation failed for course data',
                            'errors' => $courseValidator->errors()
                        ], 422);
                    }
                    
                    // Update existing course or create new one
                    if (isset($courseData['id']) && in_array($courseData['id'], $existingCourseIds)) {
                        // Update existing course
                        $course = Course::findOrFail($courseData['id']);
                        $course->update([
                            'name' => $courseData['name'],
                            'description' => $courseData['description'] ?? null,
                            'active' => $courseData['active'] ?? true
                        ]);
                        
                        $updatedCourseIds[] = $course->id;
                    } else {
                        // Create new course
                        $course = $college->courses()->create([
                            'name' => $courseData['name'],
                            'description' => $courseData['description'] ?? null,
                            'active' => $courseData['active'] ?? true
                        ]);
                        
                        $updatedCourseIds[] = $course->id;
                    }
                }
                
                // Delete courses that weren't updated (soft delete)
                $coursesToDelete = array_diff($existingCourseIds, $updatedCourseIds);
                if (!empty($coursesToDelete)) {
                    Course::whereIn('id', $coursesToDelete)->delete();
                }
            } else {
                // If no courses provided, delete all existing courses
                $college->courses()->delete();
            }
            
            // Commit the transaction
            DB::commit();
            
            // Return the updated college with its courses
            return response()->json($college->fresh()->load('courses'));
            
        } catch (\Exception $e) {
            // Roll back the transaction in case of an error
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update college with courses',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the college and its courses to trash.
     */
    public function destroy($id)
    {
        $college = College::findOrFail($id);
        
        // Start a database transaction
        DB::beginTransaction();
        
        try {
            // Soft delete the college (which also cascade soft deletes courses due to foreign key)
            $college->delete();
            
            // Commit the transaction
            DB::commit();
            
            return response()->json(['message' => 'College and its courses moved to trash']);
            
        } catch (\Exception $e) {
            // Roll back the transaction in case of an error
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to delete college',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all colleges for dropdown (active only).
     */
    public function getAll()
    {
        $colleges = College::with(['courses' => function($query) {
            $query->where('active', true)->select('id', 'college_id', 'name');
        }])
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'description']);
            
        return response()->json($colleges);
    }

    /**
     * Display trashed colleges.
     */
    public function trash(Request $request)
    {
        $trashed = College::with(['courses' => function($query) {
            $query->withTrashed();
        }])
            ->onlyTrashed()
            ->paginate($request->input('limit', 10));
            
        return response()->json([
            'data' => $trashed->items(),
            'page' => $trashed->currentPage(),
            'pages' => $trashed->lastPage(),
            'total' => $trashed->total(),
        ]);
    }

    /**
     * Restore a trashed college and its courses.
     */
    public function restore($id)
    {
        $college = College::withTrashed()->findOrFail($id);
        
        // Start a database transaction
        DB::beginTransaction();
        
        try {
            // Restore the college
            $college->restore();
            
            // Restore all associated courses
            Course::withTrashed()
                ->where('college_id', $id)
                ->restore();
            
            // Commit the transaction
            DB::commit();
            
            return response()->json(['message' => 'College and its courses restored successfully']);
            
        } catch (\Exception $e) {
            // Roll back the transaction in case of an error
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to restore college',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Permanently delete a college and its courses.
     */
    public function forceDelete($id)
    {
        $college = College::withTrashed()->findOrFail($id);
        
        // Check if any students are using this college
        $studentsCount = DB::table('students')
            ->where('college_id', $college->id)
            ->count();
            
        if ($studentsCount > 0) {
            return response()->json([
                'message' => 'Cannot delete college that is being used by students',
                'students_count' => $studentsCount
            ], 422);
        }
        
        // Start a database transaction
        DB::beginTransaction();
        
        try {
            // Force delete all courses first
            Course::withTrashed()
                ->where('college_id', $id)
                ->forceDelete();
            
            // Force delete the college
            $college->forceDelete();
            
            // Commit the transaction
            DB::commit();
            
            return response()->json(null, 204);
            
        } catch (\Exception $e) {
            // Roll back the transaction in case of an error
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to permanently delete college',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}