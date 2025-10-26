<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SimpaskorController extends Controller
{
    /**
     * Get Simpaskor schedule by proxying the external API
     * This avoids CORS issues when calling from frontend
     */
    public function getSchedule()
    {
        try {
            // Fetch from Simpaskor API
            $response = Http::timeout(10)->get('https://simpaskor.id/api/landing_page.php');

            if ($response->successful()) {
                return response()->json($response->json(), 200);
            }

            // If request failed, return error
            Log::error('Simpaskor API request failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return response()->json([
                'error' => 'Failed to fetch schedule from Simpaskor',
                'status' => $response->status()
            ], $response->status());

        } catch (\Exception $e) {
            Log::error('Simpaskor API exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to connect to Simpaskor API',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
