<?php

declare(strict_types=1);

use App\CustomObject\Actions\FindCustomObject;
use App\CustomObject\CustomObjectEloquentModel;
use App\Shared\HuePalette;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it("adds an object to the bottom of the team's list", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    CustomObjectEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'position' => 2]);

    actingAs($owner)
        ->post(route('custom-objects.store'), ['name' => 'Vehicle', 'name_plural' => 'Vehicles', 'description' => 'The fleet', 'icon' => 'truck', 'hue' => 120])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Vehicles added.']);

    expect(FindCustomObject::listForTeam($owner->current_team_id)->last()?->toSummary())
        ->toMatchArray([
            'name' => 'Vehicle',
            'name_plural' => 'Vehicles',
            'description' => 'The fleet',
            'icon' => 'truck',
            'hue' => 120,
            'position' => 3,
            'is_archived' => false,
        ]);
});

it('gives an object a colour from its name when none is picked', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($owner)
        ->post(route('custom-objects.store'), ['name' => 'Vehicle', 'name_plural' => 'Vehicles'])
        ->assertSessionHasNoErrors();

    expect(FindCustomObject::listForTeam($owner->current_team_id)->first()->getHue()->toNullableInteger())
        ->toBe(HuePalette::forName('custom_object', 'Vehicles'));
});

it('rejects invalid objects', function (array $input, string $field, string $message): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    CustomObjectEloquentModel::factory()->archived()->create(['team_id' => $owner->current_team_id, 'name_plural' => 'Vehicles']);

    actingAs($owner)
        ->post(route('custom-objects.store'), $input)
        ->assertSessionHasErrors([$field => $message]);
})->with([
    'no name' => [['name_plural' => 'Licences'], 'name', 'The name field is required.'],
    'no plural name' => [['name' => 'Licence'], 'name_plural', 'The name plural field is required.'],
    'taken plural name, even archived' => [['name' => 'Vehicle', 'name_plural' => 'Vehicles'], 'name_plural', 'You already have an object with that name, or one in the archive.'],
    'hue off the wheel' => [['name' => 'Licence', 'name_plural' => 'Licences', 'hue' => 400], 'hue', 'The hue field must be between 0 and 359.'],
]);

it("allows a name another team's object has", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    CustomObjectEloquentModel::factory()->create(['name_plural' => 'Vehicles']);

    actingAs($owner)
        ->post(route('custom-objects.store'), ['name' => 'Vehicle', 'name_plural' => 'Vehicles'])
        ->assertSessionHasNoErrors();
});

it('forbids members', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();

    actingAs($member)
        ->post(route('custom-objects.store'), ['name' => 'Vehicle', 'name_plural' => 'Vehicles'])
        ->assertForbidden();
});
