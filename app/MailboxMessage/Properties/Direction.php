<?php

declare(strict_types=1);

namespace App\MailboxMessage\Properties;

use App\MailboxMessage\MailboxMessageDirectionEnum;
use Mom\Data\AbstractEnum;

final class Direction extends AbstractEnum
{
    public static function getName(): string
    {
        return 'direction';
    }

    public static function forEloquentFactoryValue(): string
    {
        return MailboxMessageDirectionEnum::Inbound->value;
    }

    public function toNullableEnum(): ?MailboxMessageDirectionEnum
    {
        $value = $this->toValue();

        if ($value instanceof MailboxMessageDirectionEnum) {
            return $value;
        }

        return is_string($value) ? MailboxMessageDirectionEnum::tryFrom($value) : null;
    }

    public function toEnum(): MailboxMessageDirectionEnum
    {
        return $this->toNullableEnum() ?? MailboxMessageDirectionEnum::Inbound;
    }
}
