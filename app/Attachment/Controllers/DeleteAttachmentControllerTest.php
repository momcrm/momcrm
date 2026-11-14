<?php

declare(strict_types=1);

use App\Attachment\AttachmentEloquentModel;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

it('deletes a file and its stored copy', function (): void {
    Storage::fake('local');
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $attachment = AttachmentEloquentModel::factory()
        ->on(TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id]))
        ->create(['path' => 'attachments/x.pdf']);
    Storage::disk('local')->put('attachments/x.pdf', 'PDF');

    actingAs($user)->delete(route('attachments.destroy', $attachment->id));

    expect(AttachmentEloquentModel::query()->exists())->toBeFalse();
    Storage::disk('local')->assertMissing('attachments/x.pdf');
});

it("returns not found for another team's file", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->delete(route('attachments.destroy', AttachmentEloquentModel::factory()->create()->id))
        ->assertNotFound();
});
