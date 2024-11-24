<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\admin\AdminAuthContorller;
use App\Http\Controllers\admin\AdminController;

// Admin Routes
// Admin registration
Route::post('/register/admin', [AdminAuthContorller::class, 'register']);
// Admin login
Route::post('/admin/login', [AdminAuthContorller::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    // Logout admin
    Route::post('/admin/logout', [AdminAuthContorller::class, 'logout']);
    // show login admin information
    Route::get('admin/info', [AdminController::class, 'showAdminInfo']);
    // Show all admin list
    Route::get('admin/list', [AdminController::class, 'listAdmins']);
});
