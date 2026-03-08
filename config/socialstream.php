<?php

use JoelButcher\Socialstream\Features;
use JoelButcher\Socialstream\Providers;

return [
    'middleware' => ['web'],
    'prompt' => 'Or Login Via',
    'providers' => [
        Providers::facebook(),
        Providers::google(),
        Providers::linkedinOpenId(),
        Providers::twitterOAuth2(),
    ],
    'features' => [
        Features::createAccountOnFirstLogin(),
        Features::loginOnRegistration(),
        Features::globalLogin(),
    ],
    'component' => 'socialstream::components.socialstream',
];
