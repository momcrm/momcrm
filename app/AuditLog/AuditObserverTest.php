<?php

declare(strict_types=1);

use App\AuditLog\AuditEventEnum;
use App\AuditLog\AuditLogEloquentModel;
use App\Shared\MorphMapKeyEnum;
use App\Team\TeamEloquentModel;
use App\TeamUser\Actions\CreateTeamUser;
use App\TeamUser\TeamUserEloquentModel;
use App\User\Actions\UpdateUserPassword;
use App\User\User;
use App\User\UserEloquentModel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

/**
 * @return Collection<int, AuditLogEloquentModel>
 */
function auditRowsFor(string $type, string $id): Collection
{
    return AuditLogEloquentModel::query()
        ->where('auditable_type', $type)
        ->where('auditable_id', $id)
        ->orderBy('created_at')
        ->orderBy('id')
        ->get();
}

it('records a created row with the new values and the team', function (): void {
    $team = TeamEloquentModel::factory()->create(['name' => 'Acme']);

    $row = auditRowsFor(MorphMapKeyEnum::Team->value, $team->id)->sole();

    expect($row->event)->toBe(AuditEventEnum::Created->value)
        ->and($row->team_id)->toBe($team->id)
        ->and($row->old_values)->toBeNull()
        ->and($row->new_values)->toMatchArray(['name' => 'Acme', 'owner_id' => $team->owner_id])
        ->and($row->new_values)->not->toHaveKeys(['created_at', 'updated_at']);
});

it('records only the changed attributes on update', function (): void {
    $team = TeamEloquentModel::factory()->create(['name' => 'Acme']);

    $team->update(['name' => 'Acme Sales']);

    $row = auditRowsFor(MorphMapKeyEnum::Team->value, $team->id)->last();

    expect($row->event)->toBe(AuditEventEnum::Updated->value)
        ->and($row->old_values)->toBe(['name' => 'Acme'])
        ->and($row->new_values)->toBe(['name' => 'Acme Sales']);
});

it('never stores hidden attributes such as the password', function (): void {
    $user = UserEloquentModel::factory()->create();

    $created = auditRowsFor(MorphMapKeyEnum::User->value, $user->id)->sole();

    expect($created->team_id)->toBeNull()
        ->and($created->new_values)->toHaveKeys(['name', 'email'])
        ->and($created->new_values)->not->toHaveKeys(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes']);
});

it('writes nothing when only hidden attributes change', function (string $attribute): void {
    $user = UserEloquentModel::factory()->create();

    if ($attribute === 'password') {
        UpdateUserPassword::fromUser(User::fromEloquentModel($user)->setPassword('a-new-password'));
    } else {
        $user->forceFill([$attribute => Str::random(20)])->save();
    }

    expect(auditRowsFor(MorphMapKeyEnum::User->value, $user->id))->toHaveCount(1);
})->with([
    'password' => 'password',
    'remember token' => 'remember_token',
]);

it('records membership changes against the team', function (): void {
    $team = TeamEloquentModel::factory()->create();
    $member = UserEloquentModel::factory()->create();
    $membership = CreateTeamUser::fromIds(teamId: $team->id, userId: $member->id);

    TeamUserEloquentModel::query()->findOrFail($membership->getId()->toString())->delete();

    $rows = auditRowsFor(MorphMapKeyEnum::TeamUser->value, $membership->getId()->toString());

    expect($rows->pluck('event')->all())->toBe([AuditEventEnum::Created->value, AuditEventEnum::Deleted->value])
        ->and($rows->pluck('team_id')->unique()->all())->toBe([$team->id])
        ->and($rows->last()->old_values)->toMatchArray(['user_id' => $member->id, 'role' => 'member']);
});

it('records a deleted team without a team reference', function (): void {
    $team = TeamEloquentModel::factory()->create();

    $team->delete();

    $row = AuditLogEloquentModel::query()
        ->where('auditable_type', MorphMapKeyEnum::Team->value)
        ->where('auditable_id', $team->id)
        ->where('event', AuditEventEnum::Deleted->value)
        ->sole();

    expect($row->team_id)->toBeNull()
        ->and($row->old_values)->toMatchArray(['name' => $team->name]);
});

it('captures the actor, request id and ip address of the request', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $requestId = Str::uuid7()->toString();

    actingAs($user)
        ->withHeader('X-Request-Id', $requestId)
        ->patch(route('team.update'), ['name' => 'Renamed'])
        ->assertSessionHasNoErrors();

    $row = auditRowsFor(MorphMapKeyEnum::Team->value, $user->current_team_id)->last();

    expect($row->user_id)->toBe($user->id)
        ->and($row->request_id)->toBe($requestId)
        ->and($row->ip_address)->toBe('127.0.0.1')
        ->and($row->url)->toBe(route('team.update'));
});
