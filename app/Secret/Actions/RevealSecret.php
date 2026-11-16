<?php

declare(strict_types=1);

namespace App\Secret\Actions;

use App\AuditLog\Actions\RecordAudit;
use App\AuditLog\AuditEventEnum;
use App\Secret\Secret;
use App\Secret\SecretEloquentModel;
use App\Team\Scopes\CurrentTeamScope;

/**
 * Decrypts a secret's key/value pairs for someone to read, and records in the
 * audit log that they were viewed, by whom and from where.
 */
final readonly class RevealSecret
{
    public function __construct(
        private Secret $secret,
    ) {}

    /**
     * @return list<array{key: string, value: string}>
     */
    public function __invoke(): array
    {
        $model = SecretEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->find($this->secret->getId()->toString());

        if ($model === null) {
            return [];
        }

        RecordAudit::fromModel($model, AuditEventEnum::Viewed);

        return $model->values;
    }

    /**
     * @return list<array{key: string, value: string}>
     */
    public static function valuesOf(Secret $secret): array
    {
        return new self($secret)->handle();
    }

    /**
     * @return list<array{key: string, value: string}>
     */
    public function handle(): array
    {
        return $this->__invoke();
    }

    /**
     * @return list<array{key: string, value: string}>
     */
    public function execute(): array
    {
        return $this->__invoke();
    }
}
