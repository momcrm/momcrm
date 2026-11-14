<?php

declare(strict_types=1);

use App\Activity\Actions\RecordActivity;
use App\Activity\ActivityTypeEnum;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('redirects guests to the login page', function (): void {
    get(route('activity.index'))->assertRedirect(route('login'));
});

it('lists only the users own account activity and audit rows', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $otherUser = UserEloquentModel::factory()->create();
    RecordActivity::for(type: ActivityTypeEnum::SignedIn, userId: $user->id);
    RecordActivity::for(type: ActivityTypeEnum::SignedIn, userId: $otherUser->id);
    RecordActivity::for(type: ActivityTypeEnum::TeamSwitched, userId: $user->id, teamId: $user->current_team_id);

    actingAs($user)
        ->get(route('activity.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/activity')
            ->has('activities.data', 1)
            ->where('activities.data.0.type', ActivityTypeEnum::SignedIn->value)
            ->where('activities.data.0.label', 'Signed in')
            ->where('activities.data.0.actor', $user->name)
            ->where('auditLogs.data', fn ($rows): bool => collect($rows)->every(
                fn (array $row): bool => $row['subject'] === 'user',
            )),
        );
});
