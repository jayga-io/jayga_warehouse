<?php

namespace App\Http\Controllers\logstatus;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LogStatus;

class LogStatusController extends Controller
{
    //show all log status
    public function showLogs()
    {
        try {
            // Fetch all log statuses
            $logs = LogStatus::all();

            // Return the data
            return response()->json([
                'message' => 'Logs fetched successfully.',
                'data' => $logs,
            ], 200);
        } catch (\Exception $e) {
            // Handle errors
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
