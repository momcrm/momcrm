<?php

declare(strict_types=1);

use App\Activity\ActivityEloquentModel;
use App\Activity\ActivityTypeEnum;
use App\Attachment\Actions\CreateAttachment;
use App\Attachment\AttachmentEloquentModel;
use App\Ticket\TicketEloquentModel;
use Illuminate\Support\Facades\Storage;

it('stores a file handed over as bytes on the private disk and attaches it, naming who sent it', function (): void {
    Storage::fake('local');
    $ticket = TicketEloquentModel::factory()->create(['number' => 12, 'title' => 'Printer offline']);

    $attachment = CreateAttachment::fromContents('Error screen.PNG', 'png-bytes', 'image/png', $ticket, 'Sam Lee');

    $model = AttachmentEloquentModel::query()->withoutGlobalScopes()->sole();

    expect($model)
        ->id->toBe($attachment->getId()->toString())
        ->team_id->toBe($ticket->team_id)
        ->attachable_type->toBe('ticket')
        ->attachable_id->toBe($ticket->id)
        ->uploaded_by_id->toBeNull()
        ->name->toBe('Error screen.PNG')
        ->size->toBe(9)
        ->mime_type->toBe('image/png')
        ->path->toBe("attachments/{$ticket->team_id}/{$model->id}.png")
        ->and(Storage::disk('local')->get($model->path))->toBe('png-bytes')
        ->and(ActivityEloquentModel::query()->where('type', ActivityTypeEnum::AttachmentAdded->value)->sole()->details)
        ->toEqual(['file' => 'Error screen.PNG', 'number' => 12, 'title' => 'Printer offline', 'actor_name' => 'Sam Lee']);
});

it('tidies the name and type a sender gave a file', function (string $name, ?string $mimeType, string $storedName, string $extension, string $storedType): void {
    Storage::fake('local');
    $ticket = TicketEloquentModel::factory()->create();

    CreateAttachment::fromContents($name, 'bytes', $mimeType, $ticket);

    $model = AttachmentEloquentModel::query()->withoutGlobalScopes()->sole();

    expect($model)
        ->name->toBe($storedName)
        ->mime_type->toBe($storedType)
        ->and($model->path)->toBe("attachments/{$ticket->team_id}/{$model->id}.{$extension}");
})->with([
    'a path that climbs out of the folder' => ['../../../etc/passwd', 'text/plain', 'passwd', 'bin', 'text/plain'],
    'a Windows path' => ['C:\\Users\\sam\\report.PDF', 'application/pdf', 'report.PDF', 'pdf', 'application/pdf'],
    'no name and no type' => ['  ', null, 'attachment', 'bin', 'application/octet-stream'],
    'an extension that is not one' => ['notes.tar gz!', '', 'notes.tar gz!', 'bin', 'application/octet-stream'],
    'a name longer than fits' => [str_repeat('a', 300).'.txt', 'text/plain', str_repeat('a', 255), 'bin', 'text/plain'],
]);
