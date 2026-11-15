<?php

declare(strict_types=1);

namespace App\AccessRule;

use App\Choice\Actions\FindChoice;
use App\Choice\ChoiceListEnum;
use App\Customer\CustomerStatusEnum;
use App\Group\Actions\FindGroup;
use App\Invoice\InvoiceStatusEnum;
use App\Label\Actions\FindLabel;
use App\Project\Actions\FindProject;
use App\Project\ProjectStatusEnum;
use App\Project\ProjectVisibilityEnum;
use App\TeamUser\Actions\FindTeamMembers;
use App\TeamUser\TeamRoleEnum;
use App\Ticket\TicketStatusEnum;
use Illuminate\Support\Collection;

/**
 * Everything the rule editor needs: the resources with their actions, and
 * each attribute with its operators and the values it can be compared with.
 */
final class AccessRuleFormOptions
{
    /**
     * @return array{resources: array<int, array{value: string, label: string, actions: array<int, string>}>, actions: array<int, array{value: string, label: string}>, effects: array<int, array{value: string, label: string}>, operators: array<int, array{value: string, label: string}>, attributes: array<int, array{value: string, label: string, resource: ?string, type: string, operators: array<int, string>, options: array<int, array{value: string, label: string}>}>}
     */
    public static function forTeam(string $teamId, string $userId): array
    {
        return [
            'resources' => array_values(array_map(fn (AccessResourceEnum $resource): array => [
                'value' => $resource->value,
                'label' => $resource->label(),
                'actions' => array_map(fn (AccessActionEnum $action): string => $action->value, $resource->actions()),
            ], AccessResourceEnum::cases())),
            'actions' => AccessActionEnum::options(),
            'effects' => AccessEffectEnum::options(),
            'operators' => AccessOperatorEnum::options(),
            'attributes' => array_values(array_map(fn (AccessAttributeEnum $attribute): array => [
                'value' => $attribute->value,
                'label' => $attribute->label(),
                'resource' => $attribute->resource()?->value,
                'type' => $attribute->type()->value,
                'operators' => array_map(fn (AccessOperatorEnum $operator): string => $operator->value, $attribute->type()->operators()),
                'options' => self::valuesFor($attribute, $teamId, $userId),
            ], AccessAttributeEnum::cases())),
        ];
    }

    /**
     * The values a condition on the attribute may compare with; none for
     * yes/no attributes.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function valuesFor(AccessAttributeEnum $attribute, string $teamId, string $userId): array
    {
        return match ($attribute) {
            AccessAttributeEnum::SubjectTeamRole => array_values(array_map(
                fn (TeamRoleEnum $role): array => ['value' => $role->value, 'label' => ucfirst($role->value)],
                TeamRoleEnum::cases(),
            )),
            AccessAttributeEnum::SubjectUser => self::idNameOptions(FindTeamMembers::optionsForTeam($teamId)),
            AccessAttributeEnum::SubjectGroup,
            AccessAttributeEnum::SubjectLeadsGroup,
            AccessAttributeEnum::ProjectGroup => self::idNameOptions(FindGroup::optionsForTeam($teamId)),
            AccessAttributeEnum::ProjectStatus => ProjectStatusEnum::options(),
            AccessAttributeEnum::ProjectVisibility => ProjectVisibilityEnum::options(),
            AccessAttributeEnum::TicketStatus => TicketStatusEnum::options(),
            AccessAttributeEnum::TicketPriority => FindChoice::listForTeam($teamId, ChoiceListEnum::TicketPriority)->toOptions(),
            AccessAttributeEnum::TicketProject => self::idNameOptions(FindProject::optionsForTeam($teamId, $userId)),
            AccessAttributeEnum::TicketLabel => self::idNameOptions(FindLabel::optionsForTeam($teamId)),
            AccessAttributeEnum::CustomerStatus => CustomerStatusEnum::options(),
            AccessAttributeEnum::InvoiceStatus => InvoiceStatusEnum::options(),
            default => [],
        };
    }

    /**
     * @param  Collection<int, covariant array{id: string, name: string}>  $items
     * @return array<int, array{value: string, label: string}>
     */
    private static function idNameOptions(Collection $items): array
    {
        return $items->map(fn (array $item): array => ['value' => $item['id'], 'label' => $item['name']])->values()->all();
    }
}
