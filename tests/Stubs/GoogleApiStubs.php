<?php

/**
 * Stub classes for Google API Client (used in tests when the actual SDK is not installed).
 */

if (!class_exists('Google_Client')) {
    class Google_Client
    {
        public function setAuthConfig($config): void {}
        public function addScope($scope): void {}
    }
}

if (!class_exists('Google_Service_Calendar')) {
    class Google_Service_Calendar
    {
        const CALENDAR = 'https://www.googleapis.com/auth/calendar';

        public $events;

        public function __construct($client = null)
        {
            $this->events = null;
        }
    }
}

if (!class_exists('Google_Service_Calendar_Event')) {
    class Google_Service_Calendar_Event
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

        public function getSummary(): ?string
        {
            return $this->data['summary'] ?? null;
        }

        public function setSummary(?string $summary): void
        {
            $this->data['summary'] = $summary;
        }

        public function getDescription(): ?string
        {
            return $this->data['description'] ?? null;
        }

        public function setDescription(?string $description): void
        {
            $this->data['description'] = $description;
        }

        public function getStart()
        {
            if (isset($this->data['start'])) {
                return (object) $this->data['start'];
            }
            return null;
        }

        public function setStart($start): void
        {
            $this->data['start'] = is_array($start) ? $start : (array) $start;
        }

        public function setEnd($end): void
        {
            $this->data['end'] = is_array($end) ? $end : (array) $end;
        }
    }
}

if (!class_exists('Google_Service_Calendar_Events')) {
    class Google_Service_Calendar_Events
    {
        protected $items = [];

        public function __construct(array $data = [])
        {
            $this->items = $data['items'] ?? [];
        }

        public function getItems(): array
        {
            return $this->items;
        }
    }
}
