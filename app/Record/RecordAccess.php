<?php

declare(strict_types=1);

namespace App\Record;

use App\CustomObject\Actions\FindCustomObject;
use App\CustomObject\CustomObject;
use App\CustomObject\Exceptions\CouldNotFindCustomObject;
use App\Record\Actions\FindRecord;
use App\Record\Exceptions\CouldNotFindRecord;
use Illuminate\Http\Request;

/**
 * Finds what a record route names: the object in `{object}` and, under it,
 * the record in `{record}`.
 */
final class RecordAccess
{
    /**
     * The live object named in the route, if it is the team's. An archived
     * object's records are out of reach until it is restored.
     *
     * @throws CouldNotFindCustomObject
     */
    public static function object(Request $request): CustomObject
    {
        $object = FindCustomObject::byIdForTeam((string) $request->route('object'), (string) $request->user()?->current_team_id);

        if ($object->isNull() || $object->isArchived()) {
            throw CouldNotFindCustomObject::notFound();
        }

        return $object;
    }

    /**
     * The record named in the route, if it is one of the object's.
     *
     * @throws CouldNotFindRecord
     */
    public static function record(Request $request, CustomObject $object): Record
    {
        $record = FindRecord::byIdForTeam((string) $request->route('record'), $object->getTeamId()->toString());

        if ($record->isNull() || ! $record->getObjectId()->equalsTo($object->getId()->toString())) {
            throw CouldNotFindRecord::notFound();
        }

        return $record;
    }

    /**
     * A record that exists only to say which object a new record would
     * belong to, for the access check on adding one.
     */
    public static function blank(CustomObject $object): Record
    {
        return Record::new()->setTeamId($object->getTeamId())->setObjectId($object->getId());
    }
}
