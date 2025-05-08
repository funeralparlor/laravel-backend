<?php

namespace App\Http\Controllers;

use App\Models\Scholarship;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ScholarshipController extends Controller
{
    /**
     * Get validation rules
     */
    private function getValidationRules($id = null)
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'active' => ['boolean']
        ];

        if ($id) {
            $rules['name'][] = \Illuminate\Validation\Rule::unique('scholarships')->ignore($id);
        } else {
            $rules['name'][] = 'unique:scholarships';
        }

        return $rules;
    }

    /**
     * Display a listing of scholarships.
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
        
        $query = Scholarship::query();
        
        if ($request->filled('search')) {
            $query->where('name', 'LIKE', '%' . $request->input('search') . '%');
        }
        
        if ($limit == -1) {
            $scholarships = $query->get();
            return response()->json([
                'data' => $scholarships,
                'page' => 1,
                'pages' => 1,
                'total' => $scholarships->count(),
            ]);
        } else {
            $scholarships = $query->paginate($limit, ['*'], 'page', $page);
            return response()->json([
                'data' => $scholarships->items(),
                'page' => $scholarships->currentPage(),
                'pages' => $scholarships->lastPage(),
                'total' => $scholarships->total(),
            ]);
        }
    }

    /**
     * Store a newly created scholarship.
     */
    public function store(Request $request)
    {
        $validated = $request->validate($this->getValidationRules());
        
        $scholarship = Scholarship::create($validated);
        
        return response()->json($scholarship, 201);
    }

    /**
     * Display the specified scholarship.
     */
    public function show($id)
    {
        $scholarship = Scholarship::findOrFail($id);
        return response()->json($scholarship);
    }

    /**
     * Update the specified scholarship.
     */
    public function update(Request $request, $id)
    {
        $scholarship = Scholarship::findOrFail($id);
        
        $validated = $request->validate($this->getValidationRules($id));
        
        $scholarship->update($validated);
        
        return response()->json($scholarship);
    }

    /**
     * Remove the scholarship to trash.
     */
    public function destroy($id)
    {
        $scholarship = Scholarship::findOrFail($id);
        $scholarship->delete();
        return response()->json(['message' => 'Scholarship moved to trash']);
    }

    /**
     * Get all scholarships for dropdown.
     */
    public function getAll()
    {
        $scholarships = Scholarship::where('active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
            
        return response()->json($scholarships);
    }

    /**
     * Display trashed scholarships.
     */
    public function trash(Request $request)
    {
        $trashed = Scholarship::onlyTrashed()
            ->paginate($request->input('limit', 10));
            
        return response()->json([
            'data' => $trashed->items(),
            'page' => $trashed->currentPage(),
            'pages' => $trashed->lastPage(),
            'total' => $trashed->total(),
        ]);
    }

    /**
     * Restore a trashed scholarship.
     */
    public function restore($id)
    {
        $scholarship = Scholarship::withTrashed()->findOrFail($id);
        $scholarship->restore();
        return response()->json(['message' => 'Scholarship restored successfully']);
    }

    /**
     * Permanently delete a scholarship.
     */
    public function forceDelete($id)
    {
        $scholarship = Scholarship::withTrashed()->findOrFail($id);
        
        // Check if any students are using this scholarship
        $studentsCount = DB::table('students')
            ->where('scholar_ship', $scholarship->name)
            ->count();
            
        if ($studentsCount > 0) {
            return response()->json([
                'message' => 'Cannot delete scholarship that is being used by students',
                'students_count' => $studentsCount
            ], 422);
        }
        
        $scholarship->forceDelete();
        return response()->json(null, 204);
    }
}