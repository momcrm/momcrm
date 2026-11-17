<?php

declare(strict_types=1);

use App\AccessRule\AccessRuleEloquentModel;
use App\CustomObject\CustomObjectEloquentModel;
use App\Record\RecordEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('lets a member add a record, numbered within its object, and opens it', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create(['onboarded_at' => now()]);
    $vehicles = CustomObjectEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);
    $licences = CustomObjectEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);
    RecordEloquentModel::factory()->of($vehicles)->create(['number' => 4]);
    RecordEloquentModel::factory()->of($licences)->create(['number' => 9]);

    $response = actingAs($member)->post(route('records.store', $vehicles), ['name' => ' Delivery van ']);

    $record = RecordEloquentModel::query()->withoutGlobalScopes()->where('name', 'Delivery van')->sole();

    $response
        ->assertRedirectToRoute('records.show', [$vehicles->id, $record->id])
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Delivery van added.']);

    expect($record)
        ->object_id->toBe($vehicles->id)
        ->team_id->toBe($owner->current_team_id)
        ->number->toBe(5)
        ->created_by_id->toBe($member->id);
});

it('requires a name', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create(['onboarded_at' => now()]);
    $vehicles = CustomObjectEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);

    actingAs($owner)
        ->post(route('records.store', $vehicles), ['name' => ''])
        ->assertSessionHasErrors(['name' => 'The name field is required.']);
});

it('refuses a member an access rule keeps from adding records to this object, but not to others', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create(['onboarded_at' => now()]);
    $licences = CustomObjectEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);
    $vehicles = CustomObjectEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);
    AccessRuleEloquentModel::factory()->create([
        'team_id' => $owner->current_team_id,
        'name' => 'Only admins add licences',
        'effect' => 'deny',
        'resources' => ['record'],
        'actions' => ['create'],
        'conditions' => [
            ['attribute' => 'record.object', 'operator' => 'equals', 'value' => $licences->id],
            ['attribute' => 'subject.team_role', 'operator' => 'equals', 'value' => 'member'],
        ],
    ]);

    actingAs($member)->post(route('records.store', $licences), ['name' => 'Forklift licence'])->assertForbidden();
    actingAs($member)->post(route('records.store', $vehicles), ['name' => 'Van'])->assertSessionHasNoErrors()->assertRedirect();
});

it("finds no object to add to when it is archived or another team's", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create(['onboarded_at' => now()]);
    $archived = CustomObjectEloquentModel::factory()->archived()->create(['team_id' => $owner->current_team_id]);
    $foreign = CustomObjectEloquentModel::factory()->create();

    actingAs($owner)->post(route('records.store', $archived), ['name' => 'Van'])->assertNotFound();
    actingAs($owner)->post(route('records.store', $foreign), ['name' => 'Van'])->assertNotFound();
});
