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
        $url = base64_decode($encodedUrl, true);

        if ($url === false || $url === '') {
            $url = config('app.url');
        }

        $safeUrl = $this->validateRedirectUrl($url);

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

        return redirect($safeUrl);
    }

    private function validateRedirectUrl(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);

        if ($host === null) {
            return $url;
        }

        $appHost = parse_url(config('app.url'), PHP_URL_HOST);

        if ($host === $appHost || str_ends_with($host, '.' . $appHost)) {
            return $url;
        }

        Log::warning("Blocked open redirect to untrusted domain: {$host}");

        return config('app.url');
    }
}
