<?php

namespace App\Http\Controllers;

use App\Models\Campus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class CampusController extends Controller
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
            $rules['name'][] = \Illuminate\Validation\Rule::unique('campuses')->ignore($id);
        } else {
            $rules['name'][] = 'unique:campuses';
        }

        return $rules;
    }

    /**
     * Display a listing of campuses.
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
        
        $query = Campus::query();
        
        if ($request->filled('search')) {
            $query->where('name', 'LIKE', '%' . $request->input('search') . '%');
        }
        
        if ($limit == -1) {
            $campuses = $query->get();
            return response()->json([
                'data' => $campuses,
                'page' => 1,
                'pages' => 1,
                'total' => $campuses->count(),
            ]);
        } else {
            $campuses = $query->paginate($limit, ['*'], 'page', $page);
            return response()->json([
                'data' => $campuses->items(),
                'page' => $campuses->currentPage(),
                'pages' => $campuses->lastPage(),
                'total' => $campuses->total(),
            ]);
        }
    }

    /**
     * Store a newly created campus.
     */
    public function store(Request $request)
    {
        $validated = $request->validate($this->getValidationRules());
        
        $campus = Campus::create($validated);
        
        return response()->json($campus, 201);
    }

    /**
     * Display the specified campus.
     */
    public function show($id)
    {
        $campus = Campus::findOrFail($id);
        return response()->json($campus);
    }

    /**
     * Update the specified campus.
     */
    public function update(Request $request, $id)
    {
        $campus = Campus::findOrFail($id);
        
        $validated = $request->validate($this->getValidationRules($id));
        
        $campus->update($validated);
        
        return response()->json($campus);
    }

    /**
     * Remove the campus to trash.
     */
    public function destroy($id)
    {
        $campus = Campus::findOrFail($id);
        $campus->delete();
        return response()->json(['message' => 'Campus moved to trash']);
    }

    /**
     * Get all campuses for dropdown.
     */
    public function getAll()
    {
        $campuses = Campus::where('active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
            
        return response()->json($campuses);
    }

    /**
     * Display trashed campuses.
     */
    public function trash(Request $request)
    {
        $trashed = Campus::onlyTrashed()
            ->paginate($request->input('limit', 10));
            
        return response()->json([
            'data' => $trashed->items(),
            'page' => $trashed->currentPage(),
            'pages' => $trashed->lastPage(),
            'total' => $trashed->total(),
        ]);
    }

    /**
     * Restore a trashed campus.
     */
    public function restore($id)
    {
        $campus = Campus::withTrashed()->findOrFail($id);
        $campus->restore();
        return response()->json(['message' => 'Campus restored successfully']);
    }

    /**
     * Permanently delete a campus.
     */
    public function forceDelete($id)
    {
        $campus = Campus::withTrashed()->findOrFail($id);
        
        // Check if any students are using this campus
        $studentsCount = DB::table('students')
            ->where('scholar_ship', $campus->name)
            ->count();
            
        if ($studentsCount > 0) {
            return response()->json([
                'message' => 'Cannot delete campus that is being used by students',
                'students_count' => $studentsCount
            ], 422);
        }
        
        $campus->forceDelete();
        return response()->json(null, 204);
    }
}