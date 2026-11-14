<?php

declare(strict_types=1);

namespace App\Ticket;

use App\Shared\Table;
use App\Ticket\Properties\AssigneeId;
use App\Ticket\Properties\ContactId;
use App\Ticket\Properties\CustomerId;
use App\Ticket\Properties\Description;
use App\Ticket\Properties\LocationId;
use App\Ticket\Properties\Priority;
use App\Ticket\Properties\Title;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Rules shared by opening and editing a ticket. The customer must be in the
 * team; the contact and location must belong to that customer; the assignee
 * must be a member of the team.
 */
final class TicketValidationRules
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function for(FormRequest $request, string $teamId): array
    {
        $customerId = (string) $request->input(CustomerId::getNameForValidation());

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
            Title::getNameForValidation() => Title::getValidationRulesForCreate($request),
            Description::getNameForValidation() => Description::getValidationRulesForCreate($request),
            Priority::getNameForValidation() => Priority::getValidationRulesForCreate($request),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            CustomerId::getNameForValidation().'.required' => __('Choose the customer this ticket is for.'),
            CustomerId::getNameForValidation().'.exists' => __('Choose the customer this ticket is for.'),
            ContactId::getNameForValidation().'.exists' => __('Choose a contact who works for this customer.'),
            LocationId::getNameForValidation().'.exists' => __('Choose one of this customer\'s locations.'),
            AssigneeId::getNameForValidation().'.exists' => __('Choose someone in your team.'),
        ];
    }
}
