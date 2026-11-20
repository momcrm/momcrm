<?php

declare(strict_types=1);

namespace App\Team\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractString;

/**
 * What the team's invoice numbers start with, e.g. INV- in INV-0042. Empty
 * for plain numbers.
 */
final class InvoicePrefix extends AbstractString
{
    public const string DEFAULT = 'INV-';

    /**
     * A new team starts with the column's default, INV-.
     */
    public static bool $includeInDatabaseCreate = false;

    public static function getName(): string
    {
        return 'invoice_prefix';
    }

    /**
     * @return array<int, string>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['nullable', 'string', 'max:12', 'regex:/^[A-Za-z0-9\-\/_.#]*$/'];
    }

    public static function forEloquentFactoryValue(): string
    {
        return self::DEFAULT;
    }
}
