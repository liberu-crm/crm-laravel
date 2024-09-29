<?php

namespace App\Http\Controllers;

use App\Models\OAuthConfiguration;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\Request;

class OAuthController extends Controller
{
    public function redirect($provider)
    {
        $config = OAuthConfiguration::getConfig($provider);

        if (!$config) {
            return redirect()->route('login')->with('error', 'OAuth provider not configured.');
        }

        return Socialite::driver($provider)->redirect();
    }

    public function callback($provider)
    {
        $config = OAuthConfiguration::getConfig($provider);

        if (!$config) {
            return redirect()->route('login')->with('error', 'OAuth provider not configured.');
        }

        $user = Socialite::driver($provider)->user();

        // Here you would typically find or create a user based on the OAuth data
        // and log them in. For brevity, we'll just return the user object.
        return response()->json($user);
    }
}