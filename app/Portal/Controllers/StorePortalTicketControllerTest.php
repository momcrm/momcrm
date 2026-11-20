<?php

declare(strict_types=1);

use App\AccessRule\AccessRuleEloquentModel;
use App\Attachment\AttachmentEloquentModel;
use App\Favorite\FavoriteEloquentModel;
use App\Ticket\Notifications\TicketRaisedInPortalNotification;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    Storage::fake('local');
    Notification::fake();
});

it('raises a ticket for the customer with the contact as requester and their files shared', function (): void {
    $contact = portalContact();

    $response = actingAs($contact, 'portal')->post(route('portal.tickets.store'), [
        'title' => 'Printer jam',
        'description' => 'It eats paper.',
        'files' => [UploadedFile::fake()->create('photo.png', 10, 'image/png')],
    ]);

    $ticket = TicketEloquentModel::query()->withoutGlobalScopes()->sole();
    $response->assertRedirectToRoute('portal.tickets.show', $ticket->id);

    expect($ticket)
        ->team_id->toBe($contact->team_id)
        ->customer_id->toBe($contact->customer_id)
        ->contact_id->toBe($contact->id)
        ->requester_name->toBe('Mark Ellis')
        ->requester_email->toBe('mark@coastal.test')
        ->assignee_id->toBeNull();

    expect(AttachmentEloquentModel::query()->withoutGlobalScopes()->sole())
        ->attachable_id->toBe($ticket->id)
        ->uploaded_by_contact_id->toBe($contact->id)
        ->uploaded_by_id->toBeNull()
        ->shared_with_customer->toBeTrue();
});

it("tells staff who starred the customer, or else the team's owners and admins", function (): void {
    $contact = portalContact();
    $owner = UserEloquentModel::query()->where('current_team_id', $contact->team_id)->sole();

    actingAs($contact, 'portal')->post(route('portal.tickets.store'), ['title' => 'Printer jam']);

    Notification::assertSentTo($owner, TicketRaisedInPortalNotification::class);

    $watcher = UserEloquentModel::factory()->inTeam($contact->team_id)->create();
    FavoriteEloquentModel::query()->create(['team_id' => $contact->team_id, 'user_id' => $watcher->id, 'favoritable_type' => 'customer', 'favoritable_id' => $contact->customer_id]);
    Notification::fake();

    actingAs($contact, 'portal')->post(route('portal.tickets.store'), ['title' => 'Another']);

    Notification::assertSentTo($watcher, TicketRaisedInPortalNotification::class);
    Notification::assertNotSentTo($owner, TicketRaisedInPortalNotification::class);
});

it('refuses files when contacts may not add them, and tickets when they may not raise them', function (): void {
    $contact = portalContact();
    AccessRuleEloquentModel::query()->withoutGlobalScopes()->where('team_id', $contact->team_id)->where('audience', 'contact')->delete();
    AccessRuleEloquentModel::factory()->forContacts()->create(['team_id' => $contact->team_id, 'resources' => ['ticket'], 'actions' => ['create']]);

    actingAs($contact, 'portal')
        ->post(route('portal.tickets.store'), ['title' => 'Printer jam', 'files' => [UploadedFile::fake()->create('photo.png', 10, 'image/png')]])
        ->assertSessionHasErrors('files');

    AccessRuleEloquentModel::query()->withoutGlobalScopes()->where('team_id', $contact->team_id)->where('audience', 'contact')->delete();

    actingAs($contact, 'portal')->post(route('portal.tickets.store'), ['title' => 'Printer jam'])->assertForbidden();
    expect(TicketEloquentModel::query()->withoutGlobalScopes()->exists())->toBeFalse();
});

it("rejects another list's choice as the ticket type", function (): void {
    $contact = portalContact();

    actingAs($contact, 'portal')
        ->post(route('portal.tickets.store'), ['title' => 'Printer jam', 'type_id' => ticketStatusId($contact->team_id, 'New')])
        ->assertSessionHasErrors('type_id');
});
