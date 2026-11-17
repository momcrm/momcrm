<?php

declare(strict_types=1);

use App\CustomObject\CustomObjectEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it("sends old links to the object's fields page", function (array $query, Closure $object): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $vehicles = CustomObjectEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);
    $query = array_map(fn (string $value): string => $value === '{vehicles}' ? $vehicles->id : $value, $query);

    actingAs($owner)
        ->get(route('custom-fields.edit', $query))
        ->assertRedirectToRoute('custom-objects.fields', $object($vehicles));
})->with([
    'no tab' => [[], fn () => 'ticket'],
    'a built-in tab' => [['target' => 'customer'], fn () => 'customer'],
    'an unknown tab' => [['target' => 'nope'], fn () => 'ticket'],
    'a custom object tab' => [['object' => '{vehicles}'], fn (CustomObjectEloquentModel $vehicles) => $vehicles->id],
]);
