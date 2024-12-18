<?php

namespace App\Http\Controllers\payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\payment;
use Illuminate\Support\Facades\Auth;
use App\Helpers\LogHelper;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    // advance payment route create by admin
    public function advancedPayment(Request $request)
    {
        try {
            // Validate user input
            $validated = $request->validate([
                'request_id' => 'required|integer',
                'amount' => 'required|numeric|min:0',
            ]);

            // Populate the payments table
            Payment::create([
                'relatable_id' => $validated['request_id'],
                'type' => 'request',
                'payment_region' => 'advance payment of request order',
                'amount' => $validated['amount'],
                'status' => '0',
            ]);

            // Log the activity using the helper
            logAdminActivity(
                $validated['request_id'],
                Auth::id(),
                'advance payment of the order request',
                'request'
            );

            // Return success response
            return response()->json(['message' => 'Payment created successfully'], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation exceptions
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            // Log the exception details
            LogHelper::logError('Something went wrong', $e->getMessage(), 'advance payment on order request');
            // Handle general exceptions
            return response()->json([
                'message' => 'An error occurred while processing your request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // change payment status by admin
    public function updatePaymentStatus($id, Request $request)
    {
        try {
            $validatedData = $request->validate([
                'status' => 'required', // 0 = Pending, 1 = Completed
            ]);

            // Find the payment by ID
            $payment = payment::find($id);

            // Check if the payment exists
            if (!$payment) {
                return response()->json([
                    'message' => 'Payment not found',
                ], 404);
            }

            // Update the payment status
            $payment->status = $validatedData['status'];
            $payment->save();

            // Log the activity using the helper
            logAdminActivity(
                $id,
                Auth::id(),
                'advance payment status update',
                'payment'
            );

            // Return the updated payment details
            return response()->json([
                'message' => 'Payment status updated successfully',
                'data' => $payment,
            ], 200);
        } catch (\Exception $e) {
            // Log the exception details
            LogHelper::logError('Something went wrong', $e->getMessage(), 'advance payment status change');
            // Handle any unexpected errors
            return response()->json([
                'message' => 'An error occurred while updating the payment status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // show all payment
    public function showAllPayments()
    {
        try {
            // Fetch all payments
            $payments = payment::all();

            // Return the data as a JSON response
            return response()->json([
                'success' => true,
                'data' => $payments
            ], 200);
        } catch (\Exception $e) {
            // Log the error for debugging purposes
            Log::error('Error fetching payments: ' . $e->getMessage());

            // Return an error JSON response
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch payments. Please try again later.'
            ], 500);
        }
    }

    // shwo payment by id
    public function showpaymentById($id)
    {
        try {
            // Fetch the payment by ID
            $payment = payment::find($id);

            // Check if the payment exists
            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found.'
                ], 404);
            }

            // Return the payment data as a JSON response
            return response()->json([
                'success' => true,
                'data' => $payment
            ], 200);
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Error fetching payment: ' . $e->getMessage());

            // Return an error response
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch payment. Please try again later.'
            ], 500);
        }
    }
}
