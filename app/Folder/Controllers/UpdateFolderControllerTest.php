<?php

declare(strict_types=1);

use App\Customer\CustomerEloquentModel;
use App\Folder\FolderEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('renames a folder and moves it under another', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $folder = FolderEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'name' => 'Old']);
    $target = FolderEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id]);

    actingAs($user)
        ->patch(route('folders.update', $folder->id), ['name' => 'New', 'parent_id' => $target->id])
        ->assertSessionHasNoErrors();

    expect($folder->refresh())->name->toBe('New')->parent_id->toBe($target->id);
});

it('moves a folder back to the top level', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $parent = FolderEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $folder = FolderEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $parent->customer_id, 'parent_id' => $parent->id]);

    actingAs($user)
        ->patch(route('folders.update', $folder->id), ['parent_id' => null])
        ->assertSessionHasNoErrors();

    expect($folder->refresh()->parent_id)->toBeNull();
});

it('will not put a folder inside itself or one of its own', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $folder = FolderEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $child = FolderEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $folder->customer_id, 'parent_id' => $folder->id]);
    $grandchild = FolderEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $folder->customer_id, 'parent_id' => $child->id]);

    foreach ([$folder, $grandchild] as $target) {
        actingAs($user)
            ->patch(route('folders.update', $folder->id), ['parent_id' => $target->id])
            ->assertSessionHasErrors(['parent_id' => "A folder can't go inside itself."]);
    }

    expect($folder->refresh()->parent_id)->toBeNull();
});

it("rejects another customer's folder as the parent", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $folder = FolderEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $elsewhere = FolderEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->patch(route('folders.update', $folder->id), ['parent_id' => $elsewhere->id])
        ->assertSessionHasErrors(['parent_id' => "Choose one of this customer's folders."]);
});

it("returns not found for another team's folder", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->patch(route('folders.update', FolderEloquentModel::factory()->create()->id), ['name' => 'Mine'])
        ->assertNotFound();
});
