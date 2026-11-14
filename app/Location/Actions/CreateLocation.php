<?php

declare(strict_types=1);

namespace App\Location\Actions;

use App\Location\Location;
use App\Location\LocationEloquentModel;
use App\Location\Properties\Id;
use Illuminate\Support\Facades\DB;

final readonly class CreateLocation
{
    public function __construct(
        private Location $location,
    ) {}

    public function __invoke(): Location
    {
        return DB::transaction(function (): Location {
            if ($this->location->getId()->isNull()) {
                $this->location->setId(Id::generate());
            }

            $model = LocationEloquentModel::query()->create($this->location->forDatabaseCreate());

            return Location::fromEloquentModel($model->refresh());
        });
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
