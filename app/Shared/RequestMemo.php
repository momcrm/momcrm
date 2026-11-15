<?php

declare(strict_types=1);

namespace App\Shared;

use Closure;

/**
 * Remembers a value for the rest of the current request, on the request
 * itself, so the next request (or the next HTTP call in a test) starts fresh.
 */
final class RequestMemo
{
    private const string PREFIX = 'request-memo.';

    /**
     * @template TValue
     *
     * @param  Closure(): TValue  $callback
     * @return TValue
     */
    public static function remember(string $key, Closure $callback): mixed
    {
        $attributes = request()->attributes;
        $key = self::PREFIX.$key;

        if (! $attributes->has($key)) {
            $attributes->set($key, [$callback()]);
        }

        /** @var array{0: TValue} $stored */
        $stored = $attributes->get($key);

        return $stored[0];
    }

    /**
     * Forgets every remembered value whose key starts with the prefix.
     */
    public static function forget(string $prefix): void
    {
        $attributes = request()->attributes;

        foreach (array_keys($attributes->all()) as $key) {
            if (str_starts_with($key, self::PREFIX.$prefix)) {
                $attributes->remove($key);
            }
        }
    }
}
