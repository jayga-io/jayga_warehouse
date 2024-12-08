<?php

namespace App\Http\Controllers\warehouse;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Models\warehouse;
use App\Models\adminactivity;
use App\Models\grid;
use App\Helpers\LogHelper;

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
                'total_grids' => 'required|integer|min:1',
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
            $warehouse = Warehouse::create([
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

            // Auto-create grids
            $grids = [];
            $locationCode = strtoupper(substr($warehouse->location, 0, 3));
            for ($i = 1; $i <= $request->total_grids; $i++) {
                $grids[] = [
                    'warehouse_id' => $warehouse->id,
                    'grid_code' => $locationCode . $i,
                    'size' => '0',
                    'has_rack' => '0',
                    'rack_multiplier' => '0',
                    'type' => null,
                    'status' => '1',
                    'is_occupied' => '0',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Bulk insert grids
            grid::insert($grids);

            // Return success response
            return response()->json([
                'message' => 'Warehouse created successfully with grids.',
                'warehouse' => $warehouse,
                'grids' => $grids,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Log the exception details
            LogHelper::logError('Something went wrong', $e->getMessage(), 'warehouse create');
            // Handle validation exceptions
            return response()->json([
                'error' => 'Validation error',
                'message' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            // Log the exception details
            LogHelper::logError('Something went wrong', $e->getMessage(), 'warehouse create');
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

            // Use the helper function to log the activity
            logAdminActivity(
                $warehouse->id,
                Auth::id(),
                "Changed is_active status from $previousStatus to {$warehouse->is_active} for warehouse ID {$warehouse->id}",
                'warehouse'
            );

            // Return a success response
            return response()->json([
                'message' => 'Warehouse status updated successfully',
                'warehouse' => $warehouse
            ], 200);
        } catch (\Exception $e) {
            // Log the exception details
            LogHelper::logError('Something went wrong', $e->getMessage(), 'warehouse status change');
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

            // Log the update activity using the helper
            logAdminActivity(
                $warehouse->id,
                auth()->user()->id,
                'Updated warehouse details',
                'warehouse'
            );

            // Return success response
            return response()->json([
                'message' => 'Warehouse updated successfully',
                'warehouse' => $warehouse
            ], 200);
        } catch (\Exception $e) {
            // Log the exception details
            LogHelper::logError('Something went wrong', $e->getMessage(), 'warehouse update');
            // Handle exceptions
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
