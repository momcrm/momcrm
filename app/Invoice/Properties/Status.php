<?php

declare(strict_types=1);

namespace App\Invoice\Properties;

use App\Invoice\InvoiceStatusEnum;
use Mom\Data\AbstractEnum;

final class Status extends AbstractEnum
{
    public static function getName(): string
    {
        return 'status';
    }

    public static function forEloquentFactoryValue(): string
    {
        return InvoiceStatusEnum::Draft->value;
    }

    public function toNullableEnum(): ?InvoiceStatusEnum
    {
        $value = $this->toValue();

        if ($value instanceof InvoiceStatusEnum) {
            return $value;
        }

        return is_string($value) ? InvoiceStatusEnum::tryFrom($value) : null;
    }

    public function toEnum(): InvoiceStatusEnum
    {
        return $this->toNullableEnum() ?? InvoiceStatusEnum::Draft;
    }
}
