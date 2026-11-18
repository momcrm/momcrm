<?php

declare(strict_types=1);

namespace App\Mailbox\Properties;

use App\Mailbox\MailboxStatusEnum;
use Mom\Data\AbstractEnum;

final class Status extends AbstractEnum
{
    public static function getName(): string
    {
        return 'status';
    }

    public static function forEloquentFactoryValue(): string
    {
        return MailboxStatusEnum::Connected->value;
    }

    public function toNullableEnum(): ?MailboxStatusEnum
    {
        $value = $this->toValue();

        if ($value instanceof MailboxStatusEnum) {
            return $value;
        }

        return is_string($value) ? MailboxStatusEnum::tryFrom($value) : null;
    }

    /**
     * An unreadable status counts as needing a reconnect, so a broken mailbox
     * is never read or sent from.
     */
    public function toEnum(): MailboxStatusEnum
    {
        return $this->toNullableEnum() ?? MailboxStatusEnum::NeedsReconnect;
    }
}
