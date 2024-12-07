<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\admin;
use App\Helpers\LogHelper;

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
            // Retrieve all admins from the database
            $admins = admin::all();

            // Return a response with the list of admins, excluding sensitive data like passwords
            return response()->json([
                'message' => 'Admins retrieved successfully',
                'admins' => $admins->makeHidden(['password']), // Hide sensitive data
            ], 200);
        } catch (\Exception $e) {
            // Handle any errors that occur
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    // editAdmins
    public function editAdmin($id)
    {
        try {
            // Find the admin by ID
            $admin = admin::find($id);

            // Check if the admin exists
            if (!$admin) {
                return response()->json([
                    'error' => 'Admin not found',
                ], 404);
            }

            // Return the admin details (excluding sensitive data like the password)
            return response()->json([
                'message' => 'Admin found',
                'admin' => $admin->makeHidden(['password']),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    // update admin information
    public function updateAdminById(Request $request, $id)
    {
        try {
            // Find the admin by ID
            $admin = admin::find($id);

            // Check if the admin exists
            if (!$admin) {
                return response()->json([
                    'error' => 'Admin not found',
                ], 404);
            }

            // Validate the incoming data
            $request->validate([
                'name' => 'nullable|string|max:255',
                'email' => 'nullable|string|email|max:255|unique:admins,email,' . $admin->id,
                'type' => 'nullable|string',
                'role' => 'nullable|string',
                'status' => 'nullable|string',
                'admin_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            // Handle the image upload if provided
            if ($request->hasFile('admin_image')) {
                // Generate a unique file name
                $imageName = uniqid() . '.' . $request->file('admin_image')->getClientOriginalExtension();

                // Store the image in the 'public/admin_images' folder
                $imagePath = $request->file('admin_image')->storeAs('public/admin_images', $imageName);

                // Update the admin's image path
                $admin->admin_image = 'admin_images/' . $imageName;
            }

            // Update the admin's information
            $admin->update($request->only(['name', 'email', 'type', 'role', 'status']));

            // Save any additional changes
            $admin->save();

            // Return a success response
            return response()->json([
                'message' => 'Admin updated successfully',
                'admin' => $admin->makeHidden(['password']),
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation errors
            return response()->json([
                'error' => 'Validation error',
                'message' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            // Log the exception details
            LogHelper::logError('Something went wrong', $e->getMessage(), 'update admin');
            // Handle general errors
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    // admins status changed
    public function toggleAdminStatus($id)
    {
        try {
            // Find the admin by ID
            $admin = admin::find($id);

            // Check if the admin exists
            if (!$admin) {
                return response()->json(['error' => 'Admin not found'], 404);
            }

            // Toggle the status between 1 (active) and 0 (inactive)
            $admin->status = ($admin->status == 1) ? 0 : 1;

            // Save the updated status
            $admin->save();

            // Return a success response
            return response()->json([
                'message' => 'Admin status updated successfully',
                'admin' => $admin,
            ], 200);
        } catch (\Exception $e) {
            // Log the exception details
            LogHelper::logError('Something went wrong', $e->getMessage(), 'update admin status');
            // Handle any errors
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // Admin delete
    public function deleteAdmin($id)
    {
        try {
            // Find the admin by ID
            $admin = admin::find($id);

            // Check if the admin exists
            if (!$admin) {
                return response()->json([
                    'error' => 'Admin not found',
                ], 404);
            }

            // Delete the admin
            $admin->delete();

            // Return a success response
            return response()->json([
                'message' => 'Admin deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            // Log the exception details
            LogHelper::logError('Something went wrong', $e->getMessage(), 'delete admin');
            // Handle any errors
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
