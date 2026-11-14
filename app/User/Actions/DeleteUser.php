<?php

declare(strict_types=1);

namespace App\User\Actions;

use App\Activity\Actions\PurgeAccountActivities;
use App\AuditLog\Actions\PurgeAccountAuditLogs;
use App\User\Properties\Id;
use App\User\User;
use App\User\UserEloquentModel;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class DeleteUser
{
    public function __construct(
        private User $user,
    ) {}

    /**
     * Delete the user. Teams they own are removed by the owner_id cascade.
     *
     * Their account-level activity and audit rows go too, including the audit
     * row of this deletion; team rows stay with the actor nulled by the FK.
     *
     * @throws Throwable
     */
    public function __invoke(): User
    {
        $userId = $this->user->getId()->toString();

        DB::transaction(function () use ($userId): void {
            PurgeAccountActivities::fromUserId($userId);
            PurgeAccountAuditLogs::fromUserId($userId);

            UserEloquentModel::query()
                ->where(Id::getDatabaseTableColumnName(), $userId)
                ->first()
                ?->delete();

            PurgeAccountAuditLogs::fromUserId($userId);
        });

        return $this->user;
    }

    /**
     * @throws Throwable
     */
    public static function fromUser(User $user): User
    {
        return new self($user)->handle();
    }

    /**
     * @throws Throwable
     */
    public function handle(): User
    {
        return $this->__invoke();
    }

    /**
     * @throws Throwable
     */
    public function execute(): User
    {
        return $this->__invoke();
    }
}
