<?php

declare(strict_types=1);

namespace App\Mailbox\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractBoolean;

/**
 * Whether mail that belongs to no ticket opens a new one.
 */
final class AutoCreateTickets extends AbstractBoolean
{
    public static function getName(): string
    {
        return 'auto_create_tickets';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['required', 'boolean'];
    }

    public function default(): bool
    {
        return true;
    }
}
