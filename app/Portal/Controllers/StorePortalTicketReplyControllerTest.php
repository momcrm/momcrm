<?php

declare(strict_types=1);

use App\Comment\CommentEloquentModel;
use App\Comment\Notifications\TicketCommentedNotification;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;

it("adds the contact's reply to the conversation and tells the assignee", function (): void {
    Notification::fake();
    $contact = portalContact();
    $assignee = UserEloquentModel::factory()->inTeam($contact->team_id)->create();
    $ticket = portalTicket($contact, ['assignee_id' => $assignee->id]);

    actingAs($contact, 'portal')
        ->post(route('portal.tickets.replies.store', $ticket->id), ['body' => 'Still broken.'])
        ->assertRedirect();

    expect(CommentEloquentModel::query()->withoutGlobalScopes()->sole())
        ->contact_id->toBe($contact->id)
        ->user_id->toBeNull()
        ->visibility->toBe('public')
        ->sender_name->toBe('Mark Ellis')
        ->sender_email->toBe('mark@coastal.test');

    Notification::assertSentTo($assignee, TicketCommentedNotification::class, fn (TicketCommentedNotification $notification): bool => $notification->toArray($assignee)['message'] === 'replied in the portal');
});

it('reopens a closed ticket it replies to', function (): void {
    $contact = portalContact();
    $ticket = TicketEloquentModel::factory()->closed()->create(['team_id' => $contact->team_id, 'customer_id' => $contact->customer_id]);

    actingAs($contact, 'portal')->post(route('portal.tickets.replies.store', $ticket->id), ['body' => 'It broke again.']);

    expect($ticket->fresh()->status_id)->toBe(ticketStatusId($contact->team_id, 'New'));
});

it("can't reply to another customer's ticket", function (): void {
    $contact = portalContact();
    $other = portalTicket(portalContact());

    actingAs($contact, 'portal')->post(route('portal.tickets.replies.store', $other->id), ['body' => 'Hi'])->assertNotFound();
    expect(CommentEloquentModel::query()->withoutGlobalScopes()->exists())->toBeFalse();
});
