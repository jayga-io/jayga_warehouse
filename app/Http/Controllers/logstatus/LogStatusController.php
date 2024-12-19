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
            // Paginate the logs and show 15 per page
            $logs = LogStatus::paginate(15);

            // Return the data with pagination information
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



    // show single logstatus by id
    public function showLogStatus($id)
    {
        try {
            // Fetch the log status by its ID
            $log = LogStatus::findOrFail($id);

            // Return the data
            return response()->json([
                'message' => 'Log status fetched successfully.',
                'data' => $log,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Handle case where log is not found
            return response()->json([
                'error' => 'Log not found',
                'message' => 'The log status with the given ID does not exist.',
            ], 404);
        } catch (\Exception $e) {
            // Handle other exceptions
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
