<?php

declare(strict_types=1);

use App\AccessRule\AccessRuleEloquentModel;
use App\Activity\ActivityEloquentModel;
use App\Activity\ActivityTypeEnum;
use App\Choice\Actions\FindChoice;
use App\Choice\ChoiceEloquentModel;
use App\Choice\ChoiceListEnum;
use App\Contact\ContactEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\CustomField\CustomFieldEloquentModel;
use App\CustomField\CustomFieldRelationEnum;
use App\CustomField\CustomFieldTargetEnum;
use App\CustomField\CustomFieldTypeEnum;
use App\CustomObject\CustomObjectEloquentModel;
use App\Group\GroupEloquentModel;
use App\Project\ProjectEloquentModel;
use App\Record\RecordEloquentModel;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

function teamPriorityId(UserEloquentModel $user, string $name): string
{
    return FindChoice::byNameForTeam($user->current_team_id, ChoiceListEnum::TicketPriority, $name)->getId()->toString();
}

function patchableTicket(UserEloquentModel $user, array $attributes = []): TicketEloquentModel
{
    return TicketEloquentModel::factory()->create([
        'team_id' => $user->current_team_id,
        'customer_id' => CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id])->id,
        ...$attributes,
    ]);
}

it('changes only the fields sent', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $ticket = patchableTicket($user, ['title' => 'Printer']);
    $urgent = teamPriorityId($user, 'Urgent');
    $customerId = $ticket->customer_id;

    actingAs($user)
        ->from(route('tickets.index'))
        ->patch(route('tickets.patch', $ticket->id), ['priority_id' => $urgent, 'due_on' => '2026-12-01', 'estimate_minutes' => 90])
        ->assertSessionHasNoErrors()
        ->assertRedirectToRoute('tickets.index');

    expect($ticket->fresh())
        ->title->toBe('Printer')
        ->priority_id->toBe($urgent)
        ->due_on->toBe('2026-12-01')
        ->estimate_minutes->toBe(90)
        ->customer_id->toBe($customerId);
});

it('closes a ticket from the drawer, stamping the closed time', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $ticket = patchableTicket($user);

    actingAs($user)->patch(route('tickets.patch', $ticket->id), ['status_id' => ticketStatusId($user->current_team_id, 'Closed')]);

    expect($ticket->fresh()->closed_at)->not->toBeNull();
});

it('reopens a ticket from the drawer, clearing the closed time and recording it', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $ticket = patchableTicket($user, ['status_id' => ticketStatusId($user->current_team_id, 'Closed'), 'closed_at' => now()]);

    actingAs($user)
        ->patch(route('tickets.patch', $ticket->id), ['status_id' => ticketStatusId($user->current_team_id, 'New')])
        ->assertSessionHasNoErrors();

    expect($ticket->fresh()->closed_at)->toBeNull()
        ->and(ActivityEloquentModel::query()->where('type', ActivityTypeEnum::TicketReopened->value)->exists())->toBeTrue();
});

it('sets the labels', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $ticket = patchableTicket($user);
    [$keep, $add] = ChoiceEloquentModel::factory()->label()->count(2)->create(['team_id' => $user->current_team_id]);
    $drop = ChoiceEloquentModel::factory()->label()->create(['team_id' => $user->current_team_id]);
    $ticket->labels()->attach([$keep->id, $drop->id]);

    actingAs($user)->patch(route('tickets.patch', $ticket->id), ['label_ids' => [$keep->id, $add->id]]);

    expect($ticket->fresh()->labels->pluck('id')->sort()->values()->all())->toBe(collect([$keep->id, $add->id])->sort()->values()->all());
});

it("rejects another team's label", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $ticket = patchableTicket($user);

    actingAs($user)
        ->patch(route('tickets.patch', $ticket->id), ['label_ids' => [ChoiceEloquentModel::factory()->label()->create()->id]])
        ->assertSessionHasErrors('label_ids.0');
});

it('clears the contact when the customer changes', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $ticket = patchableTicket($user);
    $ticket->update(['contact_id' => ContactEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $ticket->customer_id])->id]);
    $other = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)->patch(route('tickets.patch', $ticket->id), ['customer_id' => $other->id])->assertSessionHasNoErrors();

    expect($ticket->fresh())->customer_id->toBe($other->id)->contact_id->toBeNull();
});

it('will not leave a ticket with neither customer nor project', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $project = ProjectEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $ticket = patchableTicket($user, ['customer_id' => null, 'project_id' => $project->id]);

    actingAs($user)
        ->patch(route('tickets.patch', $ticket->id), ['project_id' => null])
        ->assertSessionHasErrors('customer_id');
});

it('lets a ticket opened by an email be worked on before it has a customer, and be given one', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $ticket = patchableTicket($user, ['customer_id' => null, 'requester_email' => 'sam@acme.test']);

    actingAs($user)
        ->patch(route('tickets.patch', $ticket->id), ['assignee_id' => $user->id])
        ->assertSessionHasNoErrors();

    expect($ticket->refresh())
        ->assignee_id->toBe($user->id)
        ->customer_id->toBeNull()
        ->requester_email->toBe('sam@acme.test');

    actingAs($user)
        ->patch(route('tickets.patch', $ticket->id), ['customer_id' => $customer->id])
        ->assertSessionHasNoErrors();

    expect($ticket->refresh()->customer_id)->toBe($customer->id);
});

it("rejects another team's priority", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $ticket = patchableTicket($user);
    $otherTeamsUrgent = teamPriorityId(UserEloquentModel::factory()->withPersonalTeam()->create(), 'Urgent');

    actingAs($user)
        ->patch(route('tickets.patch', $ticket->id), ['priority_id' => $otherTeamsUrgent])
        ->assertSessionHasErrors(['priority_id' => "Choose one of your team's priorities."]);
});

it("returns not found for another team's ticket", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->patch(route('tickets.patch', TicketEloquentModel::factory()->create()->id), ['title' => 'Mine'])
        ->assertNotFound();
});

it('applies the close rules when an edit closes or reopens the ticket', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();
    AccessRuleEloquentModel::factory()->deny()->create(['team_id' => $owner->current_team_id, 'resources' => ['ticket'], 'actions' => ['close', 'reopen']]);
    $new = ticketStatusId($owner->current_team_id, 'New');
    $closedStatus = ticketStatusId($owner->current_team_id, 'Closed');
    $open = patchableTicket($owner, ['status_id' => $new]);
    $closed = patchableTicket($owner, ['status_id' => $closedStatus]);

    $urgent = teamPriorityId($owner, 'Urgent');

    actingAs($member)->patch(route('tickets.patch', $open->id), ['priority_id' => $urgent])->assertSessionHasNoErrors();
    actingAs($member)->patch(route('tickets.patch', $open->id), ['status_id' => $closedStatus])->assertForbidden();
    actingAs($member)->patch(route('tickets.patch', $closed->id), ['status_id' => $new])->assertForbidden();

    expect($open->fresh())->priority_id->toBe($urgent)->status_id->toBe($new)
        ->and($closed->fresh()->status_id)->toBe($closedStatus);
});

it('keeps an archived status the ticket already has, but does not move tickets into one', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $teamId = $user->current_team_id;
    $ticket = patchableTicket($user, ['status_id' => ticketStatusId($teamId, 'Waiting on supplier')]);
    $other = patchableTicket($user);
    ChoiceEloquentModel::query()->withoutGlobalScopes()->whereKey($ticket->status_id)->update(['archived_at' => now()]);

    actingAs($user)
        ->patch(route('tickets.patch', $ticket->id), ['status_id' => $ticket->status_id, 'title' => 'Renamed'])
        ->assertSessionHasNoErrors();

    actingAs($user)
        ->patch(route('tickets.patch', $other->id), ['status_id' => $ticket->status_id])
        ->assertSessionHasErrors(['status_id' => "Choose one of your team's statuses."]);

    expect($ticket->fresh()->title)->toBe('Renamed');
});

it('keeps an archived label the ticket already has, but does not add one', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $ticket = patchableTicket($user);
    $other = patchableTicket($user);
    $live = ChoiceEloquentModel::factory()->label()->create(['team_id' => $user->current_team_id]);
    $archived = ChoiceEloquentModel::factory()->label()->create(['team_id' => $user->current_team_id, 'archived_at' => now()]);
    $ticket->labels()->attach($archived);

    actingAs($user)
        ->patch(route('tickets.patch', $ticket->id), ['label_ids' => [$archived->id, $live->id]])
        ->assertSessionHasNoErrors();

    actingAs($user)
        ->patch(route('tickets.patch', $other->id), ['label_ids' => [$archived->id]])
        ->assertSessionHasErrors('label_ids.0');

    expect($ticket->labels()->pluck('choices.id')->sort()->values()->all())->toBe(collect([$archived->id, $live->id])->sort()->values()->all());
});

it('sets the type and group, keeping an archived type the ticket has but not choosing one from another list or team', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $teamId = $user->current_team_id;
    $incident = FindChoice::byNameForTeam($teamId, ChoiceListEnum::TicketType, 'Incident')->getId()->toString();
    $group = GroupEloquentModel::factory()->create(['team_id' => $teamId]);
    $ticket = patchableTicket($user);
    $other = patchableTicket($user);

    actingAs($user)
        ->patch(route('tickets.patch', $ticket->id), ['type_id' => $incident, 'group_id' => $group->id])
        ->assertSessionHasNoErrors();

    ChoiceEloquentModel::query()->withoutGlobalScopes()->whereKey($incident)->update(['archived_at' => now()]);

    actingAs($user)
        ->patch(route('tickets.patch', $ticket->id), ['title' => 'Renamed'])
        ->assertSessionHasNoErrors();

    actingAs($user)
        ->patch(route('tickets.patch', $other->id), ['type_id' => $incident, 'group_id' => GroupEloquentModel::factory()->create()->id])
        ->assertSessionHasErrors([
            'type_id' => "Choose one of your team's ticket types.",
            'group_id' => "Choose one of your team's groups.",
        ]);

    expect($ticket->fresh())->type_id->toBe($incident)->group_id->toBe($group->id)->title->toBe('Renamed')
        ->and($other->fresh())->type_id->toBeNull()->group_id->toBeNull();
});

it('sets custom field values, leaving the ones not sent as they are', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $teamId = $user->current_team_id;
    $serial = CustomFieldEloquentModel::factory()->create(['team_id' => $teamId]);
    $seats = CustomFieldEloquentModel::factory()->ofType(CustomFieldTypeEnum::Number)->create(['team_id' => $teamId]);
    $due = CustomFieldEloquentModel::factory()->ofType(CustomFieldTypeEnum::Date)->create(['team_id' => $teamId]);
    $onsite = CustomFieldEloquentModel::factory()->ofType(CustomFieldTypeEnum::Checkbox)->create(['team_id' => $teamId]);
    $tier = CustomFieldEloquentModel::factory()->select(['Gold', 'Silver'])->create(['team_id' => $teamId]);
    $tags = CustomFieldEloquentModel::factory()->select(['Red', 'Blue'], isMultiple: true)->create(['team_id' => $teamId]);
    $ticket = patchableTicket($user, ['custom_fields' => [$serial->id => 'SN-1']]);

    actingAs($user)
        ->patch(route('tickets.patch', $ticket->id), ['custom_fields' => [
            $seats->id => '12.5',
            $due->id => '2026-12-01',
            $onsite->id => true,
            $tier->id => $tier->options[1]['id'],
            $tags->id => [$tags->options[0]['id'], $tags->options[1]['id']],
        ]])
        ->assertSessionHasNoErrors();

    expect($ticket->fresh()->custom_fields)->toEqual([
        $serial->id => 'SN-1',
        $seats->id => 12.5,
        $due->id => '2026-12-01',
        $onsite->id => true,
        $tier->id => $tier->options[1]['id'],
        $tags->id => [$tags->options[0]['id'], $tags->options[1]['id']],
    ]);
});

it('removes a custom field value that is emptied', function (mixed $empty): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $field = CustomFieldEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $ticket = patchableTicket($user, ['custom_fields' => [$field->id => 'SN-1']]);

    actingAs($user)
        ->patch(route('tickets.patch', $ticket->id), ['custom_fields' => [$field->id => $empty]])
        ->assertSessionHasNoErrors();

    expect($ticket->fresh()->custom_fields)->toBeNull();
})->with([
    'null' => [null],
    'blank text' => ['  '],
]);

it('rejects a custom field value of the wrong type, naming the field', function (CustomFieldTypeEnum $type, mixed $value, string $message): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $field = CustomFieldEloquentModel::factory()->ofType($type)->create(['team_id' => $user->current_team_id, 'name' => 'Extra']);
    $ticket = patchableTicket($user);

    actingAs($user)
        ->patch(route('tickets.patch', $ticket->id), ['custom_fields' => [$field->id => $value]])
        ->assertSessionHasErrors(["custom_fields.{$field->id}" => $message]);

    expect($ticket->fresh()->custom_fields)->toBeNull();
})->with([
    'text too long' => [CustomFieldTypeEnum::Text, str_repeat('a', 1001), 'The Extra field must not be greater than 1000 characters.'],
    'number' => [CustomFieldTypeEnum::Number, 'twelve', 'The Extra field must be a number.'],
    'date' => [CustomFieldTypeEnum::Date, '01/12/2026', 'The Extra field must match the format Y-m-d.'],
    'checkbox' => [CustomFieldTypeEnum::Checkbox, 'maybe', 'The Extra field must be true or false.'],
    'select' => [CustomFieldTypeEnum::Select, 'not-an-option', 'The selected Extra is invalid.'],
]);

it('rejects an option a multi-select does not offer', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $field = CustomFieldEloquentModel::factory()->select(['Red'], isMultiple: true)->create(['team_id' => $user->current_team_id, 'name' => 'Tags']);
    $ticket = patchableTicket($user);

    actingAs($user)
        ->patch(route('tickets.patch', $ticket->id), ['custom_fields' => [$field->id => [$field->options[0]['id'], 'nope']]])
        ->assertSessionHasErrors(["custom_fields.{$field->id}.1" => 'The selected Tags is invalid.']);
});

it('keeps an option the select no longer offers when the ticket already has it', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $field = CustomFieldEloquentModel::factory()->select(['Gold'])->create(['team_id' => $user->current_team_id]);
    $ticket = patchableTicket($user, ['custom_fields' => [$field->id => 'removed-option']]);

    actingAs($user)
        ->patch(route('tickets.patch', $ticket->id), ['custom_fields' => [$field->id => 'removed-option']])
        ->assertSessionHasNoErrors();

    expect($ticket->fresh()->custom_fields)->toBe([$field->id => 'removed-option']);
});

it('only takes values for the live ticket fields of the team', function (Closure $field): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $ticket = patchableTicket($user);

    actingAs($user)
        ->patch(route('tickets.patch', $ticket->id), ['custom_fields' => [$field($user->current_team_id)->id => 'SN-1']])
        ->assertSessionHasErrors('custom_fields');

    expect($ticket->fresh()->custom_fields)->toBeNull();
})->with([
    'an archived field' => [fn (string $teamId) => CustomFieldEloquentModel::factory()->archived()->create(['team_id' => $teamId])],
    'a customer field' => [fn (string $teamId) => CustomFieldEloquentModel::factory()->on(CustomFieldTargetEnum::Customer)->create(['team_id' => $teamId])],
    "another team's field" => [fn (string $teamId) => CustomFieldEloquentModel::factory()->create()],
]);

it("links a ticket to one of the team's records, and to no one else's", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $teamId = $user->current_team_id;
    $field = CustomFieldEloquentModel::factory()->linkingTo(CustomFieldRelationEnum::Customer)->create(['team_id' => $teamId, 'name' => 'Reseller']);
    $reseller = CustomerEloquentModel::factory()->create(['team_id' => $teamId]);
    $foreign = CustomerEloquentModel::factory()->create();
    $ticket = patchableTicket($user);

    actingAs($user)
        ->patch(route('tickets.patch', $ticket->id), ['custom_fields' => [$field->id => $foreign->id]])
        ->assertSessionHasErrors(["custom_fields.{$field->id}" => 'The selected Reseller is invalid.']);

    actingAs($user)
        ->patch(route('tickets.patch', $ticket->id), ['custom_fields' => [$field->id => $reseller->id]])
        ->assertSessionHasNoErrors();

    expect($ticket->fresh()->custom_fields)->toBe([$field->id => $reseller->id]);
});

it('keeps a link to a record that no longer exists when the ticket already has it', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $field = CustomFieldEloquentModel::factory()->linkingTo(CustomFieldRelationEnum::Member)->create(['team_id' => $user->current_team_id]);
    $gone = '019a0000-0000-7000-8000-000000000000';
    $ticket = patchableTicket($user, ['custom_fields' => [$field->id => $gone]]);

    actingAs($user)
        ->patch(route('tickets.patch', $ticket->id), ['custom_fields' => [$field->id => $gone]])
        ->assertSessionHasNoErrors();
});

it("links a ticket to a record of the field's custom object, and not of another", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $teamId = $user->current_team_id;
    $vehicles = CustomObjectEloquentModel::factory()->create(['team_id' => $teamId]);
    $field = CustomFieldEloquentModel::factory()->linkingToObject($vehicles)->create(['team_id' => $teamId, 'name' => 'Vehicle']);
    $van = RecordEloquentModel::factory()->of($vehicles)->create();
    $licence = RecordEloquentModel::factory()->of(CustomObjectEloquentModel::factory()->create(['team_id' => $teamId]))->create();
    $ticket = patchableTicket($user);

    actingAs($user)
        ->patch(route('tickets.patch', $ticket->id), ['custom_fields' => [$field->id => $licence->id]])
        ->assertSessionHasErrors(["custom_fields.{$field->id}" => 'The selected Vehicle is invalid.']);

    actingAs($user)
        ->patch(route('tickets.patch', $ticket->id), ['custom_fields' => [$field->id => $van->id]])
        ->assertSessionHasNoErrors();

    expect($ticket->fresh()->custom_fields)->toBe([$field->id => $van->id]);
});
