<?php

declare(strict_types=1);

namespace App\Service\Actions;

use App\Service\Properties\Id;
use App\Service\Service;
use App\Service\ServiceEloquentModel;
use Illuminate\Support\Facades\DB;

final readonly class CreateService
{
    public function __construct(
        private Service $service,
    ) {}

    public function __invoke(): Service
    {
        return DB::transaction(function (): Service {
            if ($this->service->getId()->isNull()) {
                $this->service->setId(Id::generate());
            }

            $model = ServiceEloquentModel::query()->create($this->service->forDatabaseCreate());

            return Service::fromEloquentModel($model->refresh());
        });
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
