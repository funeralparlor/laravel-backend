<?php

use App\Http\Controllers\StudentController;

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CollegeController;
use App\Http\Controllers\UserController;





// Public routes
Route::get('/students/dashboard', [StudentController::class, 'dashboard']);

Route::get('students', [StudentController::class, 'index']);
Route::get('students/{id}', [StudentController::class, 'show']);
Route::get('/api/students/search', [StudentController::class, 'search']);

Route::post('students', [StudentController::class, 'store']);
Route::put('students/{id}', [StudentController::class, 'update']);
Route::delete('students/{id}', [StudentController::class, 'destroy']);
Route::post('/students/import', [StudentController::class, 'import']);
Route::delete('/students/bulk', [StudentController::class, 'bulkDelete']);
Route::post('/students/bulk-delete', [StudentController::class, 'bulkDelete']);

//



//AUTH
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
 
    Route::post('/logout', [AuthController::class, 'logout']);

   
});


Route::middleware('auth:sanctum')->group(function () {
    Route::put('/user/profile', [UserController::class, 'updateProfile']);
    Route::put('/user/password', [UserController::class, 'updatePassword']);
});

//Course
Route::get('/colleges/{college}/courses', [CollegeController::class, 'getCoursesByCollege']);

  
