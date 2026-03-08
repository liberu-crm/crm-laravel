<?php

/**
 * Stub classes for Microsoft Graph SDK (used in tests when the actual SDK is not installed).
 */

namespace Microsoft\Graph {
    if (!class_exists(\Microsoft\Graph\Graph::class)) {
        class Graph
        {
            protected $accessToken;

            public function setAccessToken(string $token): static
            {
                $this->accessToken = $token;
                return $this;
            }

            public function createRequest(string $method, string $uri): object
            {
                return new class ($method, $uri) {
                    public function __construct(private string $method, private string $uri) {}
                    public function attachBody($body): static { return $this; }
                    public function setReturnType(string $class): static { return $this; }
                    public function execute() { return null; }
                };
            }
        }
    }
}

namespace Microsoft\Graph\Model {
    if (!class_exists(\Microsoft\Graph\Model\Event::class)) {
        class Event
        {
            protected $data = [];

            public function __construct(array $data = [])
            {
                $this->data = $data;
            }

            public function getId(): ?string
            {
                return $this->data['id'] ?? null;
            }

            public function setSubject(?string $subject): void
            {
                $this->data['subject'] = $subject;
            }

            public function getSubject(): ?string
            {
                return $this->data['subject'] ?? null;
            }

            public function setBody($body): void
            {
                $this->data['body'] = $body;
            }

            public function getBody(): ?object
            {
                $body = $this->data['body'] ?? null;
                if (is_array($body)) {
                    return new class ($body) {
                        public function __construct(private array $data) {}
                        public function getContent(): ?string { return $this->data['content'] ?? null; }
                    };
                }
                return $body;
            }

            public function setStart($start): void
            {
                $this->data['start'] = $start;
            }

            public function getStart(): ?object
            {
                $start = $this->data['start'] ?? null;
                if (is_array($start)) {
                    return new class ($start) {
                        public function __construct(private array $data) {}
                        public function getDateTime(): ?string { return $this->data['dateTime'] ?? null; }
                    };
                }
                return $start;
            }

            public function setEnd($end): void
            {
                $this->data['end'] = $end;
            }
        }
    }

    if (!class_exists(\Microsoft\Graph\Model\ItemBody::class)) {
        class ItemBody
        {
            protected $data = [];

            public function __construct(array $data = [])
            {
                $this->data = $data;
            }

            public function getContent(): ?string
            {
                return $this->data['content'] ?? null;
            }
        }
    }

    if (!class_exists(\Microsoft\Graph\Model\DateTimeTimeZone::class)) {
        class DateTimeTimeZone
        {
            protected $data = [];

            public function __construct(array $data = [])
            {
                $this->data = $data;
            }

            public function getDateTime(): ?string
            {
                return $this->data['dateTime'] ?? null;
            }
        }
    }
}
