<?php

declare(strict_types=1);

namespace App\User\Actions;

use App\User\Properties\Id;
use App\User\User;
use App\User\UserEloquentModel;

final readonly class CreateUser
{
    public function __construct(
        private User $user,
    ) {}

    public function __invoke(): User
    {
        if ($this->user->getId()->isNull()) {
            $this->user->setId(Id::generate());
        }

        $model = UserEloquentModel::query()->create($this->user->forDatabaseCreate());

        return User::fromEloquentModel($model);
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
