<?php

declare(strict_types=1);

namespace App\Auth\Actions;

use App\Team\Actions\CreatePersonalTeam;
use App\User\Actions\CreateUser;
use App\User\User;
use App\User\UserEloquentModel;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Creates a new person's account once their email is proven: the user, already
 * verified, with an optional password, and their personal team.
 */
final readonly class CreateAccount
{
    public function __construct(
        private string $email,
        private string $name,
        private ?string $password,
    ) {}

    public function __invoke(): UserEloquentModel
    {
        return DB::transaction(function (): UserEloquentModel {
            $user = User::new()
                ->setName($this->name)
                ->setEmail($this->email)
                ->setEmailVerifiedAt(CarbonImmutable::now());

            if ($this->password !== null) {
                $user->setPassword($this->password);
            }

            $user = CreateUser::fromUser($user);
            CreatePersonalTeam::fromUser($user);

            return UserEloquentModel::query()->findOrFail($user->getId()->toString());
        });
    }

    public static function for(string $email, string $name, ?string $password): UserEloquentModel
    {
        return new self($email, $name, $password)->handle();
    }

    public function handle(): UserEloquentModel
    {
        return $this->__invoke();
    }

    public function execute(): UserEloquentModel
    {
        return $this->__invoke();
    }
}
