<?php

declare(strict_types=1);

namespace App\User\Properties;

use App\User\UserEloquentModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Mom\Data\AbstractString;

final class Email extends AbstractString
{
    public static function getName(): string
    {
        return 'email';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return [
            'required',
            'string',
            'email',
            'max:255',
            Rule::unique(
                table: UserEloquentModel::class,
                column: self::getDatabaseTableColumnName(),
            ),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForUpdate(FormRequest $request): array
    {
        return [
            'required',
            'string',
            'email',
            'max:255',
            Rule::unique(
                table: UserEloquentModel::class,
                column: self::getDatabaseTableColumnName(),
            )->ignore($request->user()?->getAuthIdentifier()),
        ];
    }
}
