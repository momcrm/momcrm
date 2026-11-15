<?php

declare(strict_types=1);

namespace App\Shared\Traits;

/**
 * Value/label pairs for an enum with a label() method, for select inputs.
 *
 * @method static static[] cases()
 * @method string label()
 */
trait EnumOptions
{
    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_values(array_map(
            fn (self $case): array => ['value' => (string) $case->value, 'label' => $case->label()],
            self::cases(),
        ));
    }
}
