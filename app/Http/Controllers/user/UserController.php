<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Helpers\LogHelper;
use Illuminate\Support\Facades\DB;



class UserController extends Controller
{
    //Register user
    public function register(Request $request)
    {
        try {
            // Validate the input
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email|max:255',
                'phone' => 'required|string|max:20|unique:users,phone',
                'password' => 'nullable|string|min:8',
                'company_name' => 'nullable|string|max:255',
                'industry_type' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                LogHelper::logError('Validation failed', $validator->errors(), 'user register');
                return response()->json([
                    'error' => 'Validation failed',
                    'messages' => $validator->errors(),
                ], 422);
            }

            // If password is not provided, set it to the phone number
            $password = $request->password ? $request->password : $request->phone;

            // Create the user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($password),
                'company_name' => $request->company_name,
                'industry_type' => $request->industry_type,
                'status' => '1',
            ]);

            // Generate an API token for the user (if applicable)
            $token = $user->createToken('API Token')->plainTextToken;

            return response()->json([
                'message' => 'User registered successfully',
                'user' => $user,
                'token' => $token,
            ], 201);
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle database-related exceptions
            return response()->json([
                'error' => 'Database error',
                'message' => $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            // Log the exception details
            LogHelper::logError('Something went wrong', $e->getMessage(), 'user register');

            // Handle general exceptions
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }



    // user login
    public function login(Request $request)
    {
        try {
            // Validate the input
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string|min:8',
            ]);

            if ($validator->fails()) {
                LogHelper::logError('Validation failed', $validator->errors(), 'user login');
                return response()->json([
                    'error' => 'Validation failed',
                    'messages' => $validator->errors(),
                ], 422);
            }

            // Check if user exists with the provided email
            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                LogHelper::logError('Invalid credentials', 'The provided email or password is incorrect.', 'user login');
                return response()->json([
                    'error' => 'Invalid credentials',
                    'message' => 'The provided email or password is incorrect.',
                ], 401);
            }

            // Generate an API token for the authenticated user
            $token = $user->createToken('API Token')->plainTextToken;

            return response()->json([
                'message' => 'Login successful',
                'user' => $user,
                'token' => $token,
            ], 200);
        } catch (\Exception $e) {
            // Log the exception details
            LogHelper::logError('Something went wrong', $e->getMessage(), 'user login');

            // Handle general exceptions
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }



    // shwo login user information
    public function getUserInfo()
    {
        try {
            // Retrieve the currently authenticated user
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'error' => 'User not authenticated',
                ], 401);
            }

            // Return the authenticated user's information
            return response()->json([
                'message' => 'User information retrieved successfully',
                'user' => $user,
            ], 200);
        } catch (\Exception $e) {
            // Handle general exceptions
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }



    // Logout the user
    public function logout(Request $request)
    {
        try {
            // Revoke the user's token (logout for API authentication)
            $request->user()->tokens->each(function ($token) {
                $token->delete(); // Delete each token associated with the user
            });

            return response()->json([
                'message' => 'User logged out successfully',
            ], 200);
        } catch (\Exception $e) {
            // Handle general exceptions
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }



    // show all user
    public function getAllUsers()
    {
        try {
            // Paginate the users and show 15 per page
            $users = User::select([
                'id',
                'name',
                'email',
                'phone',
                'company_name',
                'industry_type',
                'status',
                'is_suspended',
                'fcm_token',
                'auth_token',
                'profile_image',
                'description',
                'address',
                'latitude',
                'longitude',
                'created_at',
                'updated_at',
            ])->paginate(15); // 15 users per page

            // Return the data with pagination information
            return response()->json([
                'message' => 'Users fetched successfully.',
                'data' => $users,
            ], 200);
        } catch (\Exception $e) {
            // Handle errors
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }



    // show single user by id
    public function showUser($id)
    {
        try {
            // Fetch the user by its ID
            $user = User::select([
                'id',
                'name',
                'email',
                'phone',
                'company_name',
                'industry_type',
                'status',
                'is_suspended',
                'fcm_token',
                'auth_token',
                'profile_image',
                'description',
                'address',
                'latitude',
                'longitude',
                'created_at',
                'updated_at',
            ])->findOrFail($id);

            // Return the user data
            return response()->json([
                'message' => 'User fetched successfully.',
                'data' => $user,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Handle case where user is not found
            return response()->json([
                'error' => 'User not found',
                'message' => 'The user with the given ID does not exist.',
            ], 404);
        } catch (\Exception $e) {
            // Handle other exceptions
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }



    // update user info
    public function updateuserprofile(Request $request, $id)
    {
        try {
            // Validate the incoming data
            $request->validate([
                'name' => 'string|max:255',
                'email' => 'email|unique:users,email,' . $id,
                'phone' => 'string|max:15',
                'company_name' => 'string|nullable|max:255',
                'industry_type' => 'string|nullable|max:255',
                'description' => 'string|nullable|max:500',
                'address' => 'string|nullable|max:255',
            ]);

            // Find the user
            $user = User::findOrFail($id);

            // Update the user's information
            $user->update($request->only([
                'name',
                'email',
                'phone',
                'company_name',
                'industry_type',
                'description',
                'address',
            ]));

            return response()->json([
                'message' => 'User updated successfully',
                'user' => $user,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'User not found.',
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation error.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            // Log the exception details
            LogHelper::logError('Something went wrong', $e->getMessage(), 'user profile update');
            return response()->json([
                'message' => 'An error occurred while updating the user.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    // change password
    public function changePassword(Request $request)
    {
        try {
            // Validate the request data
            $request->validate([
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:8|confirmed',
            ]);

            // Get the authenticated user
            $user = auth()->user();
            if (!$user) {
                return response()->json(['message' => 'User not authenticated'], 401);
            }

            // Check if the current password is correct
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'message' => 'Current password is incorrect.',
                ], 400);
            }

            // Hash the new password
            $hashedPassword = Hash::make($request->new_password);

            // Update the password using raw SQL
            DB::update('UPDATE users SET password = ? WHERE id = ?', [$hashedPassword, $user->id]);

            return response()->json([
                'message' => 'Password changed successfully.',
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation error.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            // Log the exception details
            LogHelper::logError('Something went wrong', $e->getMessage(), 'user password changed');
            return response()->json([
                'message' => 'An error occurred while changing the password.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
