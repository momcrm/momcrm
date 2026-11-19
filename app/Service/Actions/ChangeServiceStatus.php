<?php

declare(strict_types=1);

namespace App\Service\Actions;

use App\Activity\Actions\RecordActivity;
use App\Activity\ActivityTypeEnum;
use App\Service\Service;
use App\Service\ServiceEloquentModel;
use App\Service\ServiceStatusEnum;
use App\Team\Scopes\CurrentTeamScope;

/**
 * Turns a service on or off. An inactive service stays on the customer but is
 * no longer billed, and stops renewing by itself.
 */
final readonly class ChangeServiceStatus
{
    public function __construct(
        private Service $service,
        private ServiceStatusEnum $status,
        private ?string $userId = null,
    ) {}

    public function __invoke(): Service
    {
        $model = ServiceEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->find($this->service->getId()->toString());

        if ($model === null || $this->service->getStatus()->toEnum() === $this->status) {
            return $this->service;
        }

        $this->service->setStatus($this->status);

        if ($this->status === ServiceStatusEnum::Inactive) {
            $this->service->setAutoRenew(false);
        }

        $model->update($this->service->forDatabaseUpdate());

        RecordActivity::for(
            type: $this->status === ServiceStatusEnum::Active ? ActivityTypeEnum::ServiceActivated : ActivityTypeEnum::ServiceDeactivated,
            userId: $this->userId,
            teamId: $this->service->getTeamId()->toString(),
            subject: $model,
            details: ['name' => $this->service->getName()->toString()],
        );

        return $this->service;
    }

    public static function activate(Service $service, ?string $userId = null): Service
    {
        return new self($service, ServiceStatusEnum::Active, $userId)->handle();
    }

    public static function deactivate(Service $service, ?string $userId = null): Service
    {
        return new self($service, ServiceStatusEnum::Inactive, $userId)->handle();
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
