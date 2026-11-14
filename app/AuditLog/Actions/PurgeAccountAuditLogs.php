<?php

declare(strict_types=1);

namespace App\AuditLog\Actions;

use App\AuditLog\AuditLogEloquentModel;
use App\AuditLog\Properties\AuditableId;
use App\AuditLog\Properties\AuditableType;
use App\AuditLog\Properties\TeamId;
use App\AuditLog\Properties\UserId;
use Illuminate\Database\Eloquent\Builder;

final readonly class PurgeAccountAuditLogs
{
    public function __construct(
        private string $userId,
        private string $userMorphType,
    ) {}

    /**
     * Delete a user's account-level audit rows and every audit row about their
     * user record (which holds their name and email). Team rows are kept.
     */
    public function __invoke(): int
    {
        return AuditLogEloquentModel::query()
            ->where(fn (Builder $query) => $query
                ->where(UserId::getDatabaseTableColumnName(), $this->userId)
                ->whereNull(TeamId::getDatabaseTableColumnName()))
            ->orWhere(fn (Builder $query) => $query
                ->where(AuditableType::getDatabaseTableColumnName(), $this->userMorphType)
                ->where(AuditableId::getDatabaseTableColumnName(), $this->userId))
            ->delete();
    }

    public static function fromUserId(string $userId, string $userMorphType): int
    {
        return new self($userId, $userMorphType)->handle();
    }

    public function handle(): int
    {
        return $this->__invoke();
    }

    public function execute(): int
    {
        return $this->__invoke();
    }
}
