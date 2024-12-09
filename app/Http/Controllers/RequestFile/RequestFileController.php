<?php

namespace App\Http\Controllers\RequestFile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\RequestFile;
use App\Helpers\LogHelper;
use Illuminate\Support\Facades\Log;

class RequestFileController extends Controller
{
    //user request file upload
    public function uploadRequestFiles(Request $request)
    {
        try {
            // Validate the request
            $validated = $request->validate([
                'relatable_id' => 'required|integer',
                'file' => 'required|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:5048',
                'type' => 'required|string',
            ]);

            // Handle the file upload
            if ($request->hasFile('file')) {
                // Generate a unique file name
                $fileName = uniqid() . '.' . $request->file('file')->getClientOriginalExtension();

                // Store the file in the 'public/request_files' folder with the generated name
                $filePath = $request->file('file')->storeAs('public/request_files', $fileName);
            } else {
                return response()->json(['error' => 'File is required'], 422);
            }

            // Save file info in the database
            $requestFile = RequestFile::create([
                'relatable_id' => $validated['relatable_id'],
                'file' => $filePath,
                'user_id' => auth()->id(),
                'admin_id' => null,
                'type' => $validated['type'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded and saved successfully.',
                'data' => $requestFile,
            ], 201);
        } catch (\Exception $e) {
            // Log the error for debugging purposes
            Log::error('File upload failed: ' . $e->getMessage());
            // Log the exception details
            LogHelper::logError('Something went wrong', $e->getMessage(), 'user request file upload');
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your request. Please try again later.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
