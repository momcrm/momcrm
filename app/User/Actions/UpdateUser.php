<?php

declare(strict_types=1);

namespace App\User\Actions;

use App\User\Properties\Id;
use App\User\User;
use App\User\UserEloquentModel;

final readonly class UpdateUser
{
    public function __construct(
        private User $user,
    ) {}

    public function __invoke(): User
    {
        UserEloquentModel::query()
            ->where(Id::getDatabaseTableColumnName(), $this->user->getId()->toString())
            ->first()
            ?->update($this->user->forDatabaseUpdate());

        return $this->user;
    }

    public static function fromUser(User $user): User
    {
        return new self($user)->handle();
    }

    public function handle(): User
    {
        return $this->__invoke();
    }

    public function execute(): User
    {
        return $this->__invoke();
    }
}
