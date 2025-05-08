<?php

namespace App\Http\Controllers;


use App\Models\YearLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class YearLevelController extends Controller
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
            $rules['name'][] = \Illuminate\Validation\Rule::unique('year_levels')->ignore($id);
        } else {
            $rules['name'][] = 'unique:year_levels';
        }

        return $rules;
    }

    /**
     * Display a listing of year_levels.
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
        
        $query = YearLevel::query();
        
        if ($request->filled('search')) {
            $query->where('name', 'LIKE', '%' . $request->input('search') . '%');
        }
        
        if ($limit == -1) {
            $year_levels = $query->get();
            return response()->json([
                'data' => $year_levels,
                'page' => 1,
                'pages' => 1,
                'total' => $year_levels->count(),
            ]);
        } else {
            $year_levels = $query->paginate($limit, ['*'], 'page', $page);
            return response()->json([
                'data' => $year_levels->items(),
                'page' => $year_levels->currentPage(),
                'pages' => $year_levels->lastPage(),
                'total' => $year_levels->total(),
            ]);
        }
    }

    /**
     * Store a newly created yearlevels.
     */
    public function store(Request $request)
    {
        $validated = $request->validate($this->getValidationRules());
        
        $yearlevel = YearLevel::create($validated);
        
        return response()->json($yearlevel, 201);
    }

    /**
     * Display the specified yearlevels.
     */
    public function show($id)
    {
        $yearlevel = YearLevel::findOrFail($id);
        return response()->json($yearlevel);
    }

    /**
     * Update the specified yearlevel.
     */
    public function update(Request $request, $id)
    {
        $yearlevel = YearLevel::findOrFail($id);
        
        $validated = $request->validate($this->getValidationRules($id));
        
        $yearlevel->update($validated);
        
        return response()->json($yearlevel);
    }

    /**
     * Remove the yearlevel to trash.
     */
    public function destroy($id)
    {
        $yearlevel = YearLevel::findOrFail($id);
        $yearlevel->delete();
        return response()->json(['message' => 'Year level moved to trash']);
    }

    /**
     * Get all yearlevels for dropdown.
     */
    public function getAll()
    {
        $year_levels = YearLevel::where('active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
            
        return response()->json($year_levels);
    }

    /**
     * Display trashed yearlevel.
     */
    public function trash(Request $request)
    {
        $trashed = YearLevel::onlyTrashed()
            ->paginate($request->input('limit', 10));
            
        return response()->json([
            'data' => $trashed->items(),
            'page' => $trashed->currentPage(),
            'pages' => $trashed->lastPage(),
            'total' => $trashed->total(),
        ]);
    }

    /**
     * Restore a trashed yearlevel.
     */
    public function restore($id)
    {
        $yearlevel = YearLevel::withTrashed()->findOrFail($id);
        $yearlevel->restore();
        return response()->json(['message' => 'Year Level restored successfully']);
    }

    /**
     * Permanently delete a yearlevel.
     */
    public function forceDelete($id)
    {
        $yearlevel = YearLevel::withTrashed()->findOrFail($id);
        
        // Check if any students are using this yearlevel
        $studentsCount = DB::table('students')
            ->where('year_level', $yearlevel->name)
            ->count();
            
        if ($studentsCount > 0) {
            return response()->json([
                'message' => 'Cannot delete yearlevel that is being used by students',
                'students_count' => $studentsCount
            ], 422);
        }
        
        $yearlevel->forceDelete();
        return response()->json(null, 204);
    }
}