<?php

declare(strict_types=1);

use App\Team\Scopes\CurrentTeamScope;
use App\Team\TeamEloquentModel;
use App\TeamUser\Actions\CreateTeamUser;
use App\User\UserEloquentModel;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use function Pest\Laravel\actingAs;

/**
 * A minimal team-owned model, standing in for future CRM models.
 *
 * @property string $id
 * @property string $team_id
 * @property string $name
 */
#[ScopedBy(CurrentTeamScope::class)]
final class TeamScopedRecord extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $guarded = [];
}

beforeEach(function (): void {
    Schema::create('team_scoped_records', function (Blueprint $table): void {
        $table->uuid('id')->primary();
        $table->foreignUuid('team_id')->constrained()->cascadeOnDelete();
        $table->string('name');
    });
});

it('only returns records from the users current team', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    TeamScopedRecord::query()->create(['team_id' => $user->current_team_id, 'name' => 'Mine']);
    TeamScopedRecord::query()->create(['team_id' => TeamEloquentModel::factory()->create()->id, 'name' => 'Theirs']);

    actingAs($user);

    expect(TeamScopedRecord::query()->pluck('name')->all())->toBe(['Mine']);
});

it('cannot find another teams record by id', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $foreignRecord = TeamScopedRecord::query()->create([
        'team_id' => TeamEloquentModel::factory()->create()->id,
        'name' => 'Theirs',
    ]);

    actingAs($user);

    expect(TeamScopedRecord::query()->find($foreignRecord->id))->toBeNull();
});

it('shows the new teams records after switching teams', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $secondTeam = TeamEloquentModel::factory()->create();
    CreateTeamUser::fromIds(teamId: $secondTeam->id, userId: $user->id);
    TeamScopedRecord::query()->create(['team_id' => $user->current_team_id, 'name' => 'Personal']);
    TeamScopedRecord::query()->create(['team_id' => $secondTeam->id, 'name' => 'Second']);

    actingAs($user)->put(route('current-team.update'), ['team_id' => $secondTeam->id]);
    actingAs($user->refresh());

    expect(TeamScopedRecord::query()->pluck('name')->all())->toBe(['Second']);
});

it('applies no scope without an authenticated user', function (): void {
    TeamScopedRecord::query()->create(['team_id' => TeamEloquentModel::factory()->create()->id, 'name' => 'First']);
    TeamScopedRecord::query()->create(['team_id' => TeamEloquentModel::factory()->create()->id, 'name' => 'Second']);

    expect(TeamScopedRecord::query()->count())->toBe(2);
});
