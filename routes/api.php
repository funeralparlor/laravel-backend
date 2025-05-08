<?php

use App\Http\Controllers\StudentController;

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CollegeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ScholarshipController;
use App\Http\Controllers\YearLevelController;
use App\Http\Controllers\CampusController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\PSGCController;
use Illuminate\Http\Request;






// Public routes
Route::get('/students/dashboard', [StudentController::class, 'dashboard']);

Route::get('students', [StudentController::class, 'index']);
Route::get('students/{id}', [StudentController::class, 'show']);
Route::get('/api/students/search', [StudentController::class, 'search']);
Route::get('/students/export', [StudentController::class, 'export']); // Export route
Route::get('/api/students/template', [StudentController::class, 'template']); // Added template route

Route::post('students', [StudentController::class, 'store']);
Route::put('students/{id}', [StudentController::class, 'update']);
Route::delete('students/{id}', [StudentController::class, 'destroy']);
Route::post('/students/import', [StudentController::class, 'import']);
Route::delete('/students/bulk', [StudentController::class, 'bulkDelete']);
Route::post('/students/bulk-delete', [StudentController::class, 'bulkDelete']);




    Route::get('/api/students/trash', [StudentController::class, 'trash']);
    Route::post('students/{id}/restore', [StudentController::class, 'restore']);
    Route::delete('students/{id}/force', [StudentController::class, 'forceDelete']);


//AUTH
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    Route::post('/logout', [AuthController::class, 'logout']);
});


Route::middleware('auth:sanctum')->group(function () {
    Route::put('/user/profile', [UserController::class, 'updateProfile']);
    Route::put('/user/password', [UserController::class, 'updatePassword']);

    
});

//Scholarship
// Scholarship routes
Route::apiResource('scholarships', ScholarshipController::class);
Route::get('scholarships-all', [ScholarshipController::class, 'getAll']);
Route::get('scholarships-trash', [ScholarshipController::class, 'trash']);
Route::post('scholarships-restore/{id}', [ScholarshipController::class, 'restore']);
Route::delete('scholarships-force-delete/{id}', [ScholarshipController::class, 'forceDelete']);

// Campus routes
Route::apiResource('campuses', CampusController::class);
Route::get('campuses-all', [CampusController::class, 'getAll']);
Route::get('campuses-trash', [CampusController::class, 'trash']);
Route::post('campuses-restore/{id}', [CampusController::class, 'restore']);
Route::delete('campuses-force-delete/{id}', [CampusController::class, 'forceDelete']);

// year level routes
Route::apiResource('year_levels', YearLevelController::class);
Route::get('year_levels-all', [YearLevelController::class, 'getAll']);
Route::get('year_levels-trash', [YearLevelController::class, 'trash']);
Route::post('year_levels-restore/{id}', [YearLevelController::class, 'restore']);
Route::delete('year_levels-force-delete/{id}', [YearLevelController::class, 'forceDelete']);

// College routes
Route::Resource('colleges', CollegeController::class);
Route::get('colleges-all', [CollegeController::class, 'getAll']);
Route::get('colleges-trash', [CollegeController::class, 'trash']);
Route::post('colleges-restore/{id}', [CollegeController::class, 'restore']);
Route::delete('colleges-force-delete/{id}', [CollegeController::class, 'forceDelete']);

 // Course routes
 Route::get('/courses', [CourseController::class, 'index']);
 Route::get('/colleges/{collegeId}/courses', [CourseController::class, 'getByCollege']);
 Route::get('/courses/{id}', [CourseController::class, 'show']);
 Route::get('/courses-all', [CourseController::class, 'getAll']);
 Route::get('/courses-trash', [CourseController::class, 'trash']);

// psgc
Route::prefix('psgc')->group(function () {
    Route::get('/provinces', [PSGCController::class, 'getProvinces']);
    Route::get('/cities/{provinceCode}', [PSGCController::class, 'getCitiesByProvince']);
    Route::get('/barangays/{cityCode}', [PSGCController::class, 'getBarangaysByCity']);
});