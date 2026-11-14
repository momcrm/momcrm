<?php

namespace Tests\Feature\Team;

use App\Team\TeamEloquentModel;
use App\TeamUser\Actions\CreateTeamUser;
use App\User\UserEloquentModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SwitchCurrentTeamControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login_when_switching_teams()
    {
        $team = TeamEloquentModel::factory()->create();

        $this->put(route('current-team.update'), ['team_id' => $team->id])
            ->assertRedirect(route('login'));
    }

    public function test_users_can_switch_to_a_team_they_belong_to()
    {
        $user = UserEloquentModel::factory()->withPersonalTeam()->create();
        $otherTeam = TeamEloquentModel::factory()->create();
        CreateTeamUser::fromIds(teamId: $otherTeam->id, userId: $user->id);

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->put(route('current-team.update'), ['team_id' => $otherTeam->id])
            ->assertRedirect(route('dashboard'));

        $this->assertSame($otherTeam->id, $user->refresh()->current_team_id);
    }

    public function test_switching_to_a_team_the_user_does_not_belong_to_returns_404()
    {
        $user = UserEloquentModel::factory()->withPersonalTeam()->create();
        $personalTeamId = $user->current_team_id;
        $foreignTeam = TeamEloquentModel::factory()->create();

        $this->actingAs($user)
            ->put(route('current-team.update'), ['team_id' => $foreignTeam->id])
            ->assertNotFound();

        $this->assertSame($personalTeamId, $user->refresh()->current_team_id);
    }

    public function test_team_id_is_required()
    {
        $user = UserEloquentModel::factory()->withPersonalTeam()->create();

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->put(route('current-team.update'), [])
            ->assertSessionHasErrors(['team_id' => 'The team id field is required.']);
    }
}
