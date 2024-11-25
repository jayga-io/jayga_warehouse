<?php

namespace App\Http\Controllers\warehouse;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\warehouse_type;
use App\Models\admin;
use App\Models\updatewarehousetype;

class WarehouseTypeContorller extends Controller
{
    //create warehouse type
    public function createWarehouseType(Request $request)
    {
        try {
            // Validate the request data
            $request->validate([
                'type_name' => 'required|string|max:255',
                'description' => 'required|string|max:1000',
            ]);

            // Get the logged-in admin's ID
            $adminId = Auth::id();

            // Create a new warehouse type
            $warehouseType = warehouse_type::create([
                'type_name' => $request->type_name,
                'description' => $request->description,
                'admin_id' => $adminId,
            ]);

            // Return success response
            return response()->json([
                'message' => 'Warehouse type created successfully',
                'warehouse_type' => $warehouseType,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation errors
            return response()->json([
                'error' => 'Validation error',
                'message' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            // Handle general errors
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    // Show all warehouse types
    public function getAllWarehouseTypes()
    {
        try {
            // Retrieve all warehouse types along with the associated admin information and who updated it
            $warehouseTypes = warehouse_type::with(['admin', 'updates' => function ($query) {
                $query->with('admin'); // Join the 'updatewarehousetype' table and get admin info
            }])->get();

            // Return a response with the warehouse types
            return response()->json([
                'message' => 'Warehouse types retrieved successfully',
                'warehouse_types' => $warehouseTypes,
            ], 200);
        } catch (\Exception $e) {
            // Handle any errors that occur
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    // get warehouse types by id
    public function getWarehouseTypeById($id)
    {
        try {
            // Find the warehouse type by ID along with the associated admin information
            $warehouseType = warehouse_type::with('admin')->find($id);

            // Check if warehouse type exists
            if (!$warehouseType) {
                return response()->json([
                    'error' => 'Warehouse type not found',
                ], 404);
            }

            // Return the warehouse type details
            return response()->json([
                'message' => 'Warehouse type retrieved successfully',
                'warehouse_type' => $warehouseType,
            ], 200);
        } catch (\Exception $e) {
            // Handle any errors that occur
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // Update warehouse type
    public function updateWarehouseType(Request $request, $id)
    {
        try {
            // Find the warehouse type by ID
            $warehouseType = warehouse_type::find($id);

            // Check if warehouse type exists
            if (!$warehouseType) {
                return response()->json([
                    'error' => 'Warehouse type not found',
                ], 404);
            }

            // Validate input
            $request->validate([
                'type_name' => 'required|string|max:255',
                'description' => 'required|string|max:255',
            ]);

            // Update the warehouse type information
            $warehouseType->update([
                'type_name' => $request->type_name,
                'description' => $request->description,
            ]);

            $adminId = Auth::id(); // Get the ID of the currently authenticated admin

            updatewarehousetype::create([
                'warehousetypes_id' => $id,
                'admin_id' => $adminId,
            ]);

            // Return success response
            return response()->json([
                'message' => 'Warehouse type updated successfully',
                'warehouse_type' => $warehouseType,
            ], 200);
        } catch (\Exception $e) {
            // Handle any errors that occur
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
