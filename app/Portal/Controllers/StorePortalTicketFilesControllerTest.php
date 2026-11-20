<?php

declare(strict_types=1);

use App\AccessRule\AccessRuleEloquentModel;
use App\Attachment\AttachmentEloquentModel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

beforeEach(fn () => Storage::fake('local'));

it('adds files to the ticket, shared with the customer', function (): void {
    $contact = portalContact();
    $ticket = portalTicket($contact);

    actingAs($contact, 'portal')
        ->post(route('portal.tickets.files.store', $ticket->id), ['files' => [UploadedFile::fake()->create('log.txt', 1, 'text/plain')]])
        ->assertRedirect();

    expect(AttachmentEloquentModel::query()->withoutGlobalScopes()->sole())
        ->attachable_id->toBe($ticket->id)
        ->shared_with_customer->toBeTrue();
});

it('is forbidden when contacts may not add files', function (): void {
    $contact = portalContact();
    $ticket = portalTicket($contact);
    AccessRuleEloquentModel::query()->withoutGlobalScopes()->where('team_id', $contact->team_id)->where('audience', 'contact')->delete();
    AccessRuleEloquentModel::factory()->forContacts()->create(['team_id' => $contact->team_id, 'resources' => ['ticket'], 'actions' => ['view']]);

    actingAs($contact, 'portal')
        ->post(route('portal.tickets.files.store', $ticket->id), ['files' => [UploadedFile::fake()->create('log.txt', 1, 'text/plain')]])
        ->assertForbidden();
});
