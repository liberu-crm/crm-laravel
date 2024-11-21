

<?php

namespace App\Http\Controllers;

use App\Models\OAuthConfiguration;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

class OAuthConfigurationController extends Controller
{
    public function index()
    {
        $configurations = OAuthConfiguration::all();
        return view('oauth.configurations.index', compact('configurations'));
    }

    public function create()
    {
        return view('oauth.configurations.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_name' => 'required|string',
            'client_id' => 'required|string',
            'client_secret' => 'required|string',
            'additional_settings' => 'nullable|array'
        ]);

        OAuthConfiguration::create($validated);

        return redirect()->route('oauth.configurations.index')
            ->with('success', 'OAuth configuration created successfully');
    }

    public function redirectToProvider($provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    public function handleProviderCallback($provider)
    {
        $user = Socialite::driver($provider)->user();
        
        // Store the token in connected accounts
        auth()->user()->connectedAccounts()->create([
            'provider' => $provider,
            'provider_id' => $user->getId(),
            'token' => $user->token,
            'refresh_token' => $user->refreshToken,
            'expires_at' => isset($user->expiresIn) ? now()->addSeconds($user->expiresIn) : null,
        ]);

        return redirect()->route('dashboard')
            ->with('success', 'Email account connected successfully');
    }
}