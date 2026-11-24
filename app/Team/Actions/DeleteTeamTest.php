<?php

declare(strict_types=1);

use App\AuditLog\AuditLogEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\Team\Actions\DeleteTeam;
use App\Team\Actions\FindTeam;
use App\Team\TeamEloquentModel;
use App\TeamUser\Actions\CreateTeamUser;
use App\TeamUser\TeamUserEloquentModel;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Support\Facades\Storage;

it('deletes the team with everything in it, and leaves other teams alone', function (): void {
    Storage::fake('local');
    $team = TeamEloquentModel::factory()->create();
    CustomerEloquentModel::factory()->create(['team_id' => $team->id]);
    TicketEloquentModel::factory()->create(['team_id' => $team->id]);
    Storage::disk('local')->put("attachments/{$team->id}/quote.pdf", 'quote');
    $otherTeam = TeamEloquentModel::factory()->create();
    $otherCustomer = CustomerEloquentModel::factory()->create(['team_id' => $otherTeam->id]);
    Storage::disk('local')->put("attachments/{$otherTeam->id}/invoice.pdf", 'invoice');

    DeleteTeam::fromTeam(FindTeam::byId($team->id));

    expect(TeamEloquentModel::query()->find($team->id))->toBeNull()
        ->and(CustomerEloquentModel::query()->withoutGlobalScopes()->where('team_id', $team->id)->count())->toBe(0)
        ->and(TicketEloquentModel::query()->withoutGlobalScopes()->where('team_id', $team->id)->count())->toBe(0)
        ->and(AuditLogEloquentModel::query()->withoutGlobalScopes()->where('team_id', $team->id)->count())->toBe(0)
        ->and(CustomerEloquentModel::query()->withoutGlobalScopes()->find($otherCustomer->id))->not->toBeNull();

    Storage::disk('local')->assertMissing("attachments/{$team->id}/quote.pdf");
    Storage::disk('local')->assertExists("attachments/{$otherTeam->id}/invoice.pdf");
});

it('gives a member who was in no other team a new personal team', function (): void {
    $team = TeamEloquentModel::factory()->create();
    $owner = UserEloquentModel::query()->findOrFail($team->owner_id);
    $owner->forceFill(['current_team_id' => $team->id, 'name' => 'Dana Reyes'])->save();

    DeleteTeam::fromTeam(FindTeam::byId($team->id));

    $newTeam = TeamEloquentModel::query()->where('owner_id', $owner->id)->sole();

    expect($newTeam)
        ->name->toBe("Dana Reyes's Team")
        ->personal_team->toBeTrue()
        ->and($owner->fresh()?->current_team_id)->toBe($newTeam->id);
});

it('leaves a member who is in another team with just that team', function (): void {
    $member = UserEloquentModel::factory()->withPersonalTeam()->create();
    $personalTeamId = $member->current_team_id;
    $team = TeamEloquentModel::factory()->create();
    CreateTeamUser::fromIds(teamId: $team->id, userId: $member->id);
    $member->forceFill(['current_team_id' => $team->id])->save();

    DeleteTeam::fromTeam(FindTeam::byId($team->id));

    expect(TeamUserEloquentModel::query()->withoutGlobalScopes()->where('user_id', $member->id)->pluck('team_id')->all())->toBe([$personalTeamId])
        ->and($member->fresh()?->current_team_id)->toBeNull();
});
