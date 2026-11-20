<?php

declare(strict_types=1);

use App\Attachment\AttachmentEloquentModel;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

beforeEach(fn () => Storage::fake('local'));

it("downloads a file shared on the customer's ticket, but not one kept from them", function (): void {
    $contact = portalContact();
    $ticket = portalTicket($contact);
    Storage::disk('local')->put('attachments/shared.txt', 'hello');
    Storage::disk('local')->put('attachments/hidden.txt', 'secret');
    $shared = AttachmentEloquentModel::factory()->create(['team_id' => $contact->team_id, 'attachable_type' => 'ticket', 'attachable_id' => $ticket->id, 'path' => 'attachments/shared.txt', 'name' => 'shared.txt', 'shared_with_customer' => true]);
    $hidden = AttachmentEloquentModel::factory()->create(['team_id' => $contact->team_id, 'attachable_type' => 'ticket', 'attachable_id' => $ticket->id, 'path' => 'attachments/hidden.txt']);

    actingAs($contact, 'portal')->get(route('portal.files.show', $shared->id))->assertDownload('shared.txt');
    actingAs($contact, 'portal')->get(route('portal.files.show', $hidden->id))->assertNotFound();
});

it("doesn't download another customer's shared file", function (): void {
    $contact = portalContact();
    $other = portalContact();
    Storage::disk('local')->put('attachments/theirs.txt', 'hello');
    $theirs = AttachmentEloquentModel::factory()->create(['team_id' => $other->team_id, 'attachable_type' => 'ticket', 'attachable_id' => portalTicket($other)->id, 'path' => 'attachments/theirs.txt', 'shared_with_customer' => true]);

    actingAs($contact, 'portal')->get(route('portal.files.show', $theirs->id))->assertNotFound();
});
