<?php

declare(strict_types=1);

namespace App\Location\Actions;

use App\Location\Location;
use App\Location\LocationEloquentModel;
use App\Team\Scopes\CurrentTeamScope;

final readonly class UpdateLocation
{
    public function __construct(
        private Location $location,
    ) {}

    public function __invoke(): Location
    {
        LocationEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->find($this->location->getId()->toString())
            ?->update($this->location->forDatabaseUpdate());

        return $this->location;
    }

    public static function fromLocation(Location $location): Location
    {
        return new self($location)->handle();
    }

    public function handle(): Location
    {
        return $this->__invoke();
    }

    public function execute(): Location
    {
        return $this->__invoke();
    }
}
