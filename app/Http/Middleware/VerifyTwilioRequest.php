<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Twilio\Security\RequestValidator;

class VerifyTwilioRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $authToken = config('services.twilio.auth_token');

        if (! $authToken) {
            return $next($request);
        }

        $signature = $request->header('X-Twilio-Signature');

        if (! $signature) {
            abort(403, 'Missing Twilio signature.');
        }

        $validator = new RequestValidator($authToken);

        if (! $validator->validate($signature, $request->fullUrl(), $request->except('Signature'))) {
            abort(403, 'Invalid Twilio signature.');
        }

        return $next($request);
    }
}
