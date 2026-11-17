<?php

declare(strict_types=1);

use App\CustomField\CustomFieldEloquentModel;
use App\CustomField\CustomFieldTypeEnum;
use App\CustomObject\CustomObjectEloquentModel;
use App\Record\RecordEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('lets a member rename a record', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create(['onboarded_at' => now()]);
    $vehicles = CustomObjectEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);
    $record = RecordEloquentModel::factory()->of($vehicles)->create(['name' => 'Van', 'number' => 3]);

    actingAs($member)
        ->patch(route('records.update', [$vehicles, $record]), ['name' => 'Delivery van', 'number' => 99])
        ->assertSessionHasNoErrors();

    expect($record->fresh())->name->toBe('Delivery van')->number->toBe(3);
});

it('will not blank the name', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create(['onboarded_at' => now()]);
    $vehicles = CustomObjectEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);
    $record = RecordEloquentModel::factory()->of($vehicles)->create(['name' => 'Van']);

    actingAs($owner)
        ->patch(route('records.update', [$vehicles, $record]), ['name' => ''])
        ->assertSessionHasErrors('name');

    expect($record->fresh()->name)->toBe('Van');
});

it('finds no record to change under another object, or in another team', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create(['onboarded_at' => now()]);
    $vehicles = CustomObjectEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);
    $licence = RecordEloquentModel::factory()->of(CustomObjectEloquentModel::factory()->create(['team_id' => $owner->current_team_id]))->create(['name' => 'Licence']);
    $foreign = RecordEloquentModel::factory()->create(['name' => 'Theirs']);

    actingAs($owner)->patch(route('records.update', [$vehicles, $licence]), ['name' => 'Mine'])->assertNotFound();
    actingAs($owner)->patch(route('records.update', [$foreign->object_id, $foreign]), ['name' => 'Mine'])->assertNotFound();

    expect($licence->fresh()->name)->toBe('Licence')
        ->and(RecordEloquentModel::query()->withoutGlobalScopes()->find($foreign->id)->name)->toBe('Theirs');
});

it("changes one field's value, leaving the others and the name as they are", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create(['onboarded_at' => now()]);
    $vehicles = CustomObjectEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);
    $plate = CustomFieldEloquentModel::factory()->forObject($vehicles)->create();
    $leased = CustomFieldEloquentModel::factory()->forObject($vehicles)->ofType(CustomFieldTypeEnum::Checkbox)->create();
    $record = RecordEloquentModel::factory()->of($vehicles)->create(['name' => 'Van', 'custom_fields' => [$plate->id => 'ABC 123']]);

    actingAs($owner)
        ->patch(route('records.update', [$vehicles, $record]), ['custom_fields' => [$leased->id => true]])
        ->assertSessionHasNoErrors();

    expect($record->fresh())->name->toBe('Van')->custom_fields->toEqual([$plate->id => 'ABC 123', $leased->id => true]);
});
