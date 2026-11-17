<?php

declare(strict_types=1);

namespace App\Record\Controllers;

use App\AccessRule\AccessResourceEnum;
use App\AccessRule\Engine\AccessAbilities;
use App\CustomField\Actions\FindCustomField;
use App\CustomField\CustomFieldQuery;
use App\CustomField\CustomFieldRelationLabels;
use App\CustomField\CustomFieldTargetEnum;
use App\CustomObject\Exceptions\CouldNotFindCustomObject;
use App\Record\Actions\FindRecord;
use App\Record\Record;
use App\Record\RecordAccess;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * One custom object's records: searchable by name, and filtered and sorted
 * by the object's fields.
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
        $teamId = $object->getTeamId()->toString();
        $objectId = $object->getId()->toString();
        $fields = FindCustomField::listForTeam($teamId, CustomFieldTargetEnum::Record, objectId: $objectId);
        $sort = (string) $request->query('sort');
        $sort = array_key_exists($sort, FindRecord::SORTS) || CustomFieldQuery::sortField($sort, $fields)->isNotNull() ? $sort : 'name';
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';
        $customFields = CustomFieldQuery::filtersFromRequest($request, $fields);
        $records = FindRecord::paginateForObject($teamId, $objectId, $search, $sort, $direction, customFields: $customFields);
        $labels = CustomFieldRelationLabels::forObject(
            $teamId,
            $objectId,
            ...array_map(fn (Record $record): array => $record->getCustomFields()->toValues(), $records->items()),
        );

        return Inertia::render('objects/show', [
            'object' => $object->toSummary(),
            'fields' => $fields->toSummaries(),
            'records' => $records->through(fn (Record $record): array => [
                ...$record->toSummary(),
                'custom_field_labels' => CustomFieldRelationLabels::only($labels, $record->getCustomFields()->toValues()),
            ]),
            'filters' => ['search' => $search, 'sort' => $sort, 'direction' => $direction, 'custom_fields' => $customFields],
            'can' => AccessAbilities::for($request->user(), AccessResourceEnum::Record, RecordAccess::blank($object)),
        ]);
    }
}
