<?php

declare(strict_types=1);

namespace App\Contact;

use App\Choice\ChoiceListEnum;
use App\Contact\Properties\ContactRoleId;
use App\Contact\Properties\PortalAccess;
use App\Shared\Table;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Rules shared by adding and editing a contact for its portal fields: the
 * role must be one of the team's contact roles, live or the one it has.
 */
final class ContactValidationRules
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function portal(FormRequest $request, string $teamId, ?string $currentRoleId = null): array
    {
        return [
            ContactRoleId::getNameForValidation() => [
                ...ContactRoleId::getValidationRulesForCreate($request),
                Rule::exists(Table::Choices->value, 'id')
                    ->where('team_id', $teamId)
                    ->where('list', ChoiceListEnum::ContactRole->value)
                    ->where(fn (Builder $query) => $query->whereNull('archived_at')->orWhere('id', $currentRoleId)),
            ],
            PortalAccess::getNameForValidation() => PortalAccess::getValidationRulesForCreate($request),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            ContactRoleId::getNameForValidation().'.exists' => __("Choose one of your team's contact roles."),
        ];
    }
}
