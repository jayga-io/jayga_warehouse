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
            $admin = admin::where('email', $request->email)->first();

            // Check if admin exists
            if (!$admin) {
                throw ValidationException::withMessages([
                    'email' => ['The provided email does not exist.'],
                ]);
            }

            // Check if the admin's status is 0 (inactive)
            if ($admin->status == 0) {
                return response()->json([
                    'error' => 'Your account is not valid. Please contact the super admin.',
                    'status' => false, // Returning status as false
                ], 403); // 403 Forbidden status code
            }

            // Check if the password is correct
            if (!Hash::check($request->password, $admin->password)) {
                throw ValidationException::withMessages([
                    'password' => ['The provided credentials are incorrect.'],
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

    // update the login admin profile
    public function updateProfile(Request $request)
    {
        try {
            // Get the authenticated admin
            $admin = $request->user();

            // Validate input
            $request->validate([
                'name' => 'string|max:255',
                'email' => 'string|email|max:255|unique:admins,email,' . $admin->id,
                'type' => 'string',
                'role' => 'string',
                'admin_image' => 'image|mimes:jpeg,png,jpg,gif|max:2048', // Validate optional image
            ]);

            // Handle the image upload if provided
            if ($request->hasFile('admin_image')) {
                // Generate a unique file name
                $imageName = uniqid() . '.' . $request->file('admin_image')->getClientOriginalExtension();

                // Store the new image in 'public/admin_images'
                $imagePath = $request->file('admin_image')->storeAs('public/admin_images', $imageName);

                // Update admin image path
                $admin->admin_image = 'admin_images/' . $imageName;
            }

            // Update the admin's profile information
            $admin->update($request->only(['name', 'email', 'type', 'role']));

            // Save the changes
            $admin->save();

            // Return success response
            return response()->json([
                'message' => 'Profile updated successfully',
                'admin' => $admin,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation exceptions
            Log::error('Validation error during profile update: ' . $e->getMessage());
            return response()->json([
                'error' => 'Validation error',
                'message' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            // Handle any other general exceptions
            Log::error('Error during profile update: ' . $e->getMessage());
            return response()->json([
                'error' => 'Something went wrong',
                'message' => 'An unexpected error occurred. Please try again.'
            ], 500);
        }
    }

    // change login admin password
    public function changePassword(Request $request)
    {
        try {
            // Validate the request data
            $request->validate([
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:8|confirmed', // Ensure 'new_password_confirmation' is sent
            ]);

            // Get the authenticated admin
            $admin = $request->user();

            // Check if the current password matches
            if (!Hash::check($request->current_password, $admin->password)) {
                throw ValidationException::withMessages([
                    'current_password' => ['The provided password does not match our records.'],
                ]);
            }

            // Update the admin's password
            $admin->update([
                'password' => Hash::make($request->new_password),
            ]);

            // Return success response
            return response()->json([
                'message' => 'Password updated successfully.',
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Validation error',
                'message' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
