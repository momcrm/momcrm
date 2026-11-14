<?php

declare(strict_types=1);

use App\Activity\ActivityEloquentModel;
use App\Activity\ActivityTypeEnum;
use App\Attachment\AttachmentEloquentModel;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

it('stores files on a ticket in the team folder', function (): void {
    Storage::fake('local');
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $ticket = TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->post(route('tickets.attachments.store', $ticket->id), ['files' => [
            UploadedFile::fake()->create('quote.pdf', 120, 'application/pdf'),
            UploadedFile::fake()->image('photo.png'),
        ]])
        ->assertSessionHasNoErrors();

    $attachments = AttachmentEloquentModel::query()->orderBy('name')->get();
    expect($attachments->pluck('name')->all())->toBe(['photo.png', 'quote.pdf'])
        ->and($attachments->first())
        ->attachable_type->toBe('ticket')
        ->attachable_id->toBe($ticket->id)
        ->uploaded_by_id->toBe($user->id)
        ->and($attachments->first()->path)->toStartWith("attachments/{$user->current_team_id}/")
        ->and(ActivityEloquentModel::query()->where('type', ActivityTypeEnum::AttachmentAdded->value)->count())->toBe(2);
    Storage::disk('local')->assertExists($attachments->pluck('path')->all());
});

it('rejects files of other types', function (): void {
    Storage::fake('local');
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $ticket = TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->post(route('tickets.attachments.store', $ticket->id), ['files' => [UploadedFile::fake()->create('run.exe', 10, 'application/x-msdownload')]])
        ->assertSessionHasErrors(['files.0' => 'Upload images, PDFs, Office documents, text, CSV or zip files.']);
});

it("returns not found for another team's ticket", function (): void {
    Storage::fake('local');

    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->post(route('tickets.attachments.store', TicketEloquentModel::factory()->create()->id), ['files' => [UploadedFile::fake()->image('a.png')]])
        ->assertNotFound();
});
