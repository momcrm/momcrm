<?php

declare(strict_types=1);

namespace App\Record\Actions;

use App\Record\Record;
use App\Record\RecordEloquentModel;

final readonly class UpdateRecord
{
    public function __construct(
        private Record $record,
    ) {}

    public function __invoke(): Record
    {
        $model = RecordEloquentModel::query()->withoutGlobalScopes()->findOrFail($this->record->getId()->toString());
        $model->update($this->record->forDatabaseUpdate());

        return Record::fromEloquentModel($model->refresh());
    }

    public static function fromRecord(Record $record): Record
    {
        return new self($record)->handle();
    }

    public function handle(): Record
    {
        return $this->__invoke();
    }

    public function execute(): Record
    {
        return $this->__invoke();
    }
}
