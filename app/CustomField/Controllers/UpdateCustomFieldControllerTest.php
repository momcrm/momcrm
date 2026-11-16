<?php

declare(strict_types=1);

use App\CustomField\CustomFieldEloquentModel;
use App\CustomField\CustomFieldTargetEnum;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('renames a field and changes its hint, leaving the rest alone', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $field = CustomFieldEloquentModel::factory()->select(['Low', 'High'])->create(['team_id' => $owner->current_team_id]);
    $options = $field->options;

    actingAs($owner)
        ->patch(route('custom-fields.update', $field), ['name' => 'Impact', 'description' => 'How bad it is'])
        ->assertSessionHasNoErrors();

    expect($field->fresh())
        ->name->toBe('Impact')
        ->description->toBe('How bad it is')
        ->options->toBe($options);
});

it("keeps the ids of a select's existing options when they are renamed, reordered or joined by new ones", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $field = CustomFieldEloquentModel::factory()->select(['Low', 'High'])->create(['team_id' => $owner->current_team_id]);
    [$low, $high] = $field->options;

    actingAs($owner)
        ->patch(route('custom-fields.update', $field), ['options' => [
            ['id' => $high['id'], 'name' => 'Severe', 'hue' => 0],
            ['id' => '019a0000-0000-7000-8000-000000000000', 'name' => 'Medium', 'hue' => null],
            ['id' => $low['id'], 'name' => 'Low', 'hue' => null],
        ]])
        ->assertSessionHasNoErrors();

    $saved = $field->fresh()->options;

    expect($saved)->toHaveCount(3)
        ->and($saved[0])->toBe(['id' => $high['id'], 'name' => 'Severe', 'hue' => 0])
        ->and($saved[1]['name'])->toBe('Medium')
        ->and($saved[1]['id'])->toBeUuid()->not->toBe('019a0000-0000-7000-8000-000000000000')
        ->and($saved[2])->toBe(['id' => $low['id'], 'name' => 'Low', 'hue' => null]);
});

it('rejects invalid changes', function (array $input, string $field, string $message): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    CustomFieldEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'name' => 'Region']);
    $text = CustomFieldEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);

    actingAs($owner)
        ->patch(route('custom-fields.update', $text), $input)
        ->assertSessionHasErrors([$field => $message]);
})->with([
    'taken name' => [['name' => 'Region'], 'name', 'A field with that name already exists here, or in the archive.'],
    'options on a text field' => [['options' => [['name' => 'Gold']]], 'options', 'Only select fields have options.'],
]);

it('refuses to empty a select of its options', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $field = CustomFieldEloquentModel::factory()->select()->create(['team_id' => $owner->current_team_id]);

    actingAs($owner)
        ->patch(route('custom-fields.update', $field), ['options' => []])
        ->assertSessionHasErrors(['options' => 'Add at least one option.']);
});

it('ignores a new record type or type', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $field = CustomFieldEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);

    actingAs($owner)
        ->patch(route('custom-fields.update', $field), ['name' => 'Region', 'target' => 'customer', 'type' => 'number'])
        ->assertSessionHasNoErrors();

    expect($field->fresh())->target->toBe(CustomFieldTargetEnum::Ticket->value)->type->toBe('text');
});

it("hides another team's field", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $field = CustomFieldEloquentModel::factory()->create();

    actingAs($owner)->patch(route('custom-fields.update', $field), ['name' => 'Region'])->assertNotFound();
});

it('forbids members', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();
    $field = CustomFieldEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);

    actingAs($member)->patch(route('custom-fields.update', $field), ['name' => 'Region'])->assertForbidden();
});
