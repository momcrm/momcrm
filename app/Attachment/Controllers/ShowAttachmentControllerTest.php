<?php

declare(strict_types=1);

use App\Attachment\AttachmentEloquentModel;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

it('downloads a file under its original name', function (): void {
    Storage::fake('local');
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $attachment = AttachmentEloquentModel::factory()
        ->on(TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id]))
        ->create(['name' => 'quote.pdf', 'path' => 'attachments/x.pdf']);
    Storage::disk('local')->put('attachments/x.pdf', 'PDF');

    actingAs($user)
        ->get(route('attachments.show', $attachment->id))
        ->assertOk()
        ->assertDownload('quote.pdf');
});

it("returns not found for another team's file", function (): void {
    Storage::fake('local');
    $attachment = AttachmentEloquentModel::factory()->create(['path' => 'attachments/y.pdf']);
    Storage::disk('local')->put('attachments/y.pdf', 'PDF');

    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->get(route('attachments.show', $attachment->id))
        ->assertNotFound();
});
