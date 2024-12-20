<?php

namespace App\Http\Controllers\grid;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\grid;
use App\Models\adminactivity;
use Illuminate\Support\Facades\Auth;
use App\Helpers\LogHelper;

class GridController extends Controller
{
    //create grid
    public function createGrid(Request $request)
    {
        // Validate incoming request
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'grid_code' => 'required|string',
            'size' => 'required|string',
            'has_rack' => 'nullable|string',
            'rack_multiplier' => 'required|string',
            'type' => 'nullable|string',
        ]);

        try {
            // Create a new grid
            $grid = grid::create([
                'warehouse_id' => $validated['warehouse_id'],
                'grid_code' => $validated['grid_code'],
                'size' => $validated['size'],
                'has_rack' => $validated['has_rack'] ?? '0',
                'rack_multiplier' => $validated['rack_multiplier'],
                'type' => $validated['type'] ?? null,
                'status' => '1',
                'is_occupied' => '0',
            ]);

            // Return success response
            return response()->json([
                'message' => 'Grid created successfully.',
                'grid' => $grid,
            ], 201);
        } catch (\Exception $e) {
            // Log the exception details
            LogHelper::logError('Something went wrong', $e->getMessage(), 'grid create');
            // Handle exceptions
            return response()->json([
                'error' => 'Failed to create grid.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }



    // show all grids
    public function showAllGrids()
    {
        try {
            // Fetch all grids with associated warehouse data
            $grids = grid::with('warehouse')->get();

            // Return response in JSON format (for an API)
            return response()->json([
                'message' => 'Grids with warehouse information retrieved successfully',
                'grids' => $grids
            ], 200);
        } catch (\Exception $e) {
            // Handle any errors gracefully
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage()
            ], 500);
        }
    }



    // grid shwo by id
    public function showGridById($id)
    {
        try {
            // Fetch the grid by ID with associated warehouse, admin activities, and admin info for each activity
            $grid = grid::with([
                'warehouse',
                'adminActivities' => function ($query) {
                    $query->where('type', 'grid');
                },
                'adminActivities.admin'
            ])->findOrFail($id);

            // Return the response in JSON format
            return response()->json([
                'message' => 'Grid with warehouse and admin activity information retrieved successfully',
                'grid' => $grid
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Handle case where the grid is not found
            return response()->json([
                'error' => 'Grid not found',
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



    // update grid
    public function updateGrid(Request $request, $id)
    {
        try {
            // Find the grid by ID
            $grid = grid::findOrFail($id);

            // Validate the input data (you can adjust the rules as needed)
            $validatedData = $request->validate([
                'warehouse_id' => 'required|exists:warehouses,id',
                'grid_code' => 'required|string|max:255',
                'size' => 'required|string|max:255',
                'has_rack' => 'required|boolean',
                'rack_multiplier' => 'required|numeric',
                'status' => 'required|string|max:255',
                'type' => 'required|string|max:255',
                'is_occupied' => 'required|boolean'
            ]);

            // Update the grid with validated data
            $grid->update($validatedData);

            // Log the activity using the helper
            logAdminActivity(
                $grid->id,
                Auth::id(),
                'Grid updated',
                'grid'
            );

            // Return a success response
            return response()->json([
                'message' => 'Grid updated successfully',
                'grid' => $grid
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Log the exception details
            LogHelper::logError('Something went wrong', $e->getMessage(), 'grid update');
            // Handle case where the grid is not found
            return response()->json([
                'error' => 'Grid not found',
                'message' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            // Log the exception details
            LogHelper::logError('Something went wrong', $e->getMessage(), 'grid update');
            // Handle general exceptions
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage()
            ], 500);
        }
    }



    // delete grid
    public function deleteGrid($id)
    {
        try {
            // Find the grid by ID
            $grid = grid::findOrFail($id);

            // Log the activity using the helper (before deletion)
            logAdminActivity(
                $grid->id,
                Auth::id(),
                'Grid deleted',
                'grid'
            );

            // Delete the grid
            $grid->delete();

            // Return a success response
            return response()->json([
                'message' => 'Grid deleted successfully'
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Handle case where the grid is not found
            return response()->json([
                'error' => 'Grid not found',
                'message' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            // Log the exception details
            LogHelper::logError('Something went wrong', $e->getMessage(), 'grid delete');
            // Handle general exceptions
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage()
            ], 500);
        }
    }



    // Change grid status
    public function toggleStatus($id)
    {
        try {
            // Find the grid by ID
            $grid = grid::findOrFail($id);

            // Toggle the status (1 becomes 0, and 0 becomes 1)
            $grid->status = $grid->status == 1 ? 0 : 1;
            $grid->save();

            // Log the activity using the helper
            logAdminActivity(
                $grid->id,
                Auth::id(),
                'Grid status updated',
                'grid'
            );

            // Return a success response
            return response()->json([
                'message' => 'Grid status toggled successfully',
                'grid' => $grid  // Return the updated grid object
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Handle case where the grid is not found
            return response()->json([
                'error' => 'Grid not found',
                'message' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            // Log the exception details
            LogHelper::logError('Something went wrong', $e->getMessage(), 'grid status changed');
            // Handle general exceptions
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage()
            ], 500);
        }
    }



    // Grid show by warehouse id
    public function getGridsByWarehouse($warehouse_id)
    {
        try {
            // Fetch grids related to the warehouse ID
            $grids = grid::where('warehouse_id', $warehouse_id)->get();

            if ($grids->isEmpty()) {
                return response()->json([
                    'message' => 'No grids found for the specified warehouse ID',
                ], 404);
            }

            return response()->json([
                'message' => 'Grids retrieved successfully',
                'grids' => $grids,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
