<?php

declare(strict_types=1);

namespace App\Location\Actions;

use App\Location\Location;
use App\Location\LocationEloquentModel;
use App\Team\Scopes\CurrentTeamScope;

final readonly class DeleteLocation
{
    public function __construct(
        private Location $location,
    ) {}

    public function __invoke(): void
    {
        LocationEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->find($this->location->getId()->toString())
            ?->delete();
    }

    public static function fromLocation(Location $location): void
    {
        new self($location)->handle();
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
