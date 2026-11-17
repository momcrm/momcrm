<?php

declare(strict_types=1);

namespace App\CustomObject\Actions;

use App\CustomObject\CustomObject;
use App\CustomObject\CustomObjectEloquentModel;

/**
 * Deletes an object for good.
 */
final readonly class DeleteCustomObject
{
    public function __construct(
        private CustomObject $object,
    ) {}

    public function __invoke(): void
    {
        CustomObjectEloquentModel::query()->withoutGlobalScopes()->findOrFail($this->object->getId()->toString())->delete();

        FindCustomObject::forget($this->object->getTeamId()->toString());
    }

    public static function fromCustomObject(CustomObject $object): void
    {
        new self($object)->handle();
    }

    public function handle(): void
    {
        $this->__invoke();
    }

    public function execute(): void
    {
        $this->__invoke();
    }
}
