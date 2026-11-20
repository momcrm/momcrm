<?php

declare(strict_types=1);

namespace App\Team\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractString;

/**
 * What the team's country calls its tax ID, e.g. ABN, VAT or GST No.
 */
final class TaxIdLabel extends AbstractString
{
    public static function getName(): string
    {
        return 'tax_id_label';
    }

    /**
     * @return array<int, string>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['nullable', 'string', 'max:20', 'required_with:tax_id'];
    }
}
