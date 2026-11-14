<?php

declare(strict_types=1);

namespace App\Milestone\Properties;

use App\Shared\Abstracts\AbstractDateOnly;
use Illuminate\Foundation\Http\FormRequest;

final class DueOn extends AbstractDateOnly
{
    public static function getName(): string
    {
        return 'due_on';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['required', 'date'];
    }
}
