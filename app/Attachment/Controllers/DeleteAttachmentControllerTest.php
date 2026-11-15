<?php

declare(strict_types=1);

use App\Attachment\AttachmentEloquentModel;
use App\Project\ProjectEloquentModel;
use App\TeamUser\TeamUserEloquentModel;
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

it('returns not found for a file on a ticket in a members-only project the person is not in', function (): void {
    Storage::fake('local');
    $member = UserEloquentModel::factory()->withPersonalTeam()->create();
    $outsider = UserEloquentModel::factory()->create(['current_team_id' => $member->current_team_id]);
    TeamUserEloquentModel::query()->create(['team_id' => $member->current_team_id, 'user_id' => $outsider->id, 'role' => 'member']);
    $project = ProjectEloquentModel::factory()->create(['team_id' => $member->current_team_id, 'visibility' => 'restricted']);
    $project->members()->attach($member->id);
    $attachment = AttachmentEloquentModel::factory()
        ->on(TicketEloquentModel::factory()->create(['team_id' => $member->current_team_id, 'project_id' => $project->id]))
        ->create(['path' => 'attachments/x.pdf']);
    Storage::disk('local')->put('attachments/x.pdf', 'PDF');

    actingAs($outsider)->delete(route('attachments.destroy', $attachment->id))->assertNotFound();
    actingAs($member)->delete(route('attachments.destroy', $attachment->id))->assertRedirect();
});
