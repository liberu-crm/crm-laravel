<?php

/**
 * Stub classes for Twilio SDK (used in tests when the actual SDK is not installed).
 *
 * These stubs replace the real SDK so that Mockery can create mocks that work
 * with Demeter chain expectations such as shouldReceive('messages->create').
 *
 * IMPORTANT: The Client stub must NOT declare $messages/$calls/$recordings as
 * real properties. It MUST use __get() so Mockery can intercept property access
 * through its own __get hook (which powers Demeter chain resolution).
 */

namespace Twilio\Exceptions {
    if (!class_exists(\Twilio\Exceptions\TwilioException::class)) {
        class TwilioException extends \RuntimeException {}
    }
    if (!class_exists(\Twilio\Exceptions\RestException::class)) {
        class RestException extends TwilioException {}
    }
}

namespace Twilio\Rest {
    if (!class_exists(\Twilio\Rest\Client::class)) {
        class Client
        {
            public function __construct(string $username = '', string $password = '', ?string $accountSid = null) {}

            /**
             * Expose dynamic properties via __get so Mockery can intercept them
             * for Demeter chain expectations (e.g. shouldReceive('messages->create')).
             */
            public function __get(string $name)
            {
                return null;
            }
        }
    }
}

namespace Twilio\Rest\Api\V2010\Account {
    if (!class_exists(\Twilio\Rest\Api\V2010\Account\CallList::class)) {
        class CallList
        {
            public function read(array $options = []): array { return []; }
            public function create(string $to, string $from, array $options = []) {}
        }
    }

    if (!class_exists(\Twilio\Rest\Api\V2010\Account\CallInstance::class)) {
        class CallInstance
        {
            public function fetch() { return $this; }
        }
    }
}

namespace Twilio\Rest\Api\V2010\Account\Call {
    if (!class_exists(\Twilio\Rest\Api\V2010\Account\Call\RecordingInstance::class)) {
        class RecordingInstance
        {
            public function update(array $options = []) {}
        }
    }

    if (!class_exists(\Twilio\Rest\Api\V2010\Account\Call\RecordingList::class)) {
        class RecordingList
        {
            public function create(array $options = []) {}
            public function read(array $options = []): array { return []; }
        }
    }
}
