<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ConfigController;
use App\Http\Controllers\WorkHourController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\AttendanceController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//Route::get('/login', [UserController::class, 'login'])->name('login');
//Route::middleware(['auth:custom_user', 'role:Admin,User'])->get('/user', [UserController::class, 'index']);//->middleware('role:admin');
//Route::middleware(['auth:custom_user', 'role:User'])->get('/user/detail', [UserController::class, 'index']);//->middleware('role:user');

Route::post  ('/login', [UserController::class, 'login']);
Route::get   ('/profile', [UserController::class, 'profile'])->middleware('role');

Route::get   ('/user', [UserController::class, 'index'])->middleware('role:Admin');
Route::get   ('/user/{id}', [UserController::class, 'detail'])->middleware('role:Admin');
Route::post  ('/user', [UserController::class, 'create'])->middleware('role:Admin');
Route::put   ('/user/{id}', [UserController::class, 'update'])->middleware('role:Admin');
Route::delete('/user/{id}', [UserController::class, 'delete'])->middleware('role:Admin');

Route::get   ('/config', [ConfigController::class, 'index'])->middleware('role:Admin');
Route::put   ('/config', [ConfigController::class, 'update'])->middleware('role:Admin');

Route::get   ('/work_hour', [WorkHourController::class, 'index'])->middleware('role:Admin,User');
Route::get   ('/work_hour/{id}', [WorkHourController::class, 'detail'])->middleware('role:Admin');
Route::post  ('/work_hour', [WorkHourController::class, 'create'])->middleware('role:Admin');
Route::put   ('/work_hour/{id}', [WorkHourController::class, 'update'])->middleware('role:Admin');
Route::delete('/work_hour/{id}', [WorkHourController::class, 'delete'])->middleware('role:Admin');

Route::get   ('/department', [DepartmentController::class, 'index'])->middleware('role:Admin');
Route::get   ('/department/{id}', [DepartmentController::class, 'detail'])->middleware('role:Admin');
Route::post  ('/department', [DepartmentController::class, 'create'])->middleware('role:Admin');
Route::put   ('/department/{id}', [DepartmentController::class, 'update'])->middleware('role:Admin');
Route::delete('/department/{id}', [DepartmentController::class, 'delete'])->middleware('role:Admin');

Route::get   ('/employee', [EmployeeController::class, 'index'])->middleware('role:Admin');
Route::get   ('/employee/{id}', [EmployeeController::class, 'detail'])->middleware('role:Admin');
Route::post  ('/employee', [EmployeeController::class, 'create'])->middleware('role:Admin');
Route::put   ('/employee/{id}', [EmployeeController::class, 'update'])->middleware('role:Admin');
Route::delete('/employee/{id}', [EmployeeController::class, 'delete'])->middleware('role:Admin');
Route::post  ('/employee/import', [EmployeeController::class, 'import'])->middleware('role:Admin');

Route::get   ('/attendance', [AttendanceController::class, 'index'])->middleware('role');
Route::get   ('/attendance/{id}', [AttendanceController::class, 'detail'])->middleware('role');
Route::post  ('/attendance', [AttendanceController::class, 'tap'])->middleware('role:User');
Route::delete('/attendance/{id}', [AttendanceController::class, 'delete'])->middleware('role:Admin');