@php
    $providers = \App\Models\OAuthConfiguration::all();
@endphp

<div class="social-login">
    <h2>Login with Social Media</h2>
    @foreach ($providers as $provider)
        <a href="{{ route('oauth.redirect', $provider->service_name) }}" class="btn btn-{{ $provider->service_name }}">
            Login with {{ ucfirst($provider->service_name) }}
        </a>
    @endforeach
</div>