<?php

declare(strict_types=1);

use App\Attachment\AttachmentEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\Folder\FolderEloquentModel;
use App\TeamUser\TeamRoleEnum;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it("describes a customer's file and moves it into another folder", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $folder = FolderEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id]);
    $file = AttachmentEloquentModel::factory()->on($customer)->create();

    actingAs($user)
        ->patch(route('attachments.update', $file->id), ['description' => 'Signed copy', 'folder_id' => $folder->id])
        ->assertSessionHasNoErrors();

    expect($file->refresh())->description->toBe('Signed copy')->folder_id->toBe($folder->id);
});

it("rejects another customer's folder, and any folder for a ticket's file", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $elsewhere = FolderEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $customerFile = AttachmentEloquentModel::factory()->on($customer)->create();
    $ticketFile = AttachmentEloquentModel::factory()->on(TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $elsewhere->customer_id]))->create();

    foreach ([$customerFile, $ticketFile] as $file) {
        actingAs($user)
            ->patch(route('attachments.update', $file->id), ['folder_id' => $elsewhere->id])
            ->assertSessionHasErrors(['folder_id' => "Choose one of this customer's folders."]);

        expect($file->refresh()->folder_id)->toBeNull();
    }
});

it("returns not found for another team's file", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->patch(route('attachments.update', AttachmentEloquentModel::factory()->create()->id), ['description' => 'Mine'])
        ->assertNotFound();
});

it('lets members describe and move files under the default rules', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);
    $file = AttachmentEloquentModel::factory()->on($customer)->create();

    actingAs($member)
        ->patch(route('attachments.update', $file->id), ['description' => 'Floor plan'])
        ->assertSessionHasNoErrors();

    expect($file->refresh()->description)->toBe('Floor plan');
});
