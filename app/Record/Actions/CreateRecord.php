<?php

declare(strict_types=1);

namespace App\Record\Actions;

use App\Record\Properties\Id;
use App\Record\Properties\Number;
use App\Record\Properties\ObjectId;
use App\Record\Record;
use App\Record\RecordEloquentModel;
use App\Shared\Table;
use App\Shared\Types;
use Illuminate\Support\Facades\DB;

/**
 * Adds a record to an object, numbered after the object's last record.
 */
final readonly class CreateRecord
{
    public function __construct(
        private Record $record,
    ) {}

    public function __invoke(): Record
    {
        return DB::transaction(function (): Record {
            if ($this->record->getId()->isNull()) {
                $this->record->setId(Id::generate());
            }

            // Locks the object's records until the insert commits, so two
            // people adding at once get different numbers.
            $numbers = DB::table(Table::Records->value)
                ->where(ObjectId::getDatabaseTableColumnName(), $this->record->getObjectId()->toString())
                ->lockForUpdate()
                ->pluck(Number::getDatabaseTableColumnName());

            $this->record->setNumber(Types::intOrZero($numbers->max()) + 1);

            $model = RecordEloquentModel::query()->create($this->record->forDatabaseCreate());

            return Record::fromEloquentModel($model->refresh());
        });
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
