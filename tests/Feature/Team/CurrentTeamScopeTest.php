<?php

namespace Tests\Feature\Team;

use App\Team\Scopes\CurrentTeamScope;
use App\Team\TeamEloquentModel;
use App\TeamUser\Actions\CreateTeamUser;
use App\User\UserEloquentModel;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CurrentTeamScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('team_scoped_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name');
        });
    }

    public function test_queries_only_return_records_from_the_users_current_team()
    {
        $user = UserEloquentModel::factory()->withPersonalTeam()->create();
        $otherTeam = TeamEloquentModel::factory()->create();
        TeamScopedRecord::create(['team_id' => $user->current_team_id, 'name' => 'Mine']);
        TeamScopedRecord::create(['team_id' => $otherTeam->id, 'name' => 'Theirs']);

        $this->actingAs($user);

        $this->assertSame(['Mine'], TeamScopedRecord::pluck('name')->all());
    }

    public function test_records_from_another_team_cannot_be_found_by_id()
    {
        $user = UserEloquentModel::factory()->withPersonalTeam()->create();
        $foreignRecord = TeamScopedRecord::create(['team_id' => TeamEloquentModel::factory()->create()->id, 'name' => 'Theirs']);

        $this->actingAs($user);

        $this->assertNull(TeamScopedRecord::find($foreignRecord->id));
    }

    public function test_switching_teams_changes_which_records_are_visible()
    {
        $user = UserEloquentModel::factory()->withPersonalTeam()->create();
        $secondTeam = TeamEloquentModel::factory()->create();
        CreateTeamUser::fromIds(teamId: $secondTeam->id, userId: $user->id);
        TeamScopedRecord::create(['team_id' => $user->current_team_id, 'name' => 'Personal']);
        TeamScopedRecord::create(['team_id' => $secondTeam->id, 'name' => 'Second']);

        $this->actingAs($user)
            ->put(route('current-team.update'), ['team_id' => $secondTeam->id]);

        $this->actingAs($user->refresh());

        $this->assertSame(['Second'], TeamScopedRecord::pluck('name')->all());
    }

    public function test_no_scope_is_applied_without_an_authenticated_user()
    {
        TeamScopedRecord::create(['team_id' => TeamEloquentModel::factory()->create()->id, 'name' => 'First']);
        TeamScopedRecord::create(['team_id' => TeamEloquentModel::factory()->create()->id, 'name' => 'Second']);

        $this->assertSame(2, TeamScopedRecord::count());
    }
}

/**
 * @property int $id
 * @property int $team_id
 * @property string $name
 */
#[ScopedBy(CurrentTeamScope::class)]
class TeamScopedRecord extends Model
{
    public $timestamps = false;

    protected $fillable = ['team_id', 'name'];
}
