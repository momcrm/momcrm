<?php

namespace Tests\Feature\Team;

use App\Team\TeamEloquentModel;
use App\TeamUser\Actions\CreateTeamUser;
use App\TeamUser\TeamUserEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnsureCurrentTeamTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_missing_current_team_falls_back_to_the_users_first_team()
    {
        $user = UserEloquentModel::factory()->withPersonalTeam()->create();
        $personalTeamId = $user->current_team_id;
        $user->forceFill(['current_team_id' => null])->save();

        $this->actingAs($user)->get(route('dashboard'))->assertOk();

        $this->assertSame($personalTeamId, $user->refresh()->current_team_id);
    }

    public function test_a_current_team_the_user_was_removed_from_falls_back_to_the_users_first_team()
    {
        $user = UserEloquentModel::factory()->withPersonalTeam()->create();
        $personalTeamId = $user->current_team_id;
        $formerTeam = TeamEloquentModel::factory()->create();
        $membership = CreateTeamUser::fromIds(teamId: $formerTeam->id, userId: $user->id);
        $user->forceFill(['current_team_id' => $formerTeam->id])->save();
        TeamUserEloquentModel::query()->whereKey($membership->getId()->toString())->delete();

        $this->actingAs($user)->get(route('dashboard'))->assertOk();

        $this->assertSame($personalTeamId, $user->refresh()->current_team_id);
    }
}
