<?php

declare(strict_types=1);

namespace App\Ticket;

use App\Choice\ChoiceListEnum;
use App\Project\ProjectVisibility;
use App\Shared\Table;
use App\Ticket\Properties\AssigneeId;
use App\Ticket\Properties\ContactId;
use App\Ticket\Properties\CustomerId;
use App\Ticket\Properties\Description;
use App\Ticket\Properties\DueOn;
use App\Ticket\Properties\EstimateMinutes;
use App\Ticket\Properties\GroupId;
use App\Ticket\Properties\LocationId;
use App\Ticket\Properties\PriorityId;
use App\Ticket\Properties\ProjectId;
use App\Ticket\Properties\StartsOn;
use App\Ticket\Properties\StatusId;
use App\Ticket\Properties\Title;
use App\Ticket\Properties\TypeId;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

/**
 * Rules shared by opening and editing a ticket. The customer must be in the
 * team, and can be left out only when the ticket belongs to a project; the
 * contact and location must belong to that customer; the project must be one
 * of the team's live projects the person may see (or the one the ticket is
 * already in); the assignee must be a member of the team; the group must be
 * one of the team's. The priority and type must be live, unless they are the ones the ticket already
 * has, so a ticket with an archived choice can still be edited.
 */
final class TicketValidationRules
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function for(FormRequest $request, string $teamId, ?Ticket $current = null): array
    {
        $currentProjectId = $current?->getProjectId()->toNullableString();
        $customerId = $request->string(CustomerId::getNameForValidation())->toString();
        $userId = (string) $request->user()?->id;

        return [
            CustomerId::getNameForValidation() => [
                ...CustomerId::getValidationRulesForCreate($request),
                Rule::exists(Table::Customers->value, 'id')->where('team_id', $teamId),
            ],
            ContactId::getNameForValidation() => [
                ...ContactId::getValidationRulesForCreate($request),
                Rule::exists(Table::Contacts->value, 'id')->where('customer_id', $customerId),
            ],
            LocationId::getNameForValidation() => [
                ...LocationId::getValidationRulesForCreate($request),
                Rule::exists(Table::Locations->value, 'id')->where('customer_id', $customerId),
            ],
            AssigneeId::getNameForValidation() => [
                ...AssigneeId::getValidationRulesForCreate($request),
                Rule::exists(Table::TeamUser->value, 'user_id')->where('team_id', $teamId),
            ],
            ProjectId::getNameForValidation() => [
                ...ProjectId::getValidationRulesForCreate($request),
                Rule::exists(Table::Projects->value, 'id')
                    ->where('team_id', $teamId)
                    ->where(fn (Builder $query) => $query
                        ->where(function (Builder $live) use ($userId): void {
                            $live->whereNull('archived_at');
                            ProjectVisibility::constrainProjects($live, $userId);
                        })
                        ->orWhere('id', $currentProjectId)),
            ],
            StartsOn::getNameForValidation() => StartsOn::getValidationRulesForCreate($request),
            DueOn::getNameForValidation() => DueOn::getValidationRulesForCreate($request),
            EstimateMinutes::getNameForValidation() => EstimateMinutes::getValidationRulesForCreate($request),
            Title::getNameForValidation() => Title::getValidationRulesForCreate($request),
            Description::getNameForValidation() => Description::getValidationRulesForCreate($request),
            PriorityId::getNameForValidation() => [
                ...PriorityId::getValidationRulesForCreate($request),
                self::choice($teamId, ChoiceListEnum::TicketPriority, $current?->getPriorityId()->toNullableString()),
            ],
            TypeId::getNameForValidation() => [
                ...TypeId::getValidationRulesForCreate($request),
                self::choice($teamId, ChoiceListEnum::TicketType, $current?->getTypeId()->toNullableString()),
            ],
            GroupId::getNameForValidation() => [
                ...GroupId::getValidationRulesForCreate($request),
                Rule::exists(Table::Groups->value, 'id')->where('team_id', $teamId),
            ],
        ];
    }

    /**
     * One of the team's live choices from the list, or the one the ticket
     * already has.
     */
    private static function choice(string $teamId, ChoiceListEnum $list, ?string $currentId): Exists
    {
        return Rule::exists(Table::Choices->value, 'id')
            ->where('team_id', $teamId)
            ->where('list', $list->value)
            ->where(fn (Builder $query) => $query->whereNull('archived_at')->orWhere('id', $currentId));
    }

    /**
     * The status rules: one of the team's live ticket statuses, or the one the
     * ticket already has.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function status(FormRequest $request, string $teamId, bool $isRequired = false, ?string $currentStatusId = null): array
    {
        return [
            StatusId::getNameForValidation() => [
                ...($isRequired ? ['required', 'uuid'] : StatusId::getValidationRulesForCreate($request)),
                self::choice($teamId, ChoiceListEnum::TicketStatus, $currentStatusId),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            CustomerId::getNameForValidation().'.required_without' => __('Choose the customer this ticket is for, or put it in a project.'),
            CustomerId::getNameForValidation().'.exists' => __('Choose the customer this ticket is for.'),
            ContactId::getNameForValidation().'.exists' => __('Choose a contact who works for this customer.'),
            LocationId::getNameForValidation().'.exists' => __('Choose one of this customer\'s locations.'),
            AssigneeId::getNameForValidation().'.exists' => __('Choose someone in your team.'),
            ProjectId::getNameForValidation().'.exists' => __('Choose one of your team\'s projects.'),
            PriorityId::getNameForValidation().'.exists' => __('Choose one of your team\'s priorities.'),
            TypeId::getNameForValidation().'.exists' => __('Choose one of your team\'s ticket types.'),
            GroupId::getNameForValidation().'.exists' => __('Choose one of your team\'s groups.'),
            StatusId::getNameForValidation().'.exists' => __('Choose one of your team\'s statuses.'),
            DueOn::getNameForValidation().'.after_or_equal' => __('The due date can\'t be before the start date.'),
        ];
    }
}
