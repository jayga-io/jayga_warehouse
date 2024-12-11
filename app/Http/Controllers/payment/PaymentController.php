<?php

namespace App\Http\Controllers\payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\payment;
use Illuminate\Support\Facades\Auth;
use App\Helpers\LogHelper;

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
}