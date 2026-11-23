<?php

declare(strict_types=1);

namespace App\Holiday\Actions;

use App\Holiday\Holiday;
use App\Holiday\HolidayEloquentModel;
use App\Holiday\Properties\Id;

final readonly class CreateHoliday
{
    public function __construct(
        private Holiday $holiday,
    ) {}

    public function __invoke(): Holiday
    {
        if ($this->holiday->getId()->isNull()) {
            $this->holiday->setId(Id::generate());
        }

        $model = HolidayEloquentModel::query()->create($this->holiday->forDatabaseCreate());

        return Holiday::fromEloquentModel($model->refresh());
    }

    public static function fromHoliday(Holiday $holiday): Holiday
    {
        return new self($holiday)->handle();
    }

    public function handle(): Holiday
    {
        return $this->__invoke();
    }

    public function execute(): Holiday
    {
        return $this->__invoke();
    }
}
