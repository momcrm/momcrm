<?php

declare(strict_types=1);

namespace App\CustomField\Actions;

use App\CustomField\CustomField;
use App\CustomField\CustomFieldEloquentModel;

/**
 * Saves a field's name, hint, options, place in the list or archived state.
 */
final readonly class UpdateCustomField
{
    public function __construct(
        private CustomField $field,
    ) {}

    public function __invoke(): CustomField
    {
        $model = CustomFieldEloquentModel::query()->withoutGlobalScopes()->findOrFail($this->field->getId()->toString());
        $model->update($this->field->forDatabaseUpdate());

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
