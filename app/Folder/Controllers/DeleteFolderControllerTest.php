<?php

declare(strict_types=1);

use App\Attachment\AttachmentEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\Folder\FolderEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('deletes a folder and the folders inside it, moving their files to the top level', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $folder = FolderEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id]);
    $child = FolderEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'parent_id' => $folder->id]);
    $sibling = FolderEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id]);
    $inFolder = AttachmentEloquentModel::factory()->on($customer)->create(['folder_id' => $folder->id]);
    $inChild = AttachmentEloquentModel::factory()->on($customer)->create(['folder_id' => $child->id]);
    $inSibling = AttachmentEloquentModel::factory()->on($customer)->create(['folder_id' => $sibling->id]);

    actingAs($user)
        ->delete(route('folders.destroy', $folder->id))
        ->assertRedirectToRoute('customers.files', $customer->id);

    expect(FolderEloquentModel::query()->pluck('id')->all())->toBe([$sibling->id])
        ->and($inFolder->refresh()->folder_id)->toBeNull()
        ->and($inChild->refresh()->folder_id)->toBeNull()
        ->and($inSibling->refresh()->folder_id)->toBe($sibling->id);
});

it("returns not found for another team's folder", function (): void {
    $folder = FolderEloquentModel::factory()->create();

    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->delete(route('folders.destroy', $folder->id))
        ->assertNotFound();

    expect($folder->fresh())->not->toBeNull();
});
