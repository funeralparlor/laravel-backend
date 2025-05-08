<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    /**
     * Display a listing of courses.
     */
    public function index(Request $request)
    {
        $request->validate([
            'page' => 'nullable|integer|min:1',
            'limit' => 'nullable|integer|min:1',
            'search' => 'nullable|string|max:100',
            'college_id' => 'nullable|integer|exists:colleges,id',
        ]);

        $page = $request->query('page', 1);
        $limit = $request->input('limit', 10);
        
        $query = Course::with('college');
        
        if ($request->filled('search')) {
            $query->where('name', 'LIKE', '%' . $request->input('search') . '%');
        }
        
        if ($request->filled('college_id')) {
            $query->where('college_id', $request->input('college_id'));
        }
        
        if ($limit == -1) {
            $courses = $query->get();
            return response()->json([
                'data' => $courses,
                'page' => 1,
                'pages' => 1,
                'total' => $courses->count(),
            ]);
        } else {
            $courses = $query->paginate($limit, ['*'], 'page', $page);
            return response()->json([
                'data' => $courses->items(),
                'page' => $courses->currentPage(),
                'pages' => $courses->lastPage(),
                'total' => $courses->total(),
            ]);
        }
    }

    /**
     * Display the specified course.
     */
    public function show($id)
    {
        $course = Course::with('college')->findOrFail($id);
        return response()->json($course);
    }

    /**
     * Get courses by college.
     */
    public function getByCollege($collegeId)
    {
        $courses = Course::where('college_id', $collegeId)
            ->where('active', true)
            ->orderBy('name')
            ->get();
            
        return response()->json($courses);
    }

    /**
     * Get all courses for dropdown.
     */
    public function getAll()
    {
        $courses = Course::with('college:id,name')
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'college_id']);
            
        return response()->json($courses);
    }

    /**
     * Display trashed courses.
     */
    public function trash(Request $request)
    {
        $trashed = Course::with('college')
            ->onlyTrashed()
            ->paginate($request->input('limit', 10));
            
        return response()->json([
            'data' => $trashed->items(),
            'page' => $trashed->currentPage(),
            'pages' => $trashed->lastPage(),
            'total' => $trashed->total(),
        ]);
    }
}