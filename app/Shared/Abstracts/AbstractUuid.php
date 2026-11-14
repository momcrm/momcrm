<?php

declare(strict_types=1);

namespace App\Shared\Abstracts;

use Illuminate\Support\Str;
use Mom\Data\AbstractString;
use Ramsey\Uuid\UuidInterface;

/**
 * A UUID-valued property. New identifiers are time-ordered UUIDv7s.
 */
abstract class AbstractUuid extends AbstractString
{
    public static function generate(): static
    {
        return static::fromUuidInterface(Str::uuid7());
    }

    public static function fromUuid(string $uuid): static
    {
        return static::fromString($uuid);
    }

    public static function fromNullableUuid(?string $uuid): static
    {
        return static::fromNullableString($uuid);
    }

    public static function fromUuidInterface(UuidInterface $uuid): static
    {
        return static::fromString($uuid->toString());
    }

    public static function forEloquentFactoryValue(): string
    {
        return Str::uuid7()->toString();
    }
}
