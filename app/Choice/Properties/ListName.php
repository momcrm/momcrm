<?php

declare(strict_types=1);

namespace App\Choice\Properties;

use App\Choice\ChoiceListEnum;
use Mom\Data\AbstractEnum;

/**
 * Which list the choice belongs to, such as ticket statuses.
 */
final class ListName extends AbstractEnum
{
    public static function getName(): string
    {
        return 'list';
    }

    public static function forEloquentFactoryValue(): string
    {
        return ChoiceListEnum::TicketStatus->value;
    }

    public function toNullableEnum(): ?ChoiceListEnum
    {
        $value = $this->toValue();

        if ($value instanceof ChoiceListEnum) {
            return $value;
        }

        return is_string($value) ? ChoiceListEnum::tryFrom($value) : null;
    }

    public function toEnum(): ChoiceListEnum
    {
        return $this->toNullableEnum() ?? ChoiceListEnum::TicketStatus;
    }
}
