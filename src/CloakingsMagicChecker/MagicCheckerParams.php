<?php

namespace Cloakings\CloakingsMagicChecker;

use Symfony\Component\HttpFoundation\Request;

class MagicCheckerParams
{
    public function __construct(
        private readonly array $items,
    ) {
    }

    public static function createFromRequest(Request $request): self
    {
        $items = [];
        foreach ($request->server->all() as $key => $value) {
            $items[$key] = self::normalizeValue($value, $key);
        }

        return new self($items);
    }

    public function all(): array
    {
        return $this->items;
    }

    private static function normalizeValue(mixed $value, mixed $key): string
    {
        if (is_array($value)) {
            $value = implode(', ', $value);
        }

        if (
            strlen($value) >= 1024 &&
            !in_array($key, ['HTTP_REFERER', 'QUERY_STRING', 'REQUEST_URI', 'HTTP_USER_AGENT'], true)
        ) {
            $value = 'TRIMMED: '.substr($value, 0, 1024);
        }

        return $value;
    }
}
