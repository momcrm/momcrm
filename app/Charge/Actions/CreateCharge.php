<?php

declare(strict_types=1);

namespace App\Charge\Actions;

use App\Charge\Charge;
use App\Charge\ChargeEloquentModel;
use App\Charge\Properties\Id;

final readonly class CreateCharge
{
    public function __construct(
        private Charge $charge,
    ) {}

    public function __invoke(): Charge
    {
        if ($this->charge->getId()->isNull()) {
            $this->charge->setId(Id::generate());
        }

        $model = ChargeEloquentModel::query()->create($this->charge->forDatabaseCreate());

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
