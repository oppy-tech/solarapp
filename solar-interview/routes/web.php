<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// SolarAPP+ Interview Routes
use App\Http\Controllers\Ahj\DashboardController;
Route::get('/', [DashboardController::class, 'index']);
