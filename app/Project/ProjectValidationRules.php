<?php

declare(strict_types=1);

namespace App\Project;

use App\Project\Properties\Color;
use App\Project\Properties\CustomerId;
use App\Project\Properties\Description;
use App\Project\Properties\DueOn;
use App\Project\Properties\GroupId;
use App\Project\Properties\Icon;
use App\Project\Properties\LeadId;
use App\Project\Properties\Name;
use App\Project\Properties\StartsOn;
use App\Project\Properties\Status;
use App\Project\Properties\Visibility;
use App\Shared\Table;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Rules shared by creating and editing a project. The customer and group must
 * be the team's; the lead and members must be in the team.
 */
final class ProjectValidationRules
{
    public const string MEMBER_IDS = 'member_ids';

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function for(FormRequest $request, string $teamId): array
    {
        $teamMember = Rule::exists(Table::TeamUser->value, 'user_id')->where('team_id', $teamId);

        return [
            Name::getNameForValidation() => Name::getValidationRulesForCreate($request),
            Description::getNameForValidation() => Description::getValidationRulesForCreate($request),
            Icon::getNameForValidation() => Icon::getValidationRulesForCreate($request),
            Color::getNameForValidation() => Color::getValidationRulesForCreate($request),
            Status::getNameForValidation() => Status::getValidationRulesForCreate($request),
            Visibility::getNameForValidation() => Visibility::getValidationRulesForCreate($request),
            CustomerId::getNameForValidation() => [
                ...CustomerId::getValidationRulesForCreate($request),
                Rule::exists(Table::Customers->value, 'id')->where('team_id', $teamId),
            ],
            GroupId::getNameForValidation() => [
                ...GroupId::getValidationRulesForCreate($request),
                Rule::exists(Table::Groups->value, 'id')->where('team_id', $teamId),
            ],
            LeadId::getNameForValidation() => [...LeadId::getValidationRulesForCreate($request), $teamMember],
            StartsOn::getNameForValidation() => StartsOn::getValidationRulesForCreate($request),
            DueOn::getNameForValidation() => DueOn::getValidationRulesForCreate($request),
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
            CustomerId::getNameForValidation().'.exists' => __('Choose one of your customers.'),
            GroupId::getNameForValidation().'.exists' => __('Choose one of your groups.'),
            LeadId::getNameForValidation().'.exists' => __('Choose someone in your team.'),
            self::MEMBER_IDS.'.*.exists' => __('Choose people in your team.'),
            DueOn::getNameForValidation().'.after_or_equal' => __('The due date can\'t be before the start date.'),
        ];
    }
}
