<?php

namespace App\Http\Controllers\request;

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


class RequestController extends Controller
{
    //create request order
    public function createRequrstOrder(Request $request)
    {
        try {
            // Validate the input
            $validator = Validator::make($request->all(), [
                'warehouseType_id' => 'required|integer',
                'size' => 'required|integer|min:1',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'items' => 'required|array',
                'items.*.name' => 'required|string',
                'items.*.type' => 'required|string',
                'items.*.request_quatity' => 'required|integer|min:1',
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
                'warehouseType_id' => $request->warehouseType_id,
                'status' => 0, // Default status
                'size' => $request->size,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
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
                    'recived_quatity' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Insert all items at once
            Item::insert($items);

            return response()->json([
                'message' => 'Order Request created successfully',
                'order' => $orderRequest,
                'items' => $items,
            ], 201);
        } catch (\Exception $e) {
            // Log the exception details
            LogHelper::logError('Something went wrong', $e->getMessage(), 'user register');
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    // show all request which created by login user
    public function getUserRequests(Request $request)
    {
        try {
            // Fetch the logged-in user's ID
            $userId = $request->user()->id;

            // Fetch all requests for the logged-in user with related warehouse_type and items
            $userRequests = OrderRequest::with([
                'warehouseType' => function ($query) {
                    $query->select('id', 'type_name', 'description', 'admin_id');
                },
                'items',
            ])
                ->where('user_id', $userId)
                ->get();

            // Return the data
            return response()->json([
                'message' => 'User requests fetched successfully',
                'data' => $userRequests,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    // shwo request by id
    public function getRequestById(Request $request, $id)
    {
        try {
            // Fetch the logged-in user's ID
            $userId = $request->user()->id;

            // Fetch the specific request for the logged-in user, including warehouseType and payments
            $orderRequest = OrderRequest::with(['warehouseType', 'items', 'payments'])
                ->where('user_id', $userId)
                ->where('id', $id)
                ->first();

            // Check if the request exists
            if (!$orderRequest) {
                return response()->json([
                    'error' => 'Request not found or unauthorized access',
                ], 404);
            }

            // Fetch related files from RequestFile where type is 'order_request'
            $requestFiles = RequestFile::where('relatable_id', $id)
                ->where('type', 'order_request')
                ->get();

            // Combine data from OrderRequest, related files
            $data = [
                'order_request' => $orderRequest,
                'related_files' => $requestFiles,
            ];

            // Return the data
            return response()->json([
                'message' => 'Request fetched successfully',
                'data' => $data,
            ], 200);
        } catch (\Exception $e) {
            // Handle exceptions and log the error
            Log::error('Error fetching request by ID: ' . $e->getMessage());

            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    // show all requests order in admin dashboard
    public function getAllRequestsForAdmin()
    {
        try {
            // Fetch paginated request orders with warehouseType and user details, along with items
            $requests = OrderRequest::with([
                'warehouseType' => function ($query) {
                    $query->select('id', 'type_name', 'description');
                },
                'user' => function ($query) {
                    $query->select('id', 'name', 'email', 'phone', 'company_name', 'industry_type', 'status', 'profile_image', 'address', 'latitude', 'longitude');
                },
                'items',
            ])->paginate(15);

            return response()->json([
                'message' => 'All requests fetched successfully',
                'data' => $requests,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    // shwo request order by id in admin dashboard
    public function showRequestById($id)
    {
        try {
            // Fetch the request by ID along with related data including payments
            $requestData = OrderRequest::with(['warehouseType', 'user', 'items', 'payments'])
                ->find($id);

            // Check if the request exists
            if (!$requestData) {
                return response()->json(['message' => 'Request not found'], 404);
            }

            // Fetch related files from RequestFile where type is 'order_request'
            $relatedFiles = RequestFile::where('relatable_id', $id)
                ->where('type', 'order_request')
                ->get();

            // Remove sensitive or unnecessary user data
            if ($requestData->user) {
                $requestData->user->makeHidden([
                    'password',
                    'is_suspended',
                    'fcm_token',
                    'auth_token',
                    'description',
                ]);
            }

            // Combine the request data with related files
            $data = [
                'order_request' => $requestData,
                'related_files' => $relatedFiles,
            ];

            // Return the combined data
            return response()->json([
                'message' => 'Request fetched successfully',
                'data' => $data,
            ], 200);
        } catch (\Exception $e) {
            // Handle any unexpected errors
            return response()->json([
                'message' => 'An error occurred while fetching the request',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    // update request order by id in admin dashboard
    public function updateStatus(Request $request, $id)
    {
        try {
            // Validate the request input
            $validatedData = $request->validate([
                'status' => 'required|integer',
            ]);

            // Find the order request by ID
            $orderRequest = OrderRequest::find($id);

            // If the order request is not found, return a 404 response
            if (!$orderRequest) {
                return response()->json(['message' => 'Request not found'], 404);
            }

            // Capture the current status before updating
            $currentStatus = $orderRequest->status;

            // Update the status
            $orderRequest->status = $validatedData['status'];
            $orderRequest->save();

            // Log the activity using the helper
            logAdminActivity(
                $id,
                Auth::id(),
                "Updating order request status from '{$currentStatus}' to '{$validatedData['status']}'",
                'request'
            );

            // Return success response
            return response()->json(['message' => 'Status updated successfully'], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Log the exception details
            LogHelper::logError('Something went wrong', $e->getMessage(), 'request status changed by admin');
            // Handle validation exceptions
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            // Log the exception details
            LogHelper::logError('Something went wrong', $e->getMessage(), 'request status changed by admin');
            // Handle other exceptions
            return response()->json(['message' => 'An error occurred', 'error' => $e->getMessage()], 500);
        }
    }

    // assign warehouse
    public function updateWarehouse(Request $request, $id)
    {
        try {
            // Validate the incoming request
            $validatedData = $request->validate([
                'warehouse_id' => 'required|exists:warehouses,id',
            ]);

            // Find the order by ID
            $order = OrderRequest::find($id);

            if (!$order) {
                return response()->json(['message' => 'Order not found'], 404);
            }

            // Update the warehouse_id field
            $order->warehouse_id = $validatedData['warehouse_id'];
            $order->save();

            // Log the activity using the helper
            logAdminActivity(
                $id,
                Auth::id(),
                "Assign warehouse to this request",
                'request'
            );

            return response()->json([
                'message' => 'Warehouse ID updated successfully',
                'order' => $order,
            ], 200);
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle database-related errors
            return response()->json([
                'message' => 'Database error occurred',
                'error' => $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            // Log the exception details
            LogHelper::logError('Something went wrong', $e->getMessage(), 'request assigned to warehouse');
            // Handle any other unexpected errors
            return response()->json([
                'message' => 'An error occurred',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Request items by request id
    public function getItemsByRequest($request_id)
    {
        try {
            // Fetch items related to the request ID
            $items = item::where('request_id', $request_id)->get();

            if ($items->isEmpty()) {
                return response()->json([
                    'message' => 'No items found for the specified request ID',
                ], 404);
            }

            return response()->json([
                'message' => 'Items retrieved successfully',
                'items' => $items,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

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
