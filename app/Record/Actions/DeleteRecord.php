<?php

declare(strict_types=1);

namespace App\Record\Actions;

use App\Attachment\Actions\DeleteAttachments;
use App\Record\Record;
use App\Record\RecordEloquentModel;
use App\Shared\MorphMapKeyEnum;

/**
 * Deletes a record for good, with its files. Its comments go with it.
 */
final readonly class DeleteRecord
{
    public function __construct(
        private Record $record,
    ) {}

    public function __invoke(): void
    {
        DeleteAttachments::for(MorphMapKeyEnum::Record, $this->record->getId()->toString());

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
