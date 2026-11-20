<?php

declare(strict_types=1);

namespace App\Charge\Actions;

use App\Charge\Charge;
use App\Charge\ChargeEloquentModel;

final readonly class UpdateCharge
{
    public function __construct(
        private Charge $charge,
    ) {}

    public function __invoke(): Charge
    {
        $model = ChargeEloquentModel::query()
            ->withoutGlobalScopes()
            ->findOrFail($this->charge->getId()->toString());

        $model->update($this->charge->forDatabaseUpdate());

        return Charge::fromEloquentModel($model->refresh());
    }

    public static function fromCharge(Charge $charge): Charge
    {
        return new self($charge)->handle();
    }

    public function handle(): Charge
    {
        return $this->__invoke();
    }

    public function execute(): Charge
    {
        return $this->__invoke();
    }
}
