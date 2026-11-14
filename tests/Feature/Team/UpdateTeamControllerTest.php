<?php

namespace Tests\Feature\Team;

use App\Team\TeamEloquentModel;
use App\TeamUser\Actions\CreateTeamUser;
use App\User\UserEloquentModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTeamControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_settings_page_is_displayed()
    {
        $user = UserEloquentModel::factory()->withPersonalTeam()->create();

        $this->actingAs($user)
            ->get(route('team.edit'))
            ->assertOk();
    }

    public function test_owners_can_rename_their_current_team()
    {
        $user = UserEloquentModel::factory()->withPersonalTeam()->create();

        $this->actingAs($user)
            ->patch(route('team.update'), ['name' => 'Acme Sales'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('team.edit'));

        $this->assertSame('Acme Sales', TeamEloquentModel::query()->findOrFail($user->current_team_id)->name);
    }

    public function test_team_name_is_required()
    {
        $user = UserEloquentModel::factory()->withPersonalTeam()->create();

        $this->actingAs($user)
            ->from(route('team.edit'))
            ->patch(route('team.update'), ['name' => ''])
            ->assertSessionHasErrors(['name' => 'The name field is required.'])
            ->assertRedirect(route('team.edit'));
    }

    public function test_members_who_do_not_own_the_team_cannot_rename_it()
    {
        $team = TeamEloquentModel::factory()->create(['name' => 'Original Name']);
        $member = UserEloquentModel::factory()->create();
        CreateTeamUser::fromIds(teamId: $team->id, userId: $member->id);
        $member->forceFill(['current_team_id' => $team->id])->save();

        $this->actingAs($member)
            ->patch(route('team.update'), ['name' => 'Hijacked'])
            ->assertForbidden();

        $this->assertSame('Original Name', $team->refresh()->name);
    }
}
