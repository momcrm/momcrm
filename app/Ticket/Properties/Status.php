<?php

declare(strict_types=1);

namespace App\Ticket\Properties;

use App\Ticket\TicketStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Mom\Data\AbstractEnum;

final class Status extends AbstractEnum
{
    public static function getName(): string
    {
        return 'status';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['required', Rule::enum(TicketStatusEnum::class)];
    }

    public static function forEloquentFactoryValue(): string
    {
        return TicketStatusEnum::New->value;
    }

    public function toNullableEnum(): ?TicketStatusEnum
    {
        $value = $this->toValue();

        if ($value instanceof TicketStatusEnum) {
            return $value;
        }

        return is_string($value) ? TicketStatusEnum::tryFrom($value) : null;
    }

    public function toEnum(): TicketStatusEnum
    {
        return $this->toNullableEnum() ?? TicketStatusEnum::New;
    }
}
