<?php

declare(strict_types=1);

namespace App\CustomObject\Actions;

use App\CustomObject\CustomObject;
use App\CustomObject\CustomObjectEloquentModel;
use App\CustomObject\Properties\Id;

final readonly class CreateCustomObject
{
    public function __construct(
        private CustomObject $object,
    ) {}

    public function __invoke(): CustomObject
    {
        if ($this->object->getId()->isNull()) {
            $this->object->setId(Id::generate());
        }

        $model = CustomObjectEloquentModel::query()->create($this->object->forDatabaseCreate());

        FindCustomObject::forget($this->object->getTeamId()->toString());

        return CustomObject::fromEloquentModel($model->refresh());
    }

    public static function fromCustomObject(CustomObject $object): CustomObject
    {
        return new self($object)->handle();
    }

    public function handle(): CustomObject
    {
        return $this->__invoke();
    }

    public function execute(): CustomObject
    {
        return $this->__invoke();
    }
}
