<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Helpers\LogHelper;


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
}
