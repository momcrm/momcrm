<?php

declare(strict_types=1);

namespace App\Service\Actions;

use App\Service\Service;
use App\Service\ServiceEloquentModel;
use App\Team\Scopes\CurrentTeamScope;

final readonly class UpdateService
{
    public function __construct(
        private Service $service,
    ) {}

    public function __invoke(): Service
    {
        ServiceEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->find($this->service->getId()->toString())
            ?->update($this->service->forDatabaseUpdate());

        return $this->service;
    }

    public static function fromService(Service $service): Service
    {
        return new self($service)->handle();
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
