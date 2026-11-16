<?php

declare(strict_types=1);

use App\Attachment\AttachmentEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\Folder\FolderEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it("lists the customer's folders and the files in the open folder", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $folder = FolderEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'name' => 'Contracts']);
    FolderEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Someone else']);
    AttachmentEloquentModel::factory()->on($customer)->create(['name' => 'top.pdf']);
    AttachmentEloquentModel::factory()->on($customer)->create(['name' => 'msa.pdf', 'folder_id' => $folder->id]);

    actingAs($user)
        ->get(route('customers.files', $customer->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customers/workspace/files')
            ->has('folders', 1)
            ->where('folders.0.name', 'Contracts')
            ->where('folderId', null)
            ->has('files', 1)
            ->where('files.0.name', 'top.pdf')
            ->where('workspace.counts.files', 2),
        );

    actingAs($user)
        ->get(route('customers.files', ['customer' => $customer->id, 'folder' => $folder->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('folderId', $folder->id)
            ->has('files', 1)
            ->where('files.0.name', 'msa.pdf'),
        );
});

it('opens the top level for a folder that is not the customer\'s', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $elsewhere = FolderEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->get(route('customers.files', ['customer' => $customer->id, 'folder' => $elsewhere->id]))
        ->assertInertia(fn (Assert $page) => $page->where('folderId', null));
});

it("returns not found for another team's customer", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->get(route('customers.files', CustomerEloquentModel::factory()->create()->id))
        ->assertNotFound();
});
