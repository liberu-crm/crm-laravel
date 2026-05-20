<?php

namespace App\Http\Controllers;

use App\Services\EmailTrackingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmailTrackingController extends Controller
{
    protected $trackingService;

    public function __construct(EmailTrackingService $trackingService)
    {
        $this->trackingService = $trackingService;
    }

    /**
     * Serve tracking pixel and record email open
     */
    public function pixel(Request $request, string $trackingId)
    {
        try {
            $this->trackingService->recordOpen(
                $trackingId,
                $request->header('User-Agent'),
                $request->ip()
            );
        } catch (\Exception $e) {
            Log::error("Error recording email open: " . $e->getMessage());
        }

        // Return 1x1 transparent GIF
        $gif = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        
        return response($gif)
            ->header('Content-Type', 'image/gif')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Track link clicks and redirect
     */
    public function link(Request $request, string $trackingId)
    {
        $encodedUrl = $request->get('url');
        $url = base64_decode($encodedUrl);

        try {
            $this->trackingService->recordClick(
                $trackingId,
                $url,
                $request->header('User-Agent'),
                $request->ip()
            );
        } catch (\Exception $e) {
            Log::error("Error recording link click: " . $e->getMessage());
        }

        return redirect($url);
    }
}
