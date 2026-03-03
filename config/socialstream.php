<?php

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
    'component' => 'socialstream::components.socialstream',
];
