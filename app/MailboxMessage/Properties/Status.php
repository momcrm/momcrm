<?php

declare(strict_types=1);

namespace App\MailboxMessage\Properties;

use App\MailboxMessage\MailboxMessageStatusEnum;
use Mom\Data\AbstractEnum;

final class Status extends AbstractEnum
{
    public static function getName(): string
    {
        return 'status';
    }

    public static function forEloquentFactoryValue(): string
    {
        return MailboxMessageStatusEnum::Processed->value;
    }

    public function toNullableEnum(): ?MailboxMessageStatusEnum
    {
        $value = $this->toValue();

        if ($value instanceof MailboxMessageStatusEnum) {
            return $value;
        }

        return is_string($value) ? MailboxMessageStatusEnum::tryFrom($value) : null;
    }

    /**
     * An unreadable status counts as ignored: kept, and nothing more done with it.
     */
    public function toEnum(): MailboxMessageStatusEnum
    {
        return $this->toNullableEnum() ?? MailboxMessageStatusEnum::Ignored;
    }
}
