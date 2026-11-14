<?php

declare(strict_types=1);

namespace App\Ticket\Properties;

use App\Ticket\TicketPriorityEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Mom\Data\AbstractEnum;

final class Priority extends AbstractEnum
{
    public static function getName(): string
    {
        return 'priority';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['required', Rule::enum(TicketPriorityEnum::class)];
    }

    public static function forEloquentFactoryValue(): string
    {
        return TicketPriorityEnum::Normal->value;
    }

    public function toNullableEnum(): ?TicketPriorityEnum
    {
        $value = $this->toValue();

        if ($value instanceof TicketPriorityEnum) {
            return $value;
        }

        return is_string($value) ? TicketPriorityEnum::tryFrom($value) : null;
    }

    public function toEnum(): TicketPriorityEnum
    {
        return $this->toNullableEnum() ?? TicketPriorityEnum::Normal;
    }
}
