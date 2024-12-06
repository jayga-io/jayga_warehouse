<?php

namespace App\Http\Controllers\order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    //plase order
    public function placeOrder(Request $request)
    {
        try {
            // Validate the input
            $validator = Validator::make($request->all(), [
                'request_id' => 'required|integer|exists:requests,id',
                'payment' => 'required|string',
                'grid_assignments' => 'required|array',
                'grid_assignments.*.item_id' => 'required|integer|exists:items,id',
                'grid_assignments.*.grid_id' => 'required|integer|exists:grids,id',
                'grid_assignments.*.quantity' => 'required|integer|min:1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'messages' => $validator->errors(),
                ], 422);
            }

            // Fetch the request details
            $requestData = DB::table('requests')->where('id', $request->request_id)->first();
            if (!$requestData) {
                return response()->json([
                    'error' => 'Request not found',
                ], 404);
            }

            // Create an order
            $order = DB::table('orders')->insertGetId([
                'request_id' => $request->request_id,
                'user_id' => $requestData->user_id,
                'status' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create an order list
            $orderList = DB::table('order_lists')->insertGetId([
                'order_id' => $order,
                'status' => 0,
                'payment' => $request->payment,
                'start_date' => $requestData->start_date,
                'end_date' => $requestData->end_date,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create assign grid entries for each item
            $assignGrids = [];
            foreach ($request->grid_assignments as $assignment) {
                $assignGrids[] = [
                    'item_id' => $assignment['item_id'],
                    'grid_id' => $assignment['grid_id'],
                    'quantity' => $assignment['quantity'],
                    'order_list_id' => $orderList,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            DB::table('assign_grids')->insert($assignGrids);

            // Update the request status
            DB::table('requests')->where('id', $request->request_id)->update(['status' => 1]);

            return response()->json([
                'message' => 'Order placed successfully',
                'order_id' => $order,
                'order_list_id' => $orderList,
                'assigned_grids' => $assignGrids,
            ], 201);
        } catch (\Exception $e) {
            // Log the exception for debugging
            Log::error('Error placing order: ' . $e->getMessage());

            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
