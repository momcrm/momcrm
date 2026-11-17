<?php

declare(strict_types=1);

namespace App\CustomField\Actions;

use App\CustomField\CustomField;
use App\CustomField\CustomFieldEloquentModel;

/**
 * Deletes a field for good. Values records hold for it are no longer shown.
 */
final readonly class DeleteCustomField
{
    public function __construct(
        private CustomField $field,
    ) {}

    public function __invoke(): void
    {
        CustomFieldEloquentModel::query()->withoutGlobalScopes()->findOrFail($this->field->getId()->toString())->delete();
    }

    public static function fromCustomField(CustomField $field): void
    {
        new self($field)->handle();
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
