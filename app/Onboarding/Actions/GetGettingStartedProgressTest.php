<?php

declare(strict_types=1);

use App\Contact\ContactEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\Onboarding\Actions\GetGettingStartedProgress;
use App\Onboarding\Actions\SeedSampleData;
use App\TeamInvitation\TeamInvitationEloquentModel;
use App\TeamUser\Actions\CreateTeamUser;
use App\TeamUser\TeamRoleEnum;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Support\Facades\Storage;

/**
 * @return array<string, bool>
 */
function stepsDone(UserEloquentModel $user): array
{
    $progress = GetGettingStartedProgress::forUser($user->fresh());

    return collect($progress['steps'] ?? [])->mapWithKeys(fn (array $step): array => [$step['key'] => $step['done']])->all();
}

it('starts an owner with five steps to do', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();

    expect(GetGettingStartedProgress::forUser($user))
        ->completed->toBe(0)
        ->total->toBe(5);
    expect(stepsDone($user))->toBe(['customer' => false, 'contact' => false, 'ticket' => false, 'invite' => false, 'passkey' => false]);
});

it('ticks steps off as the real records appear', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    ContactEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id]);
    TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id]);
    TeamInvitationEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    expect(stepsDone($user))->toBe(['customer' => true, 'contact' => true, 'ticket' => true, 'invite' => true, 'passkey' => false]);
});

it('does not count sample data', function (): void {
    Storage::fake('local');
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    SeedSampleData::forTeam(teamId: $user->current_team_id, userId: $user->id);

    expect(stepsDone($user))->toMatchArray(['customer' => false, 'contact' => false, 'ticket' => false]);
});

it("leaves out the invite step for members who can't invite", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->create();
    CreateTeamUser::fromIds(teamId: $owner->current_team_id, userId: $member->id, role: TeamRoleEnum::Member);
    $member->forceFill(['current_team_id' => $owner->current_team_id])->save();

    expect(array_keys(stepsDone($member)))->toBe(['customer', 'contact', 'ticket', 'passkey']);
});

it('shows nothing once dismissed or before onboarding is finished', function (UserEloquentModel $user): void {
    expect(GetGettingStartedProgress::forUser($user))->toBeNull();
})->with([
    'dismissed' => fn () => UserEloquentModel::factory()->withPersonalTeam()->create(['getting_started_dismissed_at' => now()]),
    'still onboarding' => fn () => UserEloquentModel::factory()->notOnboarded()->withPersonalTeam()->create(),
]);
