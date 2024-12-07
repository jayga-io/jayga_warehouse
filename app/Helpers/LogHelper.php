<?php

namespace App\Helpers;

use App\Models\LogStatus;
use Illuminate\Support\Facades\Log;

class LogHelper
{
    /**
     * Log an error in the `log_statuses` table.
     *
     * @param string $response
     * @param mixed $message
     * @param string $functionName
     */
    public static function logError(string $response, $message, string $functionName): void
    {
        try {
            LogStatus::create([
                'response' => $response,
                'api_name' => $functionName,
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            // Optionally log to a file or handle errors occurring during logging
            Log::error("Failed to log error: " . $e->getMessage());
        }
    }
}
