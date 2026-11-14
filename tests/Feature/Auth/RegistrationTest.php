<?php

namespace Tests\Feature\Auth;

use App\Team\Actions\FindTeam;
use App\TeamUser\Actions\FindTeamUser;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::registration());
    }

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_new_users_can_register()
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_new_users_get_a_personal_team_they_own_as_their_current_team()
    {
        $this->post(route('register.store'), [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = UserEloquentModel::where('email', 'ada@example.com')->sole();
        $team = FindTeam::byId($user->current_team_id);

        $this->assertTrue($team->isNotNull());
        $this->assertTrue(Str::isUuid($user->id));
        $this->assertTrue(Str::isUuid($team->getId()->toString()));
        $this->assertSame("Ada's Team", $team->getName()->toString());
        $this->assertTrue($team->getIsPersonal()->toBoolean());
        $this->assertSame($user->id, $team->getOwnerId()->toString());
        $this->assertSame(
            TeamRoleEnum::Owner,
            FindTeamUser::byTeamAndUser($team->getId()->toString(), $user->id)->getRole()->toEnum(),
        );
    }
}
