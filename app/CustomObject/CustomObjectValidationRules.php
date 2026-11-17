<?php

declare(strict_types=1);

namespace App\CustomObject;

use App\CustomObject\Properties\Description;
use App\CustomObject\Properties\Hue;
use App\CustomObject\Properties\Icon;
use App\CustomObject\Properties\Name;
use App\CustomObject\Properties\NamePlural;
use App\Shared\Table;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The rules shared by adding and editing an object. A team's objects are
 * told apart by their plural name, which heads the list and the sidebar.
 */
final class CustomObjectValidationRules
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function for(FormRequest $request, string $teamId, ?CustomObject $current = null): array
    {
        $unique = Rule::unique(Table::CustomObjects->value, NamePlural::getDatabaseTableColumnName())->where('team_id', $teamId);
        $sometimes = $current === null ? [] : ['sometimes'];

        return [
            Name::getNameForValidation() => [...$sometimes, ...Name::getValidationRulesForCreate($request)],
            NamePlural::getNameForValidation() => [
                ...$sometimes,
                ...NamePlural::getValidationRulesForCreate($request),
                $current === null ? $unique : $unique->ignore($current->getId()->toString()),
            ],
            Description::getNameForValidation() => [...$sometimes, ...Description::getValidationRulesForCreate($request)],
            Icon::getNameForValidation() => [...$sometimes, ...Icon::getValidationRulesForCreate($request)],
            Hue::getNameForValidation() => [...$sometimes, ...Hue::getValidationRulesForCreate($request)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            NamePlural::getNameForValidation().'.unique' => __('You already have an object with that name, or one in the archive.'),
        ];
    }
}
