<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Models\admin;
use Illuminate\Support\Facades\Log;

class AdminAuthContorller extends Controller
{
    //Register admin
    public function register(Request $request)
    {
        try {
            // Validate input
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:admins,email',
                'password' => 'required|string|min:8',
                'type' => 'required|string',
                'role' => 'required|string',
                'admin_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // Validate image
            ]);

            // Handle the image upload
            if ($request->hasFile('admin_image')) {
                // Generate a unique file name
                $imageName = uniqid() . '.' . $request->file('admin_image')->getClientOriginalExtension();

                // Store the image in the 'public/admin_images' folder with the generated name
                $imagePath = $request->file('admin_image')->storeAs('public/admin_images', $imageName);
            } else {
                return response()->json(['error' => 'Admin image is required'], 422);
            }

            // Create a new admin
            $admin = Admin::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'type' => $request->type,
                'role' => $request->role,
                'admin_image' => 'admin_images/' . $imageName, // Save the file path in the database
                'status' => $request->status ?? '1', // Default status
            ]);

            // Return success response
            return response()->json([
                'message' => 'Admin registered successfully',
                'admin' => $admin,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation exceptions
            Log::error('Validation error during admin registration: ' . $e->getMessage());
            return response()->json([
                'error' => 'Validation error',
                'message' => $e->getMessage()
            ], 422);
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle database-related exceptions
            Log::error('Database error during admin registration: ' . $e->getMessage());
            return response()->json([
                'error' => 'Database error',
                'message' => 'An error occurred while saving the admin details. Please try again.'
            ], 500);
        } catch (\Exception $e) {
            // Handle any other general exceptions
            Log::error('General error during admin registration: ' . $e->getMessage());
            return response()->json([
                'error' => 'Something went wrong',
                'message' => 'An unexpected error occurred. Please try again.'
            ], 500);
        }
    }


    // Admin login
    public function login(Request $request)
    {
        try {
            // Validate incoming request data (email and password)
            $request->validate([
                'email' => 'required|email',
                'password' => 'required|string|min:8',
            ]);

            // Attempt to find the admin by email
            $admin = Admin::where('email', $request->email)->first();

            // Check if admin exists and password is correct
            if (!$admin || !Hash::check($request->password, $admin->password)) {
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }

            // Generate a new token for the authenticated admin
            $token = $admin->createToken('AdminAppToken')->plainTextToken;

            // Hide the password field
            $admin->makeHidden('password');

            // Return a response with the token
            return response()->json([
                'message' => 'Login successful',
                'token' => $token,
                'admin' => $admin,
            ], 200);
        } catch (\Exception $e) {
            // Return a response with error message
            return response()->json([
                'error' => 'Something went wrong. Please try again.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

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
    // Admin logout
    public function logout(Request $request)
    {
        try {
            // Revoke the user's current token (logout the user)
            Auth::user()->tokens->each(function ($token) {
                $token->delete();
            });

            // Return a response confirming the logout
            return response()->json([
                'message' => 'Successfully logged out Admin'
            ], 200);
        } catch (\Exception $e) {
            // Log the error if any
            Log::error('Error logging out: ' . $e->getMessage());

            // Return an error response
            return response()->json([
                'error' => 'Failed to log out',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
