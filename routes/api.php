<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\admin\{AdminAuthContorller, AdminController};
use App\Http\Controllers\warehouse\{WarehouseTypeContorller, WarehouseContorller};
use App\Http\Controllers\grid\GridController;
use App\Http\Controllers\user\UserController;
use App\Http\Controllers\request\RequestController;
use App\Http\Controllers\order\OrderController;
use App\Http\Controllers\RequestFile\RequestFileController;
use App\Http\Controllers\payment\PaymentController;

// Admin Routes
// Admin registration
Route::post('/register/admin', [AdminAuthContorller::class, 'register']);
// Admin login
Route::post('/admin/login', [AdminAuthContorller::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    // Logout admin
    Route::post('/admin/logout', [AdminAuthContorller::class, 'logout']);

    // show login admin information
    Route::get('/admin/info', [AdminController::class, 'showAdminInfo']);
    // Update login admin information
    Route::put('/admin/update-profile', [AdminAuthContorller::class, 'updateProfile']);
    // Change login admin password
    Route::put('/admin/change-password', [AdminAuthContorller::class, 'changePassword']);

    // Show all admin list
    Route::get('/admin/list', [AdminController::class, 'listAdmins']);
    // Toggle admin status (active/inactive)
    Route::put('/admin/status/{id}/toggle', [AdminController::class, 'toggleAdminStatus']);
    // edit admin information
    Route::get('/admin/edit/{id}', [AdminController::class, 'editAdmin']);
    // Update admin information
    Route::put('/admin/{id}/update', [AdminController::class, 'updateAdminById']);
    // Delete an admin by ID
    Route::delete('admin/{id}/delete', [AdminController::class, 'deleteAdmin']);

    // warehouse Type routes
    // Create warehouse type
    Route::post('/admin/warehouse-types', [WarehouseTypeContorller::class, 'createWarehouseType']);
    // shwo all warehouse types
    Route::get('/admin/warehouse-types', [WarehouseTypeContorller::class, 'getAllWarehouseTypes']);
    // shwo id wise warehouse types
    Route::get('/admin/warehouse-types/{id}', [WarehouseTypeContorller::class, 'getWarehouseTypeById']);
    // update warehouse type
    Route::put('/admin/warehouse-types/{id}/update', [WarehouseTypeContorller::class, 'updateWarehouseType']);
    // Delete a warehouse type
    Route::delete('/admin/warehouse-types/{id}/delete', [WarehouseTypeContorller::class, 'deleteWarehouseType']);

    // Warehouse routes
    // Create warehouse
    Route::post('/warehouses', [WarehouseContorller::class, 'storeWarehouse']);
    // show all warehouse
    Route::get('/warehouses', [WarehouseContorller::class, 'showWarehouse']);
    // shwo warehouse by id
    Route::get('/warehouses/{id}', [WarehouseContorller::class, 'showWarehouseById']);
    // change status
    Route::put('/warehouses/{id}/toggle-active', [WarehouseContorller::class, 'toggleIsActive']);
    // Update warehouse
    Route::put('warehouses/{id}', [WarehouseContorller::class, 'updateWarehouse']);
    // Delete a warehouse
    Route::delete('/warehouses/{id}', [WarehouseContorller::class, 'deleteWarehouse']);

    // Grid routts
    // create gird
    Route::post('/grids', [GridController::class, 'createGrid']);
    // show all grids
    Route::get('/grids', [GridController::class, 'showAllGrids']);
    // show grid by id
    Route::get('/grids/{id}', [GridController::class, 'showGridById']);
    // update grid
    Route::put('/grids/{id}', [GridController::class, 'updateGrid']);
    // Delete a grid
    Route::delete('/grids/{id}', [GridController::class, 'deleteGrid']);
    // Change grid status
    Route::patch('/grids/{id}/toggle-status', [GridController::class, 'toggleStatus']);

    // Request order routes
    // show all request
    Route::get('/admin/requests', [RequestController::class, 'getAllRequestsForAdmin']);
    // Request file upload admin
    Route::post('/request-files/admin', [RequestFileController::class, 'uploadRequestFilesadmin']);
    // show request by id
    Route::get('/admin/requests/{id}', [RequestController::class, 'showRequestById']);
    // Request status change
    Route::post('/requests/{id}/status', [RequestController::class, 'updateStatus']);

    // Payment routes
    // advance payment route create by admin
    Route::post('/payments', [PaymentController::class, 'advancedPayment']);


    // Order routes
    // plase order
    Route::post('/place-order', [OrderController::class, 'placeOrder']);
});

// User api routes
// user register
Route::post('/register/user', [UserController::class, 'register']);
//user login
Route::post('/login/user', [UserController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    // show login user info
    Route::get('/user', [UserController::class, 'getUserInfo']);
    // user logout
    Route::post('/logout/user', [UserController::class, 'logout']);

    // shwo all warehouse types
    Route::get('/admin/warehouse-types', [WarehouseTypeContorller::class, 'getAllWarehouseTypes']);

    // order requests
    Route::post('/create-order-request/user', [RequestController::class, 'createRequrstOrder']);
    // Request file upload
    Route::post('/request-files/user', [RequestFileController::class, 'uploadRequestFiles']);
    // show all requests for this login user
    Route::get('/requests/user', [RequestController::class, 'getUserRequests']);
    // show request by id
    Route::get('/requests/user/{id}', [RequestController::class, 'getRequestById']);
});
