<?php

namespace App\Services;

use Facebook\Facebook;
use Illuminate\Support\Facades\Config;

class FacebookService
{
    protected $fb;

    public function __construct()
    {
        $config = Config::get('services.facebook');
        $this->fb = new Facebook([
            'app_id' => $config['app_id'],
            'app_secret' => $config['app_secret'],
            'default_graph_version' => $config['graph_version'],
        ]);
    }

    public function publishPost($content)
    {
        $config = Config::get('services.facebook');
        $pageAccessToken = $config['page_access_token'];
        $pageId = $config['page_id'];

        try {
            $response = $this->fb->post("/{$pageId}/feed", ['message' => $content], $pageAccessToken);
            return $response->getGraphNode();
        } catch(\Facebook\Exceptions\FacebookResponseException $e) {
            throw new \Exception('Graph returned an error: ' . $e->getMessage());
        } catch(\Facebook\Exceptions\FacebookSDKException $e) {
            throw new \Exception('Facebook SDK returned an error: ' . $e->getMessage());
        }
    }

    public function getPostInsights($postId)
    {
        $config = Config::get('services.facebook');
        $pageAccessToken = $config['page_access_token'];

        try {
            $response = $this->fb->get("/{$postId}/insights?metric=post_impressions,post_engagements", $pageAccessToken);
            return $response->getGraphEdge();
        } catch(\Facebook\Exceptions\FacebookResponseException $e) {
            throw new \Exception('Graph returned an error: ' . $e->getMessage());
        } catch(\Facebook\Exceptions\FacebookSDKException $e) {
            throw new \Exception('Facebook SDK returned an error: ' . $e->getMessage());
        }
    }
}