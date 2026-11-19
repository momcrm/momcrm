<?php

declare(strict_types=1);

namespace App\Rate\Actions;

use App\Rate\Properties\Id;
use App\Rate\Rate;
use App\Rate\RateEloquentModel;

final readonly class CreateRate
{
    public function __construct(
        private Rate $rate,
    ) {}

    public function __invoke(): Rate
    {
        if ($this->rate->getId()->isNull()) {
            $this->rate->setId(Id::generate());
        }

        $model = RateEloquentModel::query()->create($this->rate->forDatabaseCreate());

        return Rate::fromEloquentModel($model->refresh());
    }

    public static function fromRate(Rate $rate): Rate
    {
        return new self($rate)->handle();
    }

    public function handle(): Rate
    {
        return $this->__invoke();
    }

    public function execute(): Rate
    {
        return $this->__invoke();
    }
}
