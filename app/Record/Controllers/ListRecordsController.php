<?php

declare(strict_types=1);

namespace App\Record\Controllers;

use App\AccessRule\AccessResourceEnum;
use App\AccessRule\Engine\AccessAbilities;
use App\CustomObject\Exceptions\CouldNotFindCustomObject;
use App\Record\Actions\FindRecord;
use App\Record\Record;
use App\Record\RecordAccess;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * One custom object's records, searchable by name and sortable.
 */
final class ListRecordsController
{
    /**
     * @throws CouldNotFindCustomObject
     */
    public function __invoke(Request $request): Response
    {
        $object = RecordAccess::object($request);
        $search = $request->string('search')->trim()->toString() ?: null;
        $sort = array_key_exists((string) $request->query('sort'), FindRecord::SORTS) ? (string) $request->query('sort') : 'name';
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';

        return Inertia::render('objects/show', [
            'object' => $object->toSummary(),
            'records' => FindRecord::paginateForObject($object->getTeamId()->toString(), $object->getId()->toString(), $search, $sort, $direction)
                ->through(fn (Record $record): array => $record->toSummary()),
            'filters' => ['search' => $search, 'sort' => $sort, 'direction' => $direction],
            'can' => AccessAbilities::for($request->user(), AccessResourceEnum::Record, RecordAccess::blank($object)),
        ]);
    }
}
