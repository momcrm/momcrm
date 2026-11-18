<?php

declare(strict_types=1);

namespace App\User\Actions;

use App\User\Properties\Email;
use App\User\Properties\IsPlatformAdmin;
use App\User\User;
use App\User\UserEloquentModel;

/**
 * Makes someone a platform admin, or stops them being one. Run from the
 * command line only: there is no page for it, so no team owner can grant it.
 */
final readonly class SetPlatformAdmin
{
    public function __construct(
        private string $email,
        private bool $isPlatformAdmin,
    ) {}

    /**
     * The person, or an empty User when nobody has that email address.
     */
    public function __invoke(): User
    {
        $user = UserEloquentModel::query()
            ->where(Email::getDatabaseTableColumnName(), $this->email)
            ->first();

        if ($user === null) {
            return User::new();
        }

        $user->update([IsPlatformAdmin::getDatabaseTableColumnName() => $this->isPlatformAdmin]);

        return User::fromEloquentModel($user);
    }

    public static function forEmail(string $email, bool $isPlatformAdmin = true): User
    {
        return new self($email, $isPlatformAdmin)->handle();
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
