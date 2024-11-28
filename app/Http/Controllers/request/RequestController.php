<?php

namespace App\Http\Controllers\request;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\request as OrderRequest;
use App\Models\item;
use Illuminate\Support\Facades\Validator;

class RequestController extends Controller
{
    //create request order
    public function createRequrstOrder(Request $request)
    {
        try {
            // Validate the input
            $validator = Validator::make($request->all(), [
                'warehouse_id' => 'required|integer|exists:warehouses,id',
                'items' => 'required|array',
                'items.*.name' => 'required|string',
                'items.*.type' => 'required|string',
                'items.*.request_quatity' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'messages' => $validator->errors(),
                ], 422);
            }

            // Create the request
            $orderRequest = OrderRequest::create([
                'user_id' => $request->user()->id,
                'warehouse_id' => $request->warehouse_id,
                'status' => 0,
            ]);

            // Create the items
            $items = [];
            foreach ($request->items as $itemData) {
                $items[] = [
                    'request_id' => $orderRequest->id,
                    'name' => $itemData['name'],
                    'user_id' => $request->user()->id,
                    'type' => $itemData['type'],
                    'request_quatity' => $itemData['request_quatity'],
                    'recived_quatity' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Insert all items at once
            item::insert($items);

            return response()->json([
                'message' => 'Order Request created successfully',
                'order' => $orderRequest,
                'items' => $items,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
