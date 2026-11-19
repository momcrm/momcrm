<?php

declare(strict_types=1);

namespace App\Rate\Actions;

use App\Rate\Rate;
use App\Rate\RateEloquentModel;

/**
 * Saves a rate's name, prices or billing period. Services on the rate are
 * billed at its new price from their next invoice.
 */
final readonly class UpdateRate
{
    public function __construct(
        private Rate $rate,
    ) {}

    public function __invoke(): Rate
    {
        $model = RateEloquentModel::query()->withoutGlobalScopes()->findOrFail($this->rate->getId()->toString());
        $model->update($this->rate->forDatabaseUpdate());

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
