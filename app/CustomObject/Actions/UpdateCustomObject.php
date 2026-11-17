<?php

declare(strict_types=1);

namespace App\CustomObject\Actions;

use App\CustomObject\CustomObject;
use App\CustomObject\CustomObjectEloquentModel;

/**
 * Saves an object's names, look, place in the list or archived state.
 */
final readonly class UpdateCustomObject
{
    public function __construct(
        private CustomObject $object,
    ) {}

    public function __invoke(): CustomObject
    {
        $model = CustomObjectEloquentModel::query()->withoutGlobalScopes()->findOrFail($this->object->getId()->toString());
        $model->update($this->object->forDatabaseUpdate());

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
