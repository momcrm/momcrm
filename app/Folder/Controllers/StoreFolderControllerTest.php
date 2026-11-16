<?php

declare(strict_types=1);

use App\Customer\CustomerEloquentModel;
use App\Folder\FolderEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('adds a folder to a customer, inside another when asked', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $parent = FolderEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id]);

    actingAs($user)
        ->post(route('folders.store', $customer->id), ['name' => 'Contracts', 'parent_id' => $parent->id])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect(FolderEloquentModel::query()->where('name', 'Contracts')->sole())
        ->team_id->toBe($user->current_team_id)
        ->customer_id->toBe($customer->id)
        ->parent_id->toBe($parent->id);
});

it("rejects another customer's folder as the parent", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $elsewhere = FolderEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->post(route('folders.store', $customer->id), ['name' => 'Contracts', 'parent_id' => $elsewhere->id])
        ->assertSessionHasErrors(['parent_id' => "Choose one of this customer's folders."]);
});

it("returns not found for another team's customer", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->post(route('folders.store', CustomerEloquentModel::factory()->create()->id), ['name' => 'Contracts'])
        ->assertNotFound();

    expect(FolderEloquentModel::query()->exists())->toBeFalse();
});
