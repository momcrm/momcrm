<?php

declare(strict_types=1);

namespace App\AuditLog\Actions;

use App\AuditLog\AuditEventEnum;
use App\AuditLog\AuditLog;
use App\AuditLog\AuditLogEloquentModel;
use App\AuditLog\Contracts\Auditable;
use App\Shared\MorphMapKeyEnum;
use App\Shared\RequestContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

final readonly class RecordAudit
{
    /**
     * Attributes never written to the audit log, on top of the model's $hidden.
     */
    private const array ALWAYS_EXCLUDED = ['created_at', 'updated_at'];

    public function __construct(
        private AuditLog $auditLog,
    ) {}

    public function __invoke(): AuditLog
    {
        $model = AuditLogEloquentModel::query()->create($this->auditLog->forDatabaseCreate());

        return AuditLog::fromEloquentModel($model);
    }

    public static function fromAuditLog(AuditLog $auditLog): AuditLog
    {
        return new self($auditLog)->handle();
    }

    /**
     * Record a change to an Auditable model. Returns a null AuditLog when an
     * update only touched excluded attributes, e.g. the remember token.
     */
    public static function fromModel(Model&Auditable $model, AuditEventEnum $event): AuditLog
    {
        $excluded = [...$model->getHidden(), ...self::ALWAYS_EXCLUDED];

        [$oldValues, $newValues] = match ($event) {
            AuditEventEnum::Created => [null, Arr::except($model->getAttributes(), $excluded)],
            AuditEventEnum::Updated => self::changedValues($model, $excluded),
            AuditEventEnum::Deleted => [Arr::except($model->getAttributes(), $excluded), null],
        };

        if ($event === AuditEventEnum::Updated && $newValues === []) {
            return AuditLog::new();
        }

        $key = (string) $model->getKey();
        $teamId = $model->auditTeamId();

        // A deleted team's own row cannot reference it any more.
        if ($event === AuditEventEnum::Deleted && $teamId === $key) {
            $teamId = null;
        }

        return self::fromAuditLog(
            AuditLog::new()
                ->setTeamId($teamId)
                ->setUserId(self::actorId())
                ->setAuditableType(MorphMapKeyEnum::fromModel($model))
                ->setAuditableId($key)
                ->setEvent($event)
                ->setOldValues($oldValues)
                ->setNewValues($newValues)
                ->setRequestId(RequestContext::requestId())
                ->setIpAddress(RequestContext::ipAddress())
                ->setUserAgent(RequestContext::userAgent())
                ->setUrl(RequestContext::url()),
        );
    }

    public function handle(): AuditLog
    {
        return $this->__invoke();
    }

    public function execute(): AuditLog
    {
        return $this->__invoke();
    }

    /**
     * @param  array<int, string>  $excluded
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private static function changedValues(Model $model, array $excluded): array
    {
        $newValues = Arr::except($model->getChanges(), $excluded);
        $oldValues = Arr::only($model->getRawOriginal(), array_keys($newValues));

        return [$oldValues, $newValues];
    }

    private static function actorId(): ?string
    {
        $id = Auth::id();

        return $id === null ? null : (string) $id;
    }
}
