<?php

namespace App\Services\OAuth;

use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\User;

class TwilioProvider extends AbstractProvider implements ProviderInterface
{
    protected $scopes = ['openid'];
    protected $scopeSeparator = ' ';

    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://accounts.twilio.com/oauth2/v1/authorize', $state);
    }

    protected function getTokenUrl()
    {
        return 'https://accounts.twilio.com/oauth2/v1/token';
    }

    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://accounts.twilio.com/v1/userinfo', [
            'headers' => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id' => $user['sub'],
            'name' => $user['name'] ?? null,
            'email' => $user['email'] ?? null,
            'account_sid' => $user['account_sid'] ?? null,
        ]);
    }

    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
        ]);
    }
}