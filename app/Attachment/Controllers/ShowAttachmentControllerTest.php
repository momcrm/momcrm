<?php

declare(strict_types=1);

use App\Attachment\AttachmentEloquentModel;
use App\Project\ProjectEloquentModel;
use App\TeamUser\TeamUserEloquentModel;
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
        ->assertDownload('quote.pdf')
        ->assertHeader('X-Content-Type-Options', 'nosniff');
});

it("returns not found for another team's file", function (): void {
    Storage::fake('local');
    $attachment = AttachmentEloquentModel::factory()->create(['path' => 'attachments/y.pdf']);
    Storage::disk('local')->put('attachments/y.pdf', 'PDF');

    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->get(route('attachments.show', $attachment->id))
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

    actingAs($outsider)->get(route('attachments.show', $attachment->id))->assertNotFound();
    actingAs($member)->get(route('attachments.show', $attachment->id))->assertOk();
});
