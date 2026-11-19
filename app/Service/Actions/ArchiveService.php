<?php

declare(strict_types=1);

namespace App\Service\Actions;

use App\Activity\Actions\RecordActivity;
use App\Activity\ActivityTypeEnum;
use App\Service\Service;
use App\Service\ServiceEloquentModel;
use App\Team\Scopes\CurrentTeamScope;
use Carbon\CarbonImmutable;

/**
 * Puts a service away in the archive, or brings it back. An archived service
 * drops out of lists and is never billed; archiving also stops it renewing.
 */
final readonly class ArchiveService
{
    public function __construct(
        private Service $service,
        private bool $archive = true,
        private ?string $userId = null,
    ) {}

    public function __invoke(): Service
    {
        $model = ServiceEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->find($this->service->getId()->toString());

        if ($model === null || $this->service->isArchived() === $this->archive) {
            return $this->service;
        }

        $this->service->setArchivedAt($this->archive ? CarbonImmutable::now() : null);

        if ($this->archive) {
            $this->service->setAutoRenew(false);
        }

        $model->update($this->service->forDatabaseUpdate());

        RecordActivity::for(
            type: $this->archive ? ActivityTypeEnum::ServiceArchived : ActivityTypeEnum::ServiceRestored,
            userId: $this->userId,
            teamId: $this->service->getTeamId()->toString(),
            subject: $model,
            details: ['name' => $this->service->getName()->toString()],
        );

        return $this->service;
    }

    public static function archive(Service $service, ?string $userId = null): Service
    {
        return new self($service, archive: true, userId: $userId)->handle();
    }

    public static function restore(Service $service, ?string $userId = null): Service
    {
        return new self($service, archive: false, userId: $userId)->handle();
    }

    public function handle(): Service
    {
        return $this->__invoke();
    }

    public function execute(): Service
    {
        return $this->__invoke();
    }
}
