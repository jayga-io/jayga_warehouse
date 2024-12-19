<?php

namespace App\Http\Controllers\AssignedGrid;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\request as OrderRequest;
use App\Models\item;
use App\Models\RequestFile;
use App\Models\assign_grid;
use Illuminate\Support\Facades\Validator;
use App\Helpers\LogHelper;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class AssignGridController extends Controller
{
    // Assign the grid
    public function assignGrids(Request $request)
    {
        // Validate the request
        $validatedData = $request->validate([
            'request_id' => 'required|integer|exists:requests,id',
            'assignments' => 'required|array',
            'assignments.*.item_id' => 'nullable|integer|exists:items,id',
            'assignments.*.grid_id' => 'required|integer|exists:grids,id',
            'assignments.*.quantity' => 'nullable|integer|min:1',
        ]);

        try {
            // Extract data
            $request_id = $validatedData['request_id'];
            $assignments = $validatedData['assignments'];

            // Store each assignment
            foreach ($assignments as $assignment) {
                assign_grid::create([
                    'request_id' => $request_id,
                    'item_id' => $assignment['item_id'] ?? null,
                    'grid_id' => $assignment['grid_id'],
                    'quantity' => $assignment['quantity'] ?? null,
                ]);
            }

            return response()->json([
                'message' => 'Grids and items assigned successfully',
            ], 201);
        } catch (\Exception $e) {
            // Log the exception details
            LogHelper::logError('Something went wrong', $e->getMessage(), 'request assigned grid');
            return response()->json([
                'message' => 'An error occurred',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    //Fatch the assigned grid and items by request id
    public function fetchItemsByRequest($request_id)
    {
        try {
            // Fetch all assigned grids and items by request_id
            $assignments = assign_grid::where('request_id', $request_id)
                ->with(['item:id,name,type', 'grid:id,grid_code'])
                ->get();

            if ($assignments->isEmpty()) {
                return response()->json(['message' => 'No assignments found for the given request ID'], 404);
            }

            // Transform data to include item and grid details
            $data = $assignments->map(function ($assignment) {
                return [
                    'item_name' => $assignment->item->name ?? null,
                    'item_type' => $assignment->item->type ?? null,
                    'grid_code' => $assignment->grid->grid_code ?? null,
                    'quantity' => $assignment->quantity,
                ];
            });

            return response()->json([
                'message' => 'Assignments retrieved successfully',
                'data' => $data,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
