<?php

namespace App\Http\Controllers;

use App\Services\EmailTrackingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Uri;

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
        $signature = $request->get('s');

        $expectedSig = $this->trackingService->generateLinkSignature($trackingId, (string) $encodedUrl);

        if (!hash_equals($expectedSig, (string) $signature)) {
            Log::warning("Invalid link signature for tracking: {$trackingId}");
            return redirect(config('app.url'));
        }

        $url = $this->trackingService->decodeTrackedUrl((string) $encodedUrl);

        if ($url === '') {
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
        $host = Uri::of($url)->host();

        if ($host === null) {
            return $url;
        }

        $appHost = Uri::of(config('app.url'))->host();

        if ($host === $appHost || str_ends_with($host, '.' . $appHost)) {
            return $url;
        }

        Log::warning("Blocked open redirect to untrusted domain: {$host}");

        return config('app.url');
    }
}
