<?php

declare(strict_types=1);

namespace App\Service\Actions;

use App\Activity\Actions\RecordActivity;
use App\Activity\ActivityTypeEnum;
use App\Rate\Actions\FindRate;
use App\Rate\Rate;
use App\Service\Service;
use App\Service\ServiceEloquentModel;
use App\Team\Scopes\CurrentTeamScope;

/**
 * Moves a service to another rate, so it is billed from the new rate's price
 * from its next invoice.
 */
final readonly class ChangeServiceRate
{
    public function __construct(
        private Service $service,
        private Rate $rate,
        private ?string $userId = null,
    ) {}

    public function __invoke(): Service
    {
        $model = ServiceEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->find($this->service->getId()->toString());

        if ($model === null || $this->rate->getId()->equalsTo($this->service->getRateId()->toNullableString())) {
            return $this->service;
        }

        $previous = FindRate::byIdForTeam($this->service->getRateId()->toNullableString(), $this->service->getTeamId()->toString());

        $this->service->setRateId($this->rate->getId());
        $model->update($this->service->forDatabaseUpdate());

        RecordActivity::for(
            type: ActivityTypeEnum::ServiceRateChanged,
            userId: $this->userId,
            teamId: $this->service->getTeamId()->toString(),
            subject: $model,
            details: [
                'name' => $this->service->getName()->toString(),
                'from' => $previous->getName()->toNullableString(),
                'to' => $this->rate->getName()->toString(),
            ],
        );

        return $this->service;
    }

    public static function to(Service $service, Rate $rate, ?string $userId = null): Service
    {
        return new self($service, $rate, $userId)->handle();
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
