<?php

declare(strict_types=1);

namespace App\AccessRule;

use App\Choice\Actions\FindChoice;
use App\Choice\ChoiceListEnum;
use App\Contact\Actions\FindContact;
use App\Customer\Actions\FindCustomer;
use App\Customer\CustomerStatusEnum;
use App\CustomObject\Actions\FindCustomObject;
use App\CustomObject\CustomObject;
use App\Group\Actions\FindGroup;
use App\Invoice\InvoiceStatusEnum;
use App\Project\Actions\FindProject;
use App\Project\ProjectStatusEnum;
use App\Project\ProjectVisibilityEnum;
use App\TeamUser\Actions\FindTeamMembers;
use App\TeamUser\TeamRoleEnum;
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
    public static function forTeam(string $teamId, string $userId, AccessAudienceEnum $audience = AccessAudienceEnum::Staff): array
    {
        $resources = AccessResourceEnum::forAudience($audience);
        $actions = collect($resources)->flatMap(fn (AccessResourceEnum $resource): array => $resource->actions($audience))->unique()->all();

        return [
            'resources' => array_map(fn (AccessResourceEnum $resource): array => [
                'value' => $resource->value,
                'label' => $resource->label(),
                'actions' => array_values(array_map(fn (AccessActionEnum $action): string => $action->value, $resource->actions($audience))),
            ], $resources),
            'actions' => array_values(array_filter(
                AccessActionEnum::options(),
                fn (array $option): bool => in_array(AccessActionEnum::from($option['value']), $actions, true),
            )),
            'effects' => AccessEffectEnum::options(),
            'operators' => AccessOperatorEnum::options(),
            'attributes' => array_map(fn (AccessAttributeEnum $attribute): array => [
                'value' => $attribute->value,
                'label' => $attribute->label(),
                'resource' => $attribute->resource()?->value,
                'type' => $attribute->type()->value,
                'operators' => array_map(fn (AccessOperatorEnum $operator): string => $operator->value, $attribute->type()->operators()),
                'options' => self::valuesFor($attribute, $teamId, $userId),
            ], self::attributesFor($audience)),
        ];
    }

    /**
     * The attributes rules for the audience can use: what is known about
     * that kind of person, and the records they can be given access to.
     *
     * @return list<AccessAttributeEnum>
     */
    public static function attributesFor(AccessAudienceEnum $audience): array
    {
        $resources = AccessResourceEnum::forAudience($audience);

        return array_values(array_filter(
            AccessAttributeEnum::cases(),
            fn (AccessAttributeEnum $attribute): bool => $attribute->isFor($audience)
                && ($attribute->resource() === null || in_array($attribute->resource(), $resources, true)),
        ));
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
            AccessAttributeEnum::ProjectGroup,
            AccessAttributeEnum::TicketGroup => self::idNameOptions(FindGroup::optionsForTeam($teamId)),
            AccessAttributeEnum::ProjectStatus => ProjectStatusEnum::options(),
            AccessAttributeEnum::ProjectVisibility => ProjectVisibilityEnum::options(),
            AccessAttributeEnum::TicketStatus => FindChoice::listForTeam($teamId, ChoiceListEnum::TicketStatus, withArchived: true)->toOptions(),
            AccessAttributeEnum::TicketPriority => FindChoice::listForTeam($teamId, ChoiceListEnum::TicketPriority, withArchived: true)->toOptions(),
            AccessAttributeEnum::TicketType => FindChoice::listForTeam($teamId, ChoiceListEnum::TicketType, withArchived: true)->toOptions(),
            AccessAttributeEnum::TicketProject => self::idNameOptions(FindProject::optionsForTeam($teamId, $userId)),
            AccessAttributeEnum::AssetLabel,
            AccessAttributeEnum::ProjectLabel,
            AccessAttributeEnum::TicketLabel => FindChoice::listForTeam($teamId, ChoiceListEnum::Label, withArchived: true)->toOptions(),
            AccessAttributeEnum::CustomerStatus, AccessAttributeEnum::ContactCustomerStatus => CustomerStatusEnum::options(),
            AccessAttributeEnum::ContactCustomer => self::idNameOptions(FindCustomer::optionsForTeam($teamId)),
            AccessAttributeEnum::ContactCustomerType => FindChoice::listForTeam($teamId, ChoiceListEnum::CustomerType, withArchived: true)->toOptions(),
            AccessAttributeEnum::ContactIs => self::idNameOptions(FindContact::optionsForTeam($teamId)),
            AccessAttributeEnum::ContactRole => FindChoice::listForTeam($teamId, ChoiceListEnum::ContactRole, withArchived: true)->toOptions(),
            AccessAttributeEnum::InvoiceStatus => InvoiceStatusEnum::options(),
            AccessAttributeEnum::RecordObject => array_values(FindCustomObject::listForTeam($teamId, withArchived: true)
                ->map(fn (CustomObject $object): array => ['value' => $object->getId()->toString(), 'label' => $object->getNamePlural()->toString()])
                ->all()),
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
