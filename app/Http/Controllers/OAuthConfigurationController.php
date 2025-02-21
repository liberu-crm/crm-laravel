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
}<?php

namespace App\Http\Controllers;

use App\Models\OAuthConfiguration;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;

class OAuthConfigurationController extends Controller
{
    public function index()
    {
        $configurations = OAuthConfiguration::where('user_id', Auth::id())->get();
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
            'account_name' => 'required|string',
            'additional_settings' => 'nullable|array'
        ]);

        $config = new OAuthConfiguration($validated);
        $config->user_id = Auth::id();
        $config->save();

        return redirect()->route('oauth.authenticate', ['service' => $config->service_name, 'config_id' => $config->id]);
    }

    public function authenticate($service, $configId)
    {
        session(['oauth_config_id' => $configId]);
        return Socialite::driver($service)->redirect();
    }

    public function callback($service)
    {
        try {
            $configId = session('oauth_config_id');
            $config = OAuthConfiguration::findOrFail($configId);
            
            $socialiteUser = Socialite::driver($service)->user();
            
            $config->update([
                'access_token' => $socialiteUser->token,
                'refresh_token' => $socialiteUser->refreshToken,
                'token_expires_at' => now()->addSeconds($socialiteUser->expiresIn),
                'is_active' => true,
                'additional_settings' => array_merge($config->additional_settings ?? [], [
                    'email' => $socialiteUser->email,
                    'avatar' => $socialiteUser->avatar,
                    'provider_id' => $socialiteUser->id
                ])
            ]);

            return redirect()->route('oauth.configurations.index')
                ->with('success', 'Account connected successfully!');

        } catch (\Exception $e) {
            return redirect()->route('oauth.configurations.index')
                ->with('error', 'Failed to connect account: ' . $e->getMessage());
        }
    }

    public function destroy(OAuthConfiguration $configuration)
    {
        $configuration->delete();
        return redirect()->route('oauth.configurations.index')
            ->with('success', 'Configuration removed successfully');
    }
}