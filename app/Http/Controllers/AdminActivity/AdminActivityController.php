<?php

namespace App\Http\Controllers\AdminActivity;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\adminactivity;
use Illuminate\Support\Facades\Log;

class AdminActivityController extends Controller
{
    //show all admin activity
    public function shwoAllActivity()
    {
        try {
            // Fetch all data from the adminactivities table
            $adminActivities = AdminActivity::all();

            // Return the data as a JSON response
            return response()->json([
                'success' => true,
                'data' => $adminActivities
            ], 200);
        } catch (\Exception $e) {
            // Log the error for debugging purposes
            Log::error('Error fetching admin activities: ' . $e->getMessage());

            // Return an error JSON response
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch admin activities. Please try again later.'
            ], 500);
        }
    }



    // shwo activities by id
    public function showActivityByid($id)
    {
        try {
            // Fetch the admin activity by ID with the related admin
            $activity = adminactivity::with('admin:id,name,email')->find($id);

            // Check if the activity exists
            if (!$activity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Activity not found.'
                ], 404);
            }

            // Return the activity with admin details
            return response()->json([
                'success' => true,
                'data' => $activity
            ], 200);
        } catch (\Exception $e) {
            // Handle exceptions
            Log::error('Error fetching admin activity: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred. Please try again later.'
            ], 500);
        }
    }
}
