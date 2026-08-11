<?php

namespace Canopus\SmsApi;

final class SmsResponse
{
    private function __construct(
        public readonly bool $successful,
        public readonly ?string $messageId,
        public readonly ?string $errorMessage,
        public readonly array $raw = [],
    ) {
    }

    public static function success(?string $messageId, array $raw = []): self
    {
        return new self(true, $messageId, null, $raw);
    }

    public static function failure(?string $errorMessage, array $raw = []): self
    {
        return new self(false, null, $errorMessage, $raw);
    }

    public function successful(): bool
    {
        return $this->successful;
    }

    public function failed(): bool
    {
        return ! $this->successful;
    }
}
