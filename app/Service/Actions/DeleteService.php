<?php

declare(strict_types=1);

namespace App\Service\Actions;

use App\Service\Service;
use App\Service\ServiceEloquentModel;
use App\Team\Scopes\CurrentTeamScope;

final readonly class DeleteService
{
    public function __construct(
        private Service $service,
    ) {}

    public function __invoke(): void
    {
        ServiceEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->find($this->service->getId()->toString())
            ?->delete();
    }

    public static function fromService(Service $service): void
    {
        new self($service)->handle();
    }

    public function handle(): void
    {
        $this->__invoke();
    }

    public function execute(): void
    {
        $this->__invoke();
    }
}
