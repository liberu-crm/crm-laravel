<?php

namespace App\Http\Controllers;

use Google_Client;
use Google_Service_Calendar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GoogleCalendarController extends Controller
{
    public function redirectToGoogle()
    {
        $client = new Google_Client();
        $client->setAuthConfig(config('services.google.credentials_path'));
        $client->addScope(Google_Service_Calendar::CALENDAR);
        $client->setRedirectUri(route('google.callback'));

        return redirect($client->createAuthUrl());
    }

    public function handleGoogleCallback(Request $request)
    {
        $client = new Google_Client();
        $client->setAuthConfig(config('services.google.credentials_path'));
        $client->setRedirectUri(route('google.callback'));

        if ($request->get('code')) {
            $token = $client->fetchAccessTokenWithAuthCode($request->get('code'));
            $client->setAccessToken($token);

            Auth::user()->update(['google_calendar_token' => json_encode($token)]);

            return redirect()->route('tasks.index')->with('success', 'Google Calendar connected successfully.');
        }

        return redirect()->route('tasks.index')->with('error', 'Failed to connect Google Calendar.');
    }
}