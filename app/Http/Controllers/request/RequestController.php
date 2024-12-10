<?php

namespace App\Http\Controllers\request;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\request as OrderRequest;
use App\Models\item;
use App\Models\RequestFile;
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

            // Fetch all requests for the logged-in user with related warehouse and items
            $userRequests = OrderRequest::with(['warehouse', 'items'])
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

            // Fetch the specific request for the logged-in user, including warehouseType
            $orderRequest = OrderRequest::with(['warehouseType', 'items'])
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

            // Combine data from OrderRequest and related files
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
            // Fetch all request orders with warehouse and user details, along with items
            $requests = OrderRequest::with(['warehouse', 'user', 'items'])->get();

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
            // Fetch the request by ID along with related data
            $requestData = OrderRequest::with(['warehouseType', 'user', 'items'])
                ->find($id);

            // Check if the request exists
            if (!$requestData) {
                return response()->json(['message' => 'Request not found'], 404);
            }

            // Fetch related files from RequestFile where type is 'order_request'
            $relatedFiles = RequestFile::where('relatable_id', $id)
                ->where('type', 'order_request')
                ->get();

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
                'status' => 'required|integer|in:0,1,2,3',
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
}
