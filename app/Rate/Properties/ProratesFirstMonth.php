<?php

declare(strict_types=1);

namespace App\Rate\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractBoolean;

/**
 * Whether a service that starts part-way through a month pays only for the
 * days left in it. Off for things sold whole, such as a domain name.
 */
final class ProratesFirstMonth extends AbstractBoolean
{
    public static function getName(): string
    {
        return 'prorates_first_month';
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
