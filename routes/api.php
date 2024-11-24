<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\admin\AdminAuthContorller;

// Admin Routes
// Admin registration
Route::post('/register/admin', [AdminAuthContorller::class, 'register']);
// Admin login
Route::post('/admin/login', [AdminAuthContorller::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    // show login admin information
    Route::get('admin/info', [AdminAuthContorller::class, 'showAdminInfo']);
    // Show all admin list
    Route::get('admin/list', [AdminAuthContorller::class, 'listAdmins']);
});
