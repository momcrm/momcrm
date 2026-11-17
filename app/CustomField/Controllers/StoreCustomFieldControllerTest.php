<?php

declare(strict_types=1);

use App\CustomField\Actions\FindCustomField;
use App\CustomField\CustomFieldEloquentModel;
use App\CustomField\CustomFieldTargetEnum;
use App\CustomObject\CustomObjectEloquentModel;
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

it('adds a record link that points at customers', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($owner)
        ->post(route('custom-fields.store'), ['target' => 'ticket', 'type' => 'relation', 'name' => 'Reseller', 'relation' => 'customer'])
        ->assertSessionHasNoErrors();

    expect(FindCustomField::listForTeam($owner->current_team_id, CustomFieldTargetEnum::Ticket)->first()?->toSummary())
        ->toMatchArray(['type' => 'relation', 'relation' => 'customer', 'options' => []]);
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
    'record link without a kind' => [['target' => 'ticket', 'type' => 'relation', 'name' => 'Reseller'], 'relation', 'Choose what the field links to.'],
    'unknown kind of record' => [['target' => 'ticket', 'type' => 'relation', 'name' => 'Reseller', 'relation' => 'invoice'], 'relation', 'The selected relation is invalid.'],
    'a kind on a text field' => [['target' => 'ticket', 'type' => 'text', 'name' => 'Reseller', 'relation' => 'customer'], 'relation', 'Only record links say what they link to.'],
    'option without a name' => [['target' => 'ticket', 'type' => 'select', 'name' => 'Tier', 'options' => [['name' => '']]], 'options.0.name', 'Give every option a name.'],
]);

it('forbids members', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();

    actingAs($member)
        ->post(route('custom-fields.store'), ['target' => 'ticket', 'type' => 'text', 'name' => 'Region'])
        ->assertForbidden();
});

it('adds a field to a custom object, where another object may have one of the same name', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $teamId = $owner->current_team_id;
    $vehicles = CustomObjectEloquentModel::factory()->create(['team_id' => $teamId]);
    $licences = CustomObjectEloquentModel::factory()->create(['team_id' => $teamId]);
    CustomFieldEloquentModel::factory()->forObject($licences)->create(['name' => 'Expires', 'position' => 4]);

    actingAs($owner)
        ->post(route('custom-fields.store'), ['target' => 'record', 'object_id' => $vehicles->id, 'type' => 'date', 'name' => 'Expires'])
        ->assertSessionHasNoErrors();

    expect(FindCustomField::listForTeam($teamId, CustomFieldTargetEnum::Record, objectId: $vehicles->id)->first()?->toSummary())
        ->toMatchArray(['target' => 'record', 'object_id' => $vehicles->id, 'type' => 'date', 'name' => 'Expires', 'position' => 0]);

    actingAs($owner)
        ->post(route('custom-fields.store'), ['target' => 'record', 'object_id' => $vehicles->id, 'type' => 'text', 'name' => 'Expires'])
        ->assertSessionHasErrors('name');
});

it('needs one of the team\'s objects for a field on a custom object, and none otherwise', function (array $input, string $message): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $foreign = CustomObjectEloquentModel::factory()->create();
    $input = array_map(fn (mixed $value): mixed => $value === '{foreign}' ? $foreign->id : $value, $input);

    actingAs($owner)
        ->post(route('custom-fields.store'), ['type' => 'text', 'name' => 'Plate', ...$input])
        ->assertSessionHasErrors(['object_id' => $message]);
})->with([
    'no object' => [['target' => 'record'], 'Choose the object the field is for.'],
    "another team's object" => [['target' => 'record', 'object_id' => '{foreign}'], 'Choose one of your objects.'],
    'an object on a ticket field' => [['target' => 'ticket', 'object_id' => '{foreign}'], 'Only fields on custom objects name an object.'],
]);

it("adds a link to a custom object's records, which needs one of the team's live objects", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $teamId = $owner->current_team_id;
    $vehicles = CustomObjectEloquentModel::factory()->create(['team_id' => $teamId]);
    $archived = CustomObjectEloquentModel::factory()->archived()->create(['team_id' => $teamId]);
    $link = ['target' => 'ticket', 'type' => 'relation', 'relation' => 'record', 'name' => 'Vehicle'];

    actingAs($owner)->post(route('custom-fields.store'), $link)
        ->assertSessionHasErrors(['relation_object_id' => 'Choose the object the field links to.']);
    actingAs($owner)->post(route('custom-fields.store'), [...$link, 'relation_object_id' => $archived->id])
        ->assertSessionHasErrors(['relation_object_id' => 'Choose one of your objects to link to.']);
    actingAs($owner)->post(route('custom-fields.store'), ['target' => 'ticket', 'type' => 'relation', 'relation' => 'customer', 'name' => 'Reseller', 'relation_object_id' => $vehicles->id])
        ->assertSessionHasErrors(['relation_object_id' => 'Only links to a custom object name one.']);

    actingAs($owner)->post(route('custom-fields.store'), [...$link, 'relation_object_id' => $vehicles->id])->assertSessionHasNoErrors();

    expect(FindCustomField::listForTeam($teamId, CustomFieldTargetEnum::Ticket)->first()?->toSummary())
        ->toMatchArray(['type' => 'relation', 'relation' => 'record', 'relation_object_id' => $vehicles->id]);
});
