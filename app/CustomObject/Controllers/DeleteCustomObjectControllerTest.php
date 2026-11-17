<?php

declare(strict_types=1);

use App\AccessRule\AccessRuleEloquentModel;
use App\CustomField\CustomFieldEloquentModel;
use App\CustomObject\CustomObjectEloquentModel;
use App\Record\RecordEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('deletes an archived object', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $object = CustomObjectEloquentModel::factory()->archived()->create(['team_id' => $owner->current_team_id, 'name_plural' => 'Vehicles']);

    actingAs($owner)
        ->delete(route('custom-objects.destroy', $object))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Vehicles deleted.']);

    expect($object->fresh())->toBeNull();
});

it('finds no object to delete when it is not archived or belongs to another team', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $live = CustomObjectEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);
    $foreign = CustomObjectEloquentModel::factory()->archived()->create();

    actingAs($owner)->delete(route('custom-objects.destroy', $live))->assertNotFound();
    actingAs($owner)->delete(route('custom-objects.destroy', $foreign))->assertNotFound();

    expect($live->fresh())->not->toBeNull()
        ->and(CustomObjectEloquentModel::query()->withoutGlobalScopes()->find($foreign->id))->not->toBeNull();
});

it('forbids members', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();
    $object = CustomObjectEloquentModel::factory()->archived()->create(['team_id' => $owner->current_team_id]);

    actingAs($member)->delete(route('custom-objects.destroy', $object))->assertForbidden();
});

it('takes its records with it, and comes out of the access rules that named it', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $teamId = $owner->current_team_id;
    $kept = CustomObjectEloquentModel::factory()->create(['team_id' => $teamId]);
    $object = CustomObjectEloquentModel::factory()->archived()->create(['team_id' => $teamId]);
    $record = RecordEloquentModel::factory()->of($object)->create();
    $rule = AccessRuleEloquentModel::factory()->create([
        'team_id' => $teamId,
        'effect' => 'deny',
        'resources' => ['record'],
        'conditions' => [['attribute' => 'record.object', 'operator' => 'in', 'value' => [$object->id, $kept->id]]],
    ]);

    actingAs($owner)->delete(route('custom-objects.destroy', $object))->assertSessionHasNoErrors();

    expect(RecordEloquentModel::query()->withoutGlobalScopes()->find($record->id))->toBeNull()
        ->and($rule->fresh()->conditions)->toBe([['attribute' => 'record.object', 'operator' => 'in', 'value' => [$kept->id]]]);
});

it('takes its fields with it', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $object = CustomObjectEloquentModel::factory()->archived()->create(['team_id' => $owner->current_team_id]);
    $field = CustomFieldEloquentModel::factory()->forObject($object)->create();

    actingAs($owner)->delete(route('custom-objects.destroy', $object))->assertSessionHasNoErrors();

    expect(CustomFieldEloquentModel::query()->withoutGlobalScopes()->find($field->id))->toBeNull();
});
