<?php

declare(strict_types=1);

namespace App\CustomField\Actions;

use App\CustomField\CustomField;
use App\CustomField\CustomFieldEloquentModel;
use App\CustomField\Properties\Id;

final readonly class CreateCustomField
{
    public function __construct(
        private CustomField $field,
    ) {}

    public function __invoke(): CustomField
    {
        if ($this->field->getId()->isNull()) {
            $this->field->setId(Id::generate());
        }

        $model = CustomFieldEloquentModel::query()->create($this->field->forDatabaseCreate());

        FindCustomField::forget($this->field->getTeamId()->toString());

        return CustomField::fromEloquentModel($model->refresh());
    }

    public static function fromCustomField(CustomField $field): CustomField
    {
        return new self($field)->handle();
    }

    public function handle(): CustomField
    {
        return $this->__invoke();
    }

    public function execute(): CustomField
    {
        return $this->__invoke();
    }
}
