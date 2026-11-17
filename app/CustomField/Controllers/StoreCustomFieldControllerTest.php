<?php

declare(strict_types=1);

use App\CustomField\Actions\FindCustomField;
use App\CustomField\CustomFieldEloquentModel;
use App\CustomField\CustomFieldTargetEnum;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('adds a text field to the bottom of a record type', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    CustomFieldEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'position' => 3]);

    actingAs($owner)
        ->post(route('custom-fields.store'), ['target' => 'ticket', 'type' => 'text', 'name' => ' Serial number ', 'description' => 'From the sticker'])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Serial number added.']);

    expect(FindCustomField::listForTeam($owner->current_team_id, CustomFieldTargetEnum::Ticket)->last()?->toSummary())
        ->toMatchArray([
            'target' => 'ticket',
            'type' => 'text',
            'name' => 'Serial number',
            'description' => 'From the sticker',
            'options' => [],
            'position' => 4,
            'is_archived' => false,
        ]);
});

it('adds a select with its options in order, each with an id', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($owner)
        ->post(route('custom-fields.store'), [
            'target' => 'customer',
            'type' => 'select',
            'name' => 'Tier',
            'options' => [['name' => 'Gold', 'hue' => 90], ['name' => 'Silver', 'hue' => null]],
        ])
        ->assertSessionHasNoErrors();

    $options = FindCustomField::listForTeam($owner->current_team_id, CustomFieldTargetEnum::Customer)->first()->getOptions()->toOptions();

    expect($options)->toHaveCount(2)
        ->and($options[0])->toMatchArray(['name' => 'Gold', 'hue' => 90])
        ->and($options[1])->toMatchArray(['name' => 'Silver', 'hue' => null])
        ->and($options[0]['id'])->toBeUuid()->not->toBe($options[1]['id']);
});

it('allows the same name on another record type', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    CustomFieldEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'name' => 'Region']);

    actingAs($owner)
        ->post(route('custom-fields.store'), ['target' => 'customer', 'type' => 'text', 'name' => 'Region'])
        ->assertSessionHasNoErrors();
});

it('rejects invalid fields', function (array $input, string $field, string $message): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    CustomFieldEloquentModel::factory()->archived()->create(['team_id' => $owner->current_team_id, 'name' => 'Region']);

    actingAs($owner)
        ->post(route('custom-fields.store'), $input)
        ->assertSessionHasErrors([$field => $message]);
})->with([
    'unknown record type' => [['target' => 'invoice', 'type' => 'text', 'name' => 'Region 2'], 'target', 'The selected target is invalid.'],
    'unknown type' => [['target' => 'ticket', 'type' => 'colour', 'name' => 'Region 2'], 'type', 'The selected type is invalid.'],
    'taken name, even archived' => [['target' => 'ticket', 'type' => 'text', 'name' => 'Region'], 'name', 'A field with that name already exists here, or in the archive.'],
    'select without options' => [['target' => 'ticket', 'type' => 'select', 'name' => 'Tier'], 'options', 'Add at least one option.'],
    'options on a text field' => [['target' => 'ticket', 'type' => 'text', 'name' => 'Tier', 'options' => [['name' => 'Gold']]], 'options', 'Only select fields have options.'],
    'two options with one name' => [['target' => 'ticket', 'type' => 'multi_select', 'name' => 'Tier', 'options' => [['name' => 'Gold'], ['name' => 'gold']]], 'options.0.name', 'Two options have the same name.'],
    'option without a name' => [['target' => 'ticket', 'type' => 'select', 'name' => 'Tier', 'options' => [['name' => '']]], 'options.0.name', 'Give every option a name.'],
]);

it('forbids members', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();

    actingAs($member)
        ->post(route('custom-fields.store'), ['target' => 'ticket', 'type' => 'text', 'name' => 'Region'])
        ->assertForbidden();
});
