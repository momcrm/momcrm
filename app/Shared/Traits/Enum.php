<?php

declare(strict_types=1);

namespace App\Shared\Traits;

use BackedEnum;
use Illuminate\Support\Collection;

/**
 * @method static static[] cases()
 */
trait Enum
{
    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return collect(self::cases())
            ->map(fn (BackedEnum $enum): string => (string) $enum->value)
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, static>
     */
    public static function collection(): Collection
    {
        return collect(self::cases());
    }
}
