<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Models\admin;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    // Show login admin information
    public function showAdminInfo(Request $request)
    {
        try {
            // Get the authenticated admin
            $admin = Auth::user();

            // Return the admin's information
            return response()->json([
                'admin' => $admin,
            ], 200);
        } catch (\Exception $e) {
            // Handle any errors (e.g., if the admin is not authenticated)
            return response()->json([
                'error' => 'Error fetching admin information',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    // Show all admin list
    public function listAdmins()
    {
        try {
            // Fetch all admins from the database
            $admins = Admin::all(); // You can add pagination if needed

            // Return the list of admins in the response
            return response()->json([
                'message' => 'Admins retrieved successfully',
                'admins' => $admins
            ], 200);
        } catch (\Exception $e) {
            // In case of any error, return an error response
            return response()->json([
                'error' => 'Failed to retrieve admins',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
