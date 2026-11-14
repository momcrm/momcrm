<?php

declare(strict_types=1);

use App\Team\Actions\FindTeam;
use App\TeamUser\Actions\FindTeamUser;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;
use Illuminate\Support\Str;
use Laravel\Fortify\Features;

use function Pest\Laravel\post;

beforeEach(function (): void {
    $this->skipUnlessFortifyHas(Features::registration());
});

it('gives a new user a personal team they own as their current team', function (): void {
    post(route('register.store'), [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = UserEloquentModel::query()->where('email', 'ada@example.com')->sole();
    $team = FindTeam::byId($user->current_team_id);

    expect($team->isNotNull())->toBeTrue()
        ->and(Str::isUuid($user->id))->toBeTrue()
        ->and(Str::isUuid($team->getId()->toString()))->toBeTrue()
        ->and($team->getName()->toString())->toBe("Ada's Team")
        ->and($team->getIsPersonal()->toBoolean())->toBeTrue()
        ->and($team->getOwnerId()->toString())->toBe($user->id)
        ->and(FindTeamUser::byTeamAndUser($team->getId()->toString(), $user->id)->getRole()->toEnum())
        ->toBe(TeamRoleEnum::Owner);
});
