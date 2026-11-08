<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

// Local prototype only: add auth and authorization before deployment.
Route::get('/', DashboardController::class)->name('dashboard');
Route::resource('students', StudentController::class)->except('show');
