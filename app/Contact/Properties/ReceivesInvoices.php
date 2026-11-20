<?php

declare(strict_types=1);

namespace App\Contact\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractBoolean;

/**
 * Whether invoices are emailed to this contact by default.
 */
final class ReceivesInvoices extends AbstractBoolean
{
    public static function getName(): string
    {
        return 'receives_invoices';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['boolean'];
    }

    public function default(): bool
    {
        return false;
    }
}
