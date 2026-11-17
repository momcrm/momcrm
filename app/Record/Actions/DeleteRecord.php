<?php

declare(strict_types=1);

namespace App\Record\Actions;

use App\Record\Record;
use App\Record\RecordEloquentModel;

final readonly class DeleteRecord
{
    public function __construct(
        private Record $record,
    ) {}

    public function __invoke(): void
    {
        RecordEloquentModel::query()->withoutGlobalScopes()->findOrFail($this->record->getId()->toString())->delete();
    }

    public static function fromRecord(Record $record): void
    {
        new self($record)->handle();
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
