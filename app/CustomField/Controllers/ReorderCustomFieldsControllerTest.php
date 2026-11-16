<?php

declare(strict_types=1);

use App\CustomField\Actions\FindCustomField;
use App\CustomField\CustomFieldEloquentModel;
use App\CustomField\CustomFieldTargetEnum;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it("puts a record type's fields in a new order", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $first = CustomFieldEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'position' => 0]);
    $second = CustomFieldEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'position' => 1]);

    actingAs($owner)
        ->put(route('custom-fields.reorder'), ['target' => 'ticket', 'ids' => [$second->id, $first->id]])
        ->assertSessionHasNoErrors();

    expect(FindCustomField::listForTeam($owner->current_team_id, CustomFieldTargetEnum::Ticket)->ids())->toBe([$second->id, $first->id]);
});

it("rejects another team's or another record type's fields", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customerField = CustomFieldEloquentModel::factory()->on(CustomFieldTargetEnum::Customer)->create(['team_id' => $owner->current_team_id]);
    $foreign = CustomFieldEloquentModel::factory()->create();

    actingAs($owner)
        ->put(route('custom-fields.reorder'), ['target' => 'ticket', 'ids' => [$customerField->id, $foreign->id]])
        ->assertSessionHasErrors(['ids.0', 'ids.1']);
});

it('forbids members', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();
    $field = CustomFieldEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);

    actingAs($member)
        ->put(route('custom-fields.reorder'), ['target' => 'ticket', 'ids' => [$field->id]])
        ->assertForbidden();
});
