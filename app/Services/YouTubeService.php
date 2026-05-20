<?php

namespace App\Services;

use App\Models\ConnectedAccount;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class YouTubeService
{
    protected string $apiUrl = 'https://www.googleapis.com/youtube/v3/';

    protected string $uploadUrl = 'https://www.googleapis.com/upload/youtube/v3/';

    public function uploadVideo(ConnectedAccount $account, string $videoPath, string $title, string $description): array
    {
        if (!file_exists($videoPath)) {
            throw new Exception("Video file not found: {$videoPath}");
        }

        // Step 1: Initiate a resumable upload session
        $initResponse = Http::withToken($account->token)
            ->withHeaders([
                'X-Upload-Content-Type' => 'video/*',
                'X-Upload-Content-Length' => filesize($videoPath),
            ])
            ->post($this->uploadUrl . 'videos?uploadType=resumable&part=snippet,status', [
                'snippet' => [
                    'title' => mb_substr($title, 0, 100),
                    'description' => $description,
                    'categoryId' => '22', // People & Blogs
                ],
                'status' => [
                    'privacyStatus' => 'public',
                ],
            ]);

        if (!$initResponse->successful()) {
            Log::error('YouTube API Error initializing upload: ' . $initResponse->body());
            throw new Exception('Failed to initialize YouTube upload: ' . $initResponse->body());
        }

        $uploadUri = $initResponse->header('Location');

        if (empty($uploadUri)) {
            throw new Exception('YouTube upload URI not returned from API');
        }

        // Step 2: Upload the video file content using a stream to avoid loading the entire file into memory
        $stream = fopen($videoPath, 'r');
        if ($stream === false) {
            throw new Exception("Failed to open video file for reading: {$videoPath}");
        }

        try {
            $uploadResponse = Http::withToken($account->token)
                ->withHeaders([
                    'Content-Type' => 'video/*',
                    'Content-Length' => filesize($videoPath),
                ])
                ->withBody($stream, 'video/*')
                ->put($uploadUri);
        } finally {
            fclose($stream);
        }

        if (!$uploadResponse->successful()) {
            Log::error('YouTube API Error uploading video: ' . $uploadResponse->body());
            throw new Exception('Failed to upload video to YouTube: ' . $uploadResponse->body());
        }

        return $uploadResponse->json();
    }

    public function getAllConnectedAccounts()
    {
        return ConnectedAccount::ofType('youtube')->get();
    }

    public function getPrimaryAccount()
    {
        return ConnectedAccount::ofType('youtube')->primary()->first();
    }
}
