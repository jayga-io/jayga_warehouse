<?php

namespace App\Http\Controllers\warehouse;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Models\warehouse;
use App\Models\adminactivity;

class WarehouseContorller extends Controller
{
    //Create warehouse
    public function storeWarehouse(Request $request)
    {
        try {
            // Validate the input
            $request->validate([
                'location' => 'required|string|max:255',
                'latitude' => 'required|string|max:255',
                'longitude' => 'required|string|max:255',
                'size' => 'required|string|max:255',
                'contact_person' => 'required|string|max:255',
                'contact_phone' => 'required|string|max:15',
                'owner_name' => 'required|string|max:255',
                'owner_phone' => 'required|string|max:15',
                'owner_email' => 'required|email|unique:warehouses,owner_email',
                'total_grids' => 'required|string|max:255',
                'grid_price_per_day' => 'required|string|max:255',
                'district' => 'required|string|max:255',
                'area' => 'required|string|max:255',
                'warehouse_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
                'warehouse_type_id' => 'required|integer',
            ]);

            // Handle the warehouse image upload
            $imagePath = null;
            if ($request->hasFile('warehouse_image')) {
                $imageName = uniqid() . '.' . $request->file('warehouse_image')->getClientOriginalExtension();
                $imagePath = $request->file('warehouse_image')->storeAs('public/warehouse_image', $imageName);
                $imagePath = str_replace('public/', '', $imagePath);
            }

            // Create the warehouse
            $warehouse = warehouse::create([
                'location' => $request->location,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'size' => $request->size,
                'contact_person' => $request->contact_person,
                'contact_phone' => $request->contact_phone,
                'owner_name' => $request->owner_name,
                'owner_phone' => $request->owner_phone,
                'owner_email' => $request->owner_email,
                'total_grids' => $request->total_grids,
                'grid_price_per_day' => $request->grid_price_per_day,
                'district' => $request->district,
                'area' => $request->area,
                'warehouse_image' => $imagePath,
                'warehouse_type_id' => $request->warehouse_type_id,
                'admin_id' => $request->user()->id,
                'status' => '1',
                'is_active' => '1',
            ]);

            // Return success response
            return response()->json([
                'message' => 'Warehouse created successfully',
                'warehouse' => $warehouse,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation exceptions
            return response()->json([
                'error' => 'Validation error',
                'message' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            // Handle other exceptions
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // show all warehouse
    public function showWarehouse()
    {
        try {
            // Fetch warehouses with their related data
            $warehouses = warehouse::with([
                'warehouseType',
                'admin',
                'adminActivities.admin' => function ($query) {
                    $query->where('type', 'warehouse');
                }
            ])->get();

            // Hide sensitive data
            $warehouses->each(function ($warehouse) {
                if ($warehouse->admin) {
                    $warehouse->admin->makeHidden('password');
                }

                // Hide admin password in each admin activity
                $warehouse->adminActivities->each(function ($activity) {
                    if ($activity->admin) {
                        $activity->admin->makeHidden('password');
                    }
                });
            });

            // Return the response
            return response()->json([
                'message' => 'Warehouses retrieved successfully',
                'warehouses' => $warehouses
            ], 200);
        } catch (\Exception $e) {
            // Handle exceptions
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    // show warehouse by id
    public function showWarehouseById($id)
    {
        try {
            // Find the warehouse by ID and include related data (grids, warehouse type, admin, admin activities)
            $warehouse = warehouse::with([
                'warehouseType',
                'admin',
                'adminActivities.admin' => function ($query) {
                    $query->where('type', 'warehouse');
                },
                'grids' // Eager load related grids
            ])->findOrFail($id);

            // Hide sensitive fields for the warehouse's admin
            if ($warehouse->admin) {
                $warehouse->admin->makeHidden('password');
            }

            // Process admin activities and hide sensitive admin info
            $warehouse->adminActivities->each(function ($activity) {
                if ($activity->admin) {
                    $activity->admin->makeHidden(['password', 'auth_token', 'fcm_token']);
                }
            });

            // Return the response with the warehouse, admin activity data, and related grids
            return response()->json([
                'message' => 'Warehouse retrieved successfully',
                'warehouse' => $warehouse
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Handle case where the warehouse is not found
            return response()->json([
                'error' => 'Warehouse not found',
                'message' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            // Handle general exceptions
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    // activate status change
    public function toggleIsActive($id)
    {
        try {
            // Find the warehouse by ID
            $warehouse = warehouse::findOrFail($id);

            // Toggle the is_active status
            $previousStatus = $warehouse->is_active;
            $warehouse->is_active = $warehouse->is_active == '1' ? '0' : '1';
            $warehouse->save();

            // Save the activity in the adminactivities table
            adminactivity::create([
                'retated_table_id' => $warehouse->id,
                'admin_id' => Auth::id(),
                'description' => "Changed is_active status from $previousStatus to {$warehouse->is_active} for warehouse ID {$warehouse->id}",
                'type' => 'warehouse'
            ]);

            // Return a success response
            return response()->json([
                'message' => 'Warehouse status updated successfully',
                'warehouse' => $warehouse
            ], 200);
        } catch (\Exception $e) {
            // Handle general exceptions
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    // update warehouse
    public function updateWarehouse(Request $request, $id)
    {
        try {
            // Validate the incoming request data
            $validatedData = $request->validate([
                'location' => 'required|string|max:255',
                'latitude' => 'required|string|max:255',
                'longitude' => 'required|string|max:255',
                'size' => 'required|string|max:255',
                'contact_person' => 'required|string|max:255',
                'contact_phone' => 'required|string|max:255',
                'owner_name' => 'required|string|max:255',
                'owner_phone' => 'required|string|max:255',
                'owner_email' => 'required|string|email|max:255',
                'total_grids' => 'required|string|max:255',
                'grid_price_per_day' => 'required|string|max:255',
                'district' => 'required|string|max:255',
                'area' => 'required|string|max:255',
                'warehouse_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'warehouse_type_id' => 'required|integer',
                'description' => 'nullable|string',
            ]);

            // Find the warehouse by ID
            $warehouse = warehouse::findOrFail($id);

            // Handle the warehouse image upload if provided
            if ($request->hasFile('warehouse_image')) {
                $imagePath = $request->file('warehouse_image')->store('warehouse_images', 'public');
                $validatedData['warehouse_image'] = $imagePath;
            }

            // Update the warehouse details
            $warehouse->update($validatedData);

            // Save the update info in the adminactivities table
            adminactivity::create([
                'retated_table_id' => $warehouse->id,
                'admin_id' => auth()->user()->id,
                'description' => 'Updated warehouse details',
                'type' => 'warehouse'
            ]);

            // Return success response
            return response()->json([
                'message' => 'Warehouse updated successfully',
                'warehouse' => $warehouse
            ], 200);
        } catch (\Exception $e) {
            // Handle exceptions
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    // Delete warehouse
    public function deleteWarehouse($id)
    {
        try {
            // Find the warehouse by ID
            $warehouse = warehouse::findOrFail($id);

            // Get the authenticated admin ID
            $adminId = auth()->id();

            // Save the deletion activity to the adminactivities table
            adminactivity::create([
                'retated_table_id' => $warehouse->id,
                'admin_id' => $adminId,
                'description' => 'Deleted warehouse: ' . $warehouse->location,
                'type' => 'warehouse'
            ]);

            // Delete the warehouse record
            $warehouse->delete();

            // Return success response
            return response()->json([
                'message' => 'Warehouse deleted successfully and activity logged.'
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Handle case where the warehouse is not found
            return response()->json([
                'error' => 'Warehouse not found',
                'message' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            // Handle general exceptions
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
