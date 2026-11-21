<?php

declare(strict_types=1);

use App\Attachment\AttachmentEloquentModel;
use App\Comment\CommentEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('shows the conversation with the customer and shared files, never internal notes', function (): void {
    $contact = portalContact();
    $ticket = portalTicket($contact, ['description' => 'It eats paper.']);
    CommentEloquentModel::factory()->public()->create(['ticket_id' => $ticket->id, 'body' => 'Try turning it off and on.']);
    CommentEloquentModel::factory()->create(['ticket_id' => $ticket->id, 'body' => 'This customer again.']);
    $shared = AttachmentEloquentModel::factory()->create(['team_id' => $contact->team_id, 'attachable_type' => 'ticket', 'attachable_id' => $ticket->id, 'shared_with_customer' => true]);
    AttachmentEloquentModel::factory()->create(['team_id' => $contact->team_id, 'attachable_type' => 'ticket', 'attachable_id' => $ticket->id]);

    actingAs($contact, 'portal')
        ->get(route('tickets.show', $ticket->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('portal/tickets/show')
            ->where('ticket.description', 'It eats paper.')
            ->has('conversation', 1)
            ->where('conversation.0.body', 'Try turning it off and on.')
            ->where('conversation.0.is_customer', false)
            ->has('files', 1)
            ->where('files.0.id', $shared->id)
            ->where('can', ['reply' => true, 'attach' => true, 'close' => true, 'reopen' => false]));
});

it("doesn't show another customer's ticket", function (): void {
    $contact = portalContact();
    $other = portalTicket(portalContact());

    actingAs($contact, 'portal')->get(route('tickets.show', $other->id))->assertNotFound();
});
