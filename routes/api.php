<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\admin\{AdminAuthContorller, AdminController};
use App\Http\Controllers\warehouse\WarehouseTypeContorller;

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
    // Update login admin information
    Route::put('/admin/update-profile', [AdminAuthContorller::class, 'updateProfile']);
    // Change login admin password
    Route::put('/admin/change-password', [AdminAuthContorller::class, 'changePassword']);

    // Show all admin list
    Route::get('admin/list', [AdminController::class, 'listAdmins']);
    // Toggle admin status (active/inactive)
    Route::put('admin/status/{id}/toggle', [AdminController::class, 'toggleAdminStatus']);
    // edit admin information
    Route::get('admin/edit/{id}', [AdminController::class, 'editAdmin']);
    // Update admin information
    Route::put('admin/{id}/update', [AdminController::class, 'updateAdminById']);
    // Delete an admin by ID
    Route::delete('admin/{id}/delete', [AdminController::class, 'deleteAdmin']);

    // warehouse Type routes
    // Create warehouse type
    Route::post('admin/warehouse-types', [WarehouseTypeContorller::class, 'createWarehouseType']);
    // shwo all warehouse types
    Route::get('admin/warehouse-types', [WarehouseTypeContorller::class, 'getAllWarehouseTypes']);
    // shwo id wise warehouse types
    Route::get('admin/warehouse-type/{id}', [WarehouseTypeContorller::class, 'getWarehouseTypeById']);
    // update warehouse type
    Route::put('admin/warehouse-type/{id}/update', [WarehouseTypeContorller::class, 'updateWarehouseType']);

});
