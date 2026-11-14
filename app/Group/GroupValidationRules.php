<?php

declare(strict_types=1);

namespace App\Group;

use App\Group\Properties\Color;
use App\Group\Properties\Description;
use App\Group\Properties\Icon;
use App\Group\Properties\Name;
use App\Shared\Table;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Rules shared by creating and editing a group: a name unique in the team, and
 * a lead and members who are in the team.
 */
final class GroupValidationRules
{
    public const string LEAD_ID = 'lead_id';

    public const string MEMBER_IDS = 'member_ids';

    /**
     * Sent by the form alongside the member pickers, so an empty selection
     * (which sends no member_ids at all) still means "no members".
     */
    public const string MEMBERS_SENT = 'members_sent';

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function for(FormRequest $request, string $teamId, ?string $ignoreId = null): array
    {
        $teamMember = Rule::exists(Table::TeamUser->value, 'user_id')->where('team_id', $teamId);

        return [
            Name::getNameForValidation() => [
                ...Name::getValidationRulesForCreate($request),
                Rule::unique(Table::Groups->value, 'name')->where('team_id', $teamId)->ignore($ignoreId),
            ],
            Description::getNameForValidation() => Description::getValidationRulesForCreate($request),
            Icon::getNameForValidation() => Icon::getValidationRulesForCreate($request),
            Color::getNameForValidation() => Color::getValidationRulesForCreate($request),
            self::LEAD_ID => ['nullable', 'uuid', $teamMember],
            self::MEMBER_IDS => ['sometimes', 'array'],
            self::MEMBER_IDS.'.*' => ['uuid', $teamMember],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            Name::getNameForValidation().'.unique' => __('There is already a group with that name.'),
            self::LEAD_ID.'.exists' => __('Choose someone in your team.'),
            self::MEMBER_IDS.'.*.exists' => __('Choose people in your team.'),
        ];
    }
}
